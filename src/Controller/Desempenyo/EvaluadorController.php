<?php

declare(strict_types=1);

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Desempenyo\Evalua;
use App\Entity\Plantilla\Empleado;
use App\Entity\Usuario;
use App\Form\Desempenyo\CorreccionType;
use App\Form\Desempenyo\EvaluadorType;
use App\Form\Desempenyo\RegistroType;
use App\Form\VolcadoType;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Desempenyo\EvaluaRepository;
use App\Repository\Plantilla\EmpleadoRepository;
use App\Service\Csv;
use App\Service\MessageGenerator;
use App\Service\RutaActual;
use App\Service\SirhusLock;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Predis\ClientInterface;
use Redis;
use RedisArray;
use RedisCluster;
use RedisException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function Symfony\Component\String\u;

#[Route(path: '/desempenyo', name: 'desempenyo_')]
class EvaluadorController extends AbstractController
{
    /** @var ClientInterface|Redis|RedisArray|RedisCluster $redis */
    private readonly object $redis;

    /** @var string $rutaBase Ruta base de la aplicación actual */
    private readonly string $rutaBase;

    /** @var int $ttl Tiempo de bloqueo en s. */
    private readonly int $ttl;

    public function __construct(
        private readonly MessageGenerator $generator,
        private readonly SirhusLock       $lock,
        private readonly RutaActual       $actual,
        private readonly EvaluaRepository $evaluaRepository,
        #[Autowire('%app.redis_url%')]
        private readonly string           $redisUrl,
    ) {
        $this->redis = RedisAdapter::createConnection($this->redisUrl);
        $this->rutaBase = $this->actual->getRuta() ?? 'inicio';
        $this->ttl = 60;
    }

    #[Route(
        path: '/admin/cuestionario/{id}/evaluador/',
        name: 'admin_evaluador_index',
        defaults: ['titulo' => 'Evaluaciones de Cuestionario de Desempeño'],
        methods: ['GET']
    )]
    public function index(Request $request, Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $tipo = is_numeric($request->query->get('tipo')) ? $request->query->getInt('tipo') : -1;
        switch ($tipo) {
            case Evalua::AUTOEVALUACION:
            case Evalua::NO_EVALUACION:
                $evaluaciones = $this->evaluaRepository->findByEvaluacion([
                    'cuestionario' => $cuestionario,
                    'tipo' => $tipo,
                ]);
                break;
            case Evalua::EVALUA_RESPONSABLE:
            case Evalua::EVALUA_OTRO:
                $evaluaciones = [];
                $cuenta = $request->query->has('cuenta');
                if ($cuenta) {
                    // Contar evaluados por cada evaluador y rechazados
                    $idRechazados = array_map(
                        static fn (Evalua $evalua) => $evalua->getEmpleado()->getId(),
                        $this->evaluaRepository->findByEvaluacion([
                            'cuestionario' => $cuestionario,
                            'rechazados' => true,
                        ])
                    );
                    $evaluaciones = array_reduce(
                        $this->evaluaRepository->findByEvaluacion(['cuestionario' => $cuestionario, 'tipo' => $tipo]),
                        /** @param array<array-key, array{evaluador: ?Empleado, asignados: int, evaluados: int, rechazados: int}>|null $cuentas */
                        function (?array $cuentas, Evalua $evaluacion) use ($idRechazados) {
                            $id = $evaluacion->getEvaluador()?->getId() ?? 0;
                            if (0 !== $id && isset($cuentas[$id])) {
                                ++$cuentas[$id]['asignados'];
                                if (null !== $evaluacion->getFormulario()?->getFechaEnvio()) {
                                    ++$cuentas[$id]['evaluados'];
                                }
                                if (in_array($evaluacion->getEmpleado()->getId(), $idRechazados)) {
                                    ++$cuentas[$id]['rechazados'];
                                }
                            } else {
                                $cuentas[$id] = [
                                    'evaluador' => $evaluacion->getEvaluador(),
                                    'asignados' => 1,
                                    'evaluados' => null === $evaluacion->getFormulario()?->getFechaEnvio() ? 0 : 1,
                                    'rechazados' => null === $evaluacion->getRechazado() ? 0 : 1,
                                ];
                            }
                            return $cuentas;
                        }
                    );
                } else {
                    foreach ($this->evaluaRepository->findByEvaluacion(['cuestionario' => $cuestionario]) as $evaluacion) {
                        if (in_array($evaluacion->getTipoEvaluador(), [Evalua::NO_EVALUACION, Evalua::AUTOEVALUACION, $tipo])) {
                            $evaluaciones[(int)$evaluacion->getEmpleado()?->getId()][$evaluacion->getTipoEvaluador()] = $evaluacion;
                        }
                    }
                }
                break;
            default:
                $evaluaciones = [];
                foreach ($this->evaluaRepository->findByEvaluacion(['cuestionario' => $cuestionario]) as $evaluacion) {
                    $evaluaciones[(int) $evaluacion->getEmpleado()?->getId()][$evaluacion->getTipoEvaluador()] = $evaluacion;
                }
        }
        $ultimo = null;

        try {
            $claveRedis = sprintf('evaluacion-%d', $tipo);
            /** @var array{finalizado: bool, inicio: string[]} $datos */
            $datos = json_decode((string) $this->redis->get($claveRedis), true);
            if (true === $datos['finalizado']) {
                $ultimo = new DateTimeImmutable(
                    $datos['inicio']['date'],
                    new DateTimeZone($datos['inicio']['timezone'] ?? 'UTC')
                );
            }
        } catch (Exception) {
        }

        return $this->render('desempenyo/admin/evaluador/index.html.twig', [
            'cuestionario' => $cuestionario,
            'evaluaciones' => $evaluaciones,
            'tipo' => $tipo,
            'cuenta' => $cuenta ?? false,
            'volcado_empleados' => $ultimo,
        ]);
    }

    #[Route(
        path: '/admin/cuestionario/{cuestionario}/evaluador/{evalua}/',
        name: 'admin_evaluador_delete',
        defaults: ['titulo' => 'Eliminar Asignación de Evaluación'],
        methods: ['POST']
    )]
    public function delete(
        Request      $request,
        Cuestionario $cuestionario,
        Evalua       $evalua
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        $id = (int) $evalua->getId();
        $token = sprintf('delete%d-%d', $cuestionario->getId() ?? 0, $id);
        if ($cuestionario->getId() !== $evalua->getCuestionario()?->getId()) {
            $this->addFlash('warning', 'La evaluación no corresponde a este cuestionario.');
        } elseif ($this->isCsrfTokenValid($token, $request->request->getString('_token'))) {
            $this->evaluaRepository->remove($evalua, true);
            $this->generator->logAndFlash('info', 'Evaluación eliminada correctamente', [
                'id' => $id,
                'codigo' => $cuestionario->getCodigo(),
                'empleado' => $evalua->getEmpleado()?->getDocIdentidad(),
                'evaluador' => $evalua->getEvaluador()?->getDocIdentidad(),
                'tipo_evaluador' => $evalua->getTipoEvaluador(),
            ]);
        }

        return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', [
            'id' => $cuestionario->getId(),
        ], Response::HTTP_SEE_OTHER);
    }

    /** Cargar empleados activos para autoevaluación que no hayan solicitado exclusión en un cuestionario. */
    #[Route(
        path: '/admin/cuestionario/{id}/evaluador/auto',
        name: 'admin_evaluador_auto',
        defaults: ['titulo' => 'Cargar Empleados para Autoevaluación'],
        methods: ['GET']
    )]
    public function cargarAutoevaluacion(
        Request            $request,
        EmpleadoRepository $empleadoRepository,
        Cuestionario       $cuestionario,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        $claveRedis = sprintf('evaluacion-%d', Evalua::AUTOEVALUACION);
        if (null === $this->lock->acquire($this->ttl)) {
            $this->addFlash('warning', 'Recurso bloqueado por otra operación de carga.');

            return $this->redirectToRoute($request->attributes->getString('_route'));
        }

        $inicio = microtime(true);
        $externo = EVALUA::EXTERNO;
        $empleados = $empleadoRepository->findCesados(false);
        $datos = [
            'inicio' => new DateTimeImmutable(),
            'total' => count($empleados),
            'actual' => 0,
            'nuevos' => 0,
            'duracion' => 0,
            'finalizado' => false,
        ];

        foreach ($empleados as $empleado) {
            ++$datos['actual'];
            if (!$this->evaluaRepository->findOneBy([
                    'empleado' => $empleado,
                    'tipo_evaluador' => Evalua::AUTOEVALUACION,
                    'cuestionario' => $cuestionario,
                    'origen' => $externo,
                ]) instanceof Evalua) {
                $evalua = new Evalua();
                $evalua
                    ->setEmpleado($empleado)
                    ->setTipoEvaluador()
                    ->setCuestionario($cuestionario)
                    ->setOrigen($externo)
                ;
                $this->evaluaRepository->save($evalua);
                ++$datos['nuevos'];
            }

            $datos['duracion'] = microtime(true) - $inicio;
            try {
                $this->redis->set($claveRedis, json_encode($datos));
            } catch (RedisException) {
            }
        }

        if ($datos['nuevos'] > 0) {
            $this->evaluaRepository->flush();
            $datos['duracion'] = microtime(true) - $inicio;
            $this->generator->logAndFlash('success', 'Volcado de autoevaluaciones', [
                'cuestionario' => $cuestionario->getCodigo(),
                'nuevos' => $datos['nuevos'],
                'duracion' => $datos['duracion'],
            ]);
        } else {
            $this->addFlash('warning', 'No se han registrado autoevaluaciones nuevas.');
        }

        $datos['finalizado'] = true;
        try {
            $this->redis->set($claveRedis, json_encode($datos));
        } catch (RedisException) {
        }

        $this->lock->release();

        return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', [
                'id' => $cuestionario->getId(),
                'tipo' => Evalua::AUTOEVALUACION,
            ]
        );
    }

    /** Cargar datos que relacionan empleado con su evaluador para el cuestionario indicado. */
    #[Route(
        path: '/admin/cuestionario/{id}/{tipo}/carga',
        name: 'admin_evaluador_carga',
        requirements: ['tipo' => '(evaluador)|(otro)'],
        defaults: ['titulo' => 'Cargar Evaluadores de Empleados'],
        methods: ['GET', 'POST']
    )]
    public function cargarEvaluacion(
        Request            $request,
        EmpleadoRepository $empleadoRepository,
        Cuestionario       $cuestionario,
        string             $tipo,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        $tipo = match ($tipo) {
            'evaluador' => Evalua::EVALUA_RESPONSABLE,
            'otro' => Evalua::EVALUA_OTRO,
            default => null,
        };
        if (null === $tipo) {
            $this->addFlash('warning', 'Tipo de evaluador desconocido.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (null === $this->lock->acquire($this->ttl)) {
            $this->addFlash('warning', 'Recurso bloqueado por otra operación de carga.');

            return $this->redirectToRoute($request->attributes->getString('_route'));
        }

        $campos = [
            'DNI USUARIO',      // Documento empleado
            'DNI VALIDADOR',    // Documento evaluador
        ];
        $form = $this->createForm(VolcadoType::class, ['maxSize' => '256k']);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            set_time_limit($this->ttl); // La carga completa puede tardar más de los 30 s. por defecto
            $inicio = microtime(true);
            $claveRedis = sprintf('evaluacion-%d', $tipo);
            $lineas = [];
            // Cargar fichero CSV
            /** @var UploadedFile $fichero */
            $fichero = $form->get('fichero_csv')->getData();
            $csv = new Csv();
            $csv->abrir($fichero);
            if (!$csv->comprobarCabeceras($campos)) {
                $this->generator->logAndFlash('error', 'No se puede abrir el fichero de datos o no es correcto', [
                    'fichero' => $fichero->getClientOriginalName(),
                ]);

                return $this->redirectToRoute(
                    $request->attributes->getString('_route'),
                    ['id' => $cuestionario->getId()]
                );
            }

            while (($datos = $csv->leer($campos)) !== null) {
                $lineas[] = $datos;
            }

            $csv->cerrar();
            $datos = [
                'inicio' => new DateTimeImmutable(),
                'total' => count($lineas),
                'actual' => 0,
                'nuevos' => 0,
                'descartados' => 0,
                'duracion' => 0,
                'finalizado' => false,
            ];

            // Grabar datos
            $fichero = EVALUA::FICHERO;
            foreach ($lineas as $linea) {
                ++$datos['actual'];
                // Guardar solo asignaciones nuevas
                $empleado = $empleadoRepository->findOneByDocumento($linea['DNI USUARIO']);
                $evaluador = $empleadoRepository->findOneByDocumento($linea['DNI VALIDADOR']);
                if ($empleado instanceof Empleado && $evaluador instanceof Empleado &&
                    0 === $this->evaluaRepository->count(
                        ['empleado' => $empleado, 'evaluador' => $evaluador, 'cuestionario' => $cuestionario]
                    )) {
                    $evaluacion = new Evalua();
                    $evaluacion
                        ->setCuestionario($cuestionario)
                        ->setEmpleado($empleado)
                        ->setEvaluador($evaluador)
                        ->setTipoEvaluador($tipo)
                        ->setOrigen($fichero)
                    ;
                    $this->evaluaRepository->save($evaluacion, true);
                    ++$datos['nuevos'];
                } else {
                    ++$datos['descartados'];
                }
                $datos['duracion'] = microtime(true) - $inicio;
                try {
                    $this->redis->set($claveRedis, json_encode($datos));
                } catch (RedisException) {
                }
            }

            $datos['finalizado'] = true;
            try {
                $this->redis->set($claveRedis, json_encode($datos));
            } catch (RedisException) {
            }

            $this->lock->release();
            if ($datos['nuevos'] > 0) {
                $this->generator->logAndFlash('info', 'Nuevos evaluadores cargados', $datos);
            } else {
                $this->addFlash('warning', 'No se han cargado evaluadores nuevos.');
            }

            return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', [
                'id' => $cuestionario->getId(),
                'tipo' => $tipo,
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('desempenyo/admin/evaluador/volcado.html.twig', [
            'form' => $form->createView(),
            'cuestionario' => $cuestionario,
            'tipo' => $tipo,
            'campos' => $campos,
        ]);
    }

    /** Rechazar la evaluación de un empleado. */
    #[Route(
        path: '/admin/cuestionario/{cuestionario}/evaluador/rechaza/{empleado}',
        name: 'admin_evaluador_rechaza',
        methods: ['GET']
    )]
    public function rechazaAdmin(
        Cuestionario $cuestionario,
        Empleado     $empleado,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        // Comprobar si el empleado puede autoevaluarse
        $evalua = $this->evaluaRepository->findOneBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo_evaluador' => Evalua::AUTOEVALUACION,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->addFlash('warning', 'El empleado no existe o no es evaluable.');

            return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', ['id' => $cuestionario->getId()]);
        }

        $evalua
            ->setTipoEvaluador(Evalua::NO_EVALUACION)
            ->setRechazado(new DateTimeImmutable())
            ->setRechazoTexto(null)
            ->setRegistrado(null)
        ;
        $this->evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'Empleado marcado como no evaluable', [
            'cuestionario' => $cuestionario->getCodigo(),
            'empleado' => $empleado->getDocIdentidad(),
        ]);

        return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', ['id' => $cuestionario->getId()]);
    }

    /** Empleado solicita no ser evaluado. */
    #[Route(
        path: '/formulario/{codigo}/rechaza',
        name: 'formulario_rechaza',
        requirements: ['codigo' => '[a-z0-9-]+'],
        methods: ['GET']
    )]
    public function rechazaEmpleado(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        // Buscar usuario actual como empleado
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $empleado = $empleadoRepository->findOneByUsuario($usuario);
        $ruta = u($request->getRequestUri())->beforeLast('/')->toString();
        $cuestionario = $cuestionarioRepository->findOneBy(['url' => $ruta]);
        // Comprobar si el empleado puede autoevaluarse
        $evalua = $this->evaluaRepository->findOneBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo_evaluador' => Evalua::AUTOEVALUACION,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->addFlash('warning', 'El empleado no existe o no es evaluable.');

            return $this->redirectToRoute($this->rutaBase);
        }

        $evalua
            ->setTipoEvaluador(Evalua::NO_EVALUACION)
            ->setHabilita(false)
            ->setRechazado(new DateTimeImmutable())
            ->setRechazoTexto(null)
            ->setRegistrado(null)
        ;
        $this->evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'Empleado solicita no ser evaluable', [
            'cuestionario' => $cuestionario->getCodigo(),
            'empleado' => $empleado->getDocIdentidad(),
        ]);

        return $this->redirectToRoute($this->rutaBase);
    }

    /** Notifica que una solicitud de rechazo ha sido entregada en el Registro General. */
    #[Route(
        path: '/admin/cuestionario/{cuestionario}/evaluador/registra/{empleado}',
        name: 'admin_evaluador_registra',
        methods: ['POST']
    )]
    public function registra(
        Request      $request,
        Cuestionario $cuestionario,
        Empleado     $empleado,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        // Comprobar si el empleado ha solicitado no ser evaluado
        $evalua = $this->evaluaRepository->findOneBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo_evaluador' => Evalua::NO_EVALUACION,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->addFlash('warning', 'El empleado no existe o no ha rechazado ser evaluado.');

            return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', [
                'id' => $cuestionario->getId(),
            ]);
        }

        $form = $this->createForm(RegistroType::class, $evalua, [
            'action' => $request->getPathInfo(),
            'method' => 'POST',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->evaluaRepository->save($evalua, true);
            $this->generator->logAndFlash('info', 'Registro general validado', [
                'cuestionario' => $cuestionario->getCodigo(),
                'empleado' => $empleado->getDocIdentidad(),
            ]);

            return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', [
                'id' => $cuestionario->getId(),
                'tipo' => Evalua::NO_EVALUACION,
            ]);
        }

        return $this->render('desempenyo/admin/evaluador/_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /** Recupera la evaluación de un empleado que la había rechazado previamente. */
    #[Route(
        path: '/admin/cuestionario/{cuestionario}/evaluador/recupera/{empleado?}',
        name: 'admin_evaluador_recupera',
        methods: ['GET']
    )]
    public function recuperaAdmin(
        Cuestionario $cuestionario,
        ?Empleado    $empleado = null,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        // Comprobar si el empleado ha rechazado la evaluación
        $evalua = $this->evaluaRepository->findOneBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo_evaluador' => Evalua::NO_EVALUACION,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->addFlash('warning', 'El empleado no existe o no había solicitado rechazar evaluación.');

            return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', [
                'id' => $cuestionario->getId(),
            ]);
        }

        $evalua
            ->setTipoEvaluador()
            ->setRechazado(null)
            ->setRechazoTexto(null)
            ->setRegistrado(null)
        ;
        $this->evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'Empleado vuelve a ser evaluable', [
            'cuestionario' => $cuestionario->getCodigo(),
            'empleado' => $empleado->getDocIdentidad(),
        ]);

        return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', [
            'id' => $cuestionario->getId(),
        ]);
    }

    /** Empleado solicita recuperar la posibilidad de ser evaluado. */
    #[Route(
        path: '/formulario/{codigo}/recupera',
        name: 'formulario_recupera',
        requirements: ['codigo' => '[a-z0-9-]+'],
        methods: ['GET']
    )]
    public function recuperaEmpleado(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        // Buscar usuario actual como empleado
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $empleado = $empleadoRepository->findOneByUsuario($usuario);
        $ruta = u($request->getRequestUri())->beforeLast('/')->toString();
        $cuestionario = $cuestionarioRepository->findOneBy(['url' => $ruta]);
        // Comprobar si el empleado ha rechazado la evaluación
        $evalua = $this->evaluaRepository->findOneBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo_evaluador' => Evalua::NO_EVALUACION,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->addFlash('warning', 'El empleado no existe o no había solicitado rechazar evaluación.');

            return $this->redirectToRoute($this->rutaBase);
        }

        $evalua
            ->setTipoEvaluador()
            ->setRechazado(null)
            ->setRechazoTexto(null)
            ->setRegistrado(null)
        ;
        $this->evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'Empleado solicita ser evaluable', [
            'cuestionario' => $cuestionario->getCodigo(),
            'empleado' => $empleado->getDocIdentidad(),
        ]);

        return $this->redirectToRoute($this->rutaBase);
    }


    /** Corregir la puntuación de una evaluación. */
    #[Route(
        path: '/admin/cuestionario/{cuestionario}/evaluador/corrige/{evalua}',
        name: 'admin_evaluador_corrige',
        methods: ['GET']
    )]
    public function corrige(
        Request          $request,
        EvaluaRepository $evaluaRepository,
        Cuestionario     $cuestionario,
        Evalua           $evalua,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        if ($cuestionario !== $evalua->getCuestionario()) {
            $this->addFlash('warning', 'La evaluación a corregir no corresponde con el cuestionario.');

            return $this->redirectToRoute($this->rutaBase);
        }

        $form = $this->createForm(CorreccionType::class, $evalua);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $evalua
                ->setCorrector($usuario)
                ->setCorregido(new DateTimeImmutable())
            ;
            $evaluaRepository->save($evalua, true);
            $this->generator->logAndFlash('info', 'Evaluación corregida', [
                'id' => $evalua->getId(),
                'cuestionario' => $cuestionario->getCodigo(),
                'empleado' => $evalua->getEmpleado()?->getDocIdentidad(),
                'evaluador' => $evalua->getEvaluador()?->getDocIdentidad(),
                'tipo' => $evalua->getTipoEvaluador(),
            ]);

            return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', [
                'id' => $cuestionario->getId(),
            ]);
        }

        // TODO mostrar formulario
        return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', [
            'id' => $cuestionario->getId(),
        ]);
    }

    /** Empleado indica que puede ser evaluado en su puesto actual. */
    #[Route(
        path: '/formulario/{codigo}/habilita',
        name: 'formulario_habilita',
        requirements: ['codigo' => '[a-z0-9-]+'],
        methods: ['GET']
    )]
    public function habilita(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
    ): Response
    {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $empleado = $empleadoRepository->findOneByUsuario($usuario);
        $ruta = u($request->getRequestUri())->beforeLast('/')->toString();
        $cuestionario = $cuestionarioRepository->findOneBy(['url' => $ruta]);
        $evalua = $this->evaluaRepository->findOneBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo_evaluador' => Evalua::AUTOEVALUACION,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->addFlash('warning', 'El empleado no existe o no es evaluable.');

            return $this->redirectToRoute($this->rutaBase);
        } else if ($this->evaluaRepository->findOneBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo_evaluador' => Evalua::NO_EVALUACION,
        ]) instanceof Evalua) {
            $this->addFlash('warning', 'El empleado ha solicitado no ser evaluado.');

            return $this->redirectToRoute($this->rutaBase);
        }

        $evalua->setHabilita();
        $this->evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'Empleado habilita su evaluación', [
            'cuestionario' => $cuestionario->getCodigo(),
            'empleado' => $empleado->getDocIdentidad(),
        ]);

        return $this->redirectToRoute($this->rutaBase);
    }

    /** Asigna un evaluador de un tipo determinado a un empleado. */
    #[Route(
        path: '/admin/cuestionario/{cuestionario}/evaluador/asigna/{empleado}/{tipo?}',
        name: 'admin_evaluador_asigna',
        methods: ['POST']
    )]
    public function asigna(
        Request            $request,
        EmpleadoRepository $empleadoRepository,
        EvaluaRepository   $evaluaRepository,
        Cuestionario       $cuestionario,
        Empleado           $empleado,
        ?int               $tipo = Evalua::EVALUA_RESPONSABLE
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        $evalua = $evaluaRepository->findOneBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo_evaluador' => $tipo,
        ]);
        if (!$evalua instanceof Evalua) {
            $evalua = new Evalua();
            $evalua
                ->setCuestionario($cuestionario)
                ->setEmpleado($empleado)
                ->setTipoEvaluador($tipo)
            ;
        }

        $form = $this->createForm(EvaluadorType::class, $evalua, [
            'action' => $request->getPathInfo(),
            'method' => 'POST',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $evalua->setOrigen(EVALUA::MANUAL);
            $evaluaRepository->save($evalua, true);
            $this->generator->logAndFlash('info', 'Evaluador asignado', [
                'id' => $evalua->getId(),
                'cuestionario' => $cuestionario->getCodigo(),
                'empleado' => $evalua->getEmpleado()?->getDocIdentidad(),
                'evaluador' => $evalua->getEvaluador()?->getDocIdentidad(),
                'tipo' => $evalua->getTipoEvaluador(),
            ]);

            return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', [
                'id' => $cuestionario->getId(),
                'tipo' => $evalua->getTipoEvaluador(),
            ]);
        }

        return $this->render('desempenyo/admin/evaluador/_form_evaluador.html.twig', [
            'form' => $form->createView(),
            'empleados' => $empleadoRepository->findCesados(false),
        ]);
    }
}

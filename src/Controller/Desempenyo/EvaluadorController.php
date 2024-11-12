<?php

declare(strict_types=1);

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Desempenyo\Evalua;
use App\Entity\Plantilla\Empleado;
use App\Entity\Sistema\Origen;
use App\Entity\Sistema\Usuario;
use App\Form\Util\VolcadoType;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Desempenyo\EvaluaRepository;
use App\Repository\Plantilla\EmpleadoRepository;
use App\Repository\Sistema\OrigenRepository;
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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use function Symfony\Component\String\u;

#[Route(path: '/intranet/desempenyo', name: 'intranet_desempenyo_')]
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
        $this->rutaBase = $this->actual->getAplicacion()?->getRuta() ?? 'intranet_inicio';
        $this->ttl = 60;
    }

    #[Route(
        path: '/admin/cuestionario/{id}/evaluador/',
        name: 'admin_evaluador_index',
        defaults: ['titulo' => 'Evaluadores de Cuestionario de Competencias'],
        methods: ['GET']
    )]
    public function index(Request $request, Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $tipo = $request->query->getInt('tipo', Evalua::AUTOEVALUACION);
        $evaluaciones = $this->evaluaRepository->findByEvaluacion([
            'cuestionario' => $cuestionario,
            'tipo' => $tipo,
        ]);
        $claveRedis = sprintf('evaluacion-%d', $tipo);
        $ultimo = null;

        try {
            /** @var bool[]|array<string[]> $datos */
            $datos = json_decode((string) $this->redis->get($claveRedis), true);
            if (true === $datos['finalizado']) {
                $ultimo = new DateTimeImmutable(
                    $datos['inicio']['date'],
                    new DateTimeZone($datos['inicio']['timezone'] ?? 'UTC')
                );
            }
        } catch (Exception) {
        }

        return $this->render('intranet/desempenyo/admin/evaluador/index.html.twig', [
            'cuestionario' => $cuestionario,
            'evaluaciones' => $evaluaciones,
            'tipo' => $tipo,
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
        Request          $request,
        EvaluaRepository $evaluaRepository,
        Cuestionario     $cuestionario,
        Evalua           $evalua
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        $token = sprintf('delete%d-%d', $cuestionario->getId() ?? 0, $evalua->getId() ?? 0);
        if ($cuestionario->getId() !== $evalua->getCuestionario()?->getId()) {
            $this->addFlash('warning', 'La evaluación no corresponde a este cuestionario.');
        } elseif ($this->isCsrfTokenValid($token, $request->request->getString('_token'))) {
            $evaluaRepository->remove($evalua, true);
            $this->generator->logAndFlash('info', 'Evaluación eliminada correctamente', [
                'id' => $evalua->getId(),
                'codigo' => $cuestionario->getCodigo(),
                'empleado' => $evalua->getEmpleado()?->getPersona(),
                'evaluador' => $evalua->getEvaluador()?->getPersona(),
                'tipo_evaluador' => $evalua->getTipoEvaluador(),
            ]);
        }

        return $this->redirectToRoute('intranet_desempenyo_admin_evaluador_index', [
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
        OrigenRepository   $origenRepository,
        Cuestionario       $cuestionario,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        $claveRedis = sprintf('evaluacion-%d', Evalua::AUTOEVALUACION);
        if ($cuestionario->getAplicacion() !== $this->actual->getAplicacion()) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (null === $this->lock->acquire($this->ttl)) {
            $this->addFlash('warning', 'Recurso bloqueado por otra operación de carga.');

            return $this->redirectToRoute($request->attributes->getString('_route'));
        }

        $inicio = microtime(true);
        $externo = $origenRepository->findOneBy(['nombre' => Origen::EXTERNO]);
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

        return $this->redirectToRoute(
            sprintf(
                '%s_%s_evaluador_index',
                $this->actual->getAplicacion()?->getRuta() ?? '',
                $this->actual->getRol()?->getRuta() ?? ''
            ),
            [
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
        OrigenRepository   $origenRepository,
        Cuestionario       $cuestionario,
        string             $tipo,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        $tipo = match ($tipo) {
            'evaluador' => Evalua::EVALUA_RESPONSABLE,
            'otro' => Evalua::EVALUA_OTRO,
            default => null,
        };
        if ($cuestionario->getAplicacion() !== $this->actual->getAplicacion()) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (null === $tipo) {
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
            $fichero = $origenRepository->findOneBy(['nombre' => Origen::FICHERO]);
            /** @var string[] $linea */
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
                $this->generator->logAndFlash('warning', 'No se han cargado evaluadores nuevos', [
                    'descartados' => $datos['descartados'],
                    'duracion' => $datos['duracion'],
                ]);
            }

            return $this->redirectToRoute('intranet_desempenyo_admin_evaluador_index', [
                'id' => $cuestionario->getId(),
                'tipo' => $tipo,
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('intranet/desempenyo/admin/evaluador/volcado.html.twig', [
            'form' => $form->createView(),
            'cuestionario' => $cuestionario,
            'tipo' => $tipo,
            'campos' => $campos,
        ]);
    }

    /** Volcar datos que relacionan empleado con su evaluador desde la API REST del servidor Temponet mediante comando. */
    #[Route(
        path: '/admin/cuestionario/{id}/evaluador/volcado',
        name: 'admin_evaluador_volcado',
        defaults: ['titulo' => 'Volcado de Validaciones desde Temponet'],
        methods: ['GET', 'POST']
    )]
    public function volcadoEvaluacion(
        KernelInterface $kernel,
        Request         $request,
        Cuestionario    $cuestionario,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        if ($cuestionario->getAplicacion() !== $this->actual->getAplicacion()) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (null === $this->lock->acquire($this->ttl)) {
            $this->addFlash('warning', 'Recurso bloqueado por otra operación de carga.');

            return $this->redirectToRoute($request->attributes->getString('_route'));
        }

        $app = new Application($kernel);
        $app->setAutoExit(false);
        $input = new ArrayInput([
            'command' => 'sirhus:rest:validaciones',
            'cuestionario' => $cuestionario->getCodigo(),
        ]);
        $output = new BufferedOutput();
        try {
            if (0 !== $app->run($input, $output)) {
                throw new Exception();
            }
        } catch (Exception) {
            $this->lock->release();
            $this->addFlash('warning', 'Error al volcar datos desde Temponet.');

            return $this->redirectToRoute($this->rutaBase);
        }

        $this->lock->release();
        $this->generator->logAndFlash('info', 'Volcado de validaciones desde Temponet', [
            'cuestionario' => $cuestionario->getCodigo(),
            'salida' => $output->fetch(),
        ]);

        return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', [
            'id' => $cuestionario->getId(),
            'tipo' => Evalua::EVALUA_RESPONSABLE,
        ], Response::HTTP_SEE_OTHER);
    }

    /** Rechazar la evaluación de un empleado. */
    #[Route(
        path: '/admin/cuestionario/{cuestionario}/evaluador/rechaza/{empleado}',
        name: 'admin_evaluador_rechaza',
        methods: ['GET']
    )]
    public function rechazaAdmin(
        EvaluaRepository $evaluaRepository,
        Cuestionario     $cuestionario,
        Empleado         $empleado,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        // Comprobar si el empleado puede autoevaluarse
        $evalua = $evaluaRepository->findOneBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo_evaluador' => Evalua::AUTOEVALUACION,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->generator->logAndFlash('warning', 'El empleado no existe o no es evaluable', [
                'cuestionario' => $cuestionario->getCodigo(),
                'usuario' => $empleado->getPersona()?->getUsuario()?->getUvus() ?? $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', ['id' => $cuestionario->getId()]);
        }

        $evalua
            ->setTipoEvaluador(Evalua::NO_EVALUACION)
            ->setFechaRechazo(new DateTimeImmutable())
        ;
        $evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'El empleado ha sido marcado como no evaluable', [
            'cuestionario' => $cuestionario->getCodigo(),
            'empleado' => $empleado->getPersona()?->getUsuario()?->getUvus(),
        ]);

        return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', ['id' => $cuestionario->getId()]);
    }

    /** Empleado solicita no ser evaluado. */
    #[Route(
        path: '/formulario/{codigo}/rechaza',
        name: 'formulario_rechaza',
        methods: ['GET']
    )]
    public function rechazaEmpleado(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
        EvaluaRepository       $evaluaRepository,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        // Buscar usuario actual como empleado
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $empleado = $empleadoRepository->findOneByUsuario($usuario);
        $ruta = u($request->getRequestUri())->beforeLast('/')->toString();
        $cuestionario = $cuestionarioRepository->findOneBy(['url' => $ruta]);
        // Comprobar si el empleado puede autoevaluarse
        $evalua = $evaluaRepository->findOneBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo_evaluador' => Evalua::AUTOEVALUACION,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->generator->logAndFlash('warning', 'El empleado no existe o no es evaluable', [
                'cuestionario' => $cuestionario?->getCodigo(),
                'usuario' => $empleado?->getPersona()?->getUsuario()?->getUvus() ?? $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->redirectToRoute($this->rutaBase);
        }

        $evalua
            ->setTipoEvaluador(Evalua::NO_EVALUACION)
            ->setFechaRechazo(new DateTimeImmutable())
        ;
        $evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'El empleado ha solicitado no ser evaluable', [
            'cuestionario' => $cuestionario?->getCodigo(),
            'empleado' => $empleado?->getPersona()?->getUsuario()?->getUvus(),
        ]);

        return $this->redirectToRoute($this->rutaBase);
    }

    /** Recupera la evaluación de un empleado que la había rechazado previamente. */
    #[Route(
        path: '/admin/cuestionario/{cuestionario}/evaluador/recupera/{empleado?}',
        name: 'admin_evaluador_recupera',
        methods: ['GET']
    )]
    public function recuperaAdmin(
        EvaluaRepository $evaluaRepository,
        Cuestionario     $cuestionario,
        ?Empleado        $empleado = null,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        // Comprobar si el empleado ha rechazado la evaluación
        $evalua = $evaluaRepository->findOneBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo_evaluador' => Evalua::NO_EVALUACION,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->generator->logAndFlash(
                'warning',
                'El empleado no existe o no había solicitado rechazar evaluación',
                [
                    'cuestionario' => $cuestionario->getCodigo(),
                    'usuario' => $empleado?->getPersona()?->getUsuario()?->getUvus() ?? $this->getUser()?->getUserIdentifier(),
                ]
            );

            return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', ['id' => $cuestionario->getId()]);
        }

        $evalua
            ->setTipoEvaluador()
            ->setFechaRechazo(null)
        ;
        $evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'El empleado vuelve a ser evaluable', [
            'cuestionario' => $cuestionario->getCodigo(),
            'empleado' => $empleado?->getPersona()?->getUsuario()?->getUvus(),
        ]);

        return $this->redirectToRoute($this->rutaBase . '_admin_evaluador_index', ['id' => $cuestionario->getId()]);
    }

    /** Empleado solicita recuperar la posibilidad de ser evaluado. */
    #[Route(
        path: '/formulario/{codigo}/recupera',
        name: 'formulario_recupera',
        methods: ['GET']
    )]
    public function recuperaEmpleado(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
        EvaluaRepository       $evaluaRepository,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        // Buscar usuario actual como empleado
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $empleado = $empleadoRepository->findOneByUsuario($usuario);
        $ruta = u($request->getRequestUri())->beforeLast('/')->toString();
        $cuestionario = $cuestionarioRepository->findOneBy(['url' => $ruta]);
        // Comprobar si el empleado ha rechazado la evaluación
        $evalua = $evaluaRepository->findOneBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo_evaluador' => Evalua::NO_EVALUACION,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->generator->logAndFlash(
                'warning',
                'El empleado no existe o no había solicitado rechazar evaluación',
                [
                    'cuestionario' => $cuestionario?->getCodigo(),
                    'usuario' => $empleado?->getPersona()?->getUsuario()?->getUvus() ?? $this->getUser(
                    )?->getUserIdentifier(),
                ]
            );

            return $this->redirectToRoute($this->rutaBase);
        }

        $evalua
            ->setTipoEvaluador()
            ->setFechaRechazo(null)
        ;
        $evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'El empleado vuelve a ser evaluable', [
            'cuestionario' => $cuestionario?->getCodigo(),
            'empleado' => $empleado?->getPersona()?->getUsuario()?->getUvus(),
        ]);

        return $this->redirectToRoute($this->rutaBase);
    }
}

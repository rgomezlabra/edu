<?php

declare(strict_types=1);

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Desempenyo\Evalua;
use App\Entity\Plantilla\Empleado;
use App\Entity\Sistema\Usuario;
use App\Form\Util\VolcadoType;
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
use RedisException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function Symfony\Component\String\u;

#[Route(path: '/intranet/desempenyo', name: 'intranet_desempenyo_')]
class EvaluadorController extends AbstractController
{
    private object $redis;

    public function __construct(
        private readonly MessageGenerator $generator,
        private readonly SirhusLock       $lock,
        private readonly RutaActual       $actual,
        private readonly EvaluaRepository $evaluaRepository,
    ) {
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
        /** @var int $tipo */
        $tipo = match ($request->query->getString('tipo')) {
            'auto', '' => $this->evaluaRepository::AUTOEVALUACION,
            'valida' => $this->evaluaRepository::EVALUACION,
            'noevalua' => $this->evaluaRepository::NO_EVALUACION,
            default => 0,
        };
        $evaluaciones = $this->evaluaRepository->findByEvaluacion([
            'cuestionario' => $cuestionario,
            'tipo' => $tipo,
        ]);
        $this->redis = RedisAdapter::createConnection($request->server->getString('REDIS_URL'));
        $ultimo = null;

        try {
            /** @var array<array-key, mixed> $datos */
            $datos = json_decode((string) $this->redis->get('autoevaluacion'), true);
            if (true === $datos['finalizado']) {
                $ultimo = new DateTimeImmutable(
                    (string) $datos['inicio']['date'],
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
        Cuestionario       $cuestionario,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        $ttl = 300; // Periodo de validez de 300 s
        if ($cuestionario->getAplicacion() !== $this->actual->getAplicacion()) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return $this->redirectToRoute($this->actual->getAplicacion()?->getRuta() ?? 'intranet_inicio');
        } elseif (null === $this->lock->acquire($ttl)) {
            $this->addFlash('warning', 'Recurso bloqueado por otra operación de carga.');

            return $this->redirectToRoute($request->attributes->getString('_route'));
        }

        $inicio = microtime(true);
        $this->redis = RedisAdapter::createConnection($request->server->getString('REDIS_URL'));
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
                'evaluador' => $empleado,
                'cuestionario' => $cuestionario,
                ]) instanceof Evalua) {
                $evalua = new Evalua();
                $evalua
                    ->setEmpleado($empleado)
                    ->setEvaluador($empleado)
                    ->setCuestionario($cuestionario)
                ;
                $this->evaluaRepository->save($evalua);
                ++$datos['nuevos'];
            }

            $datos['duracion'] = microtime(true) - $inicio;
            try {
                $this->redis->set('autoevaluacion', json_encode($datos));
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
            $this->redis->set('autoevaluacion', json_encode($datos));
        } catch (RedisException) {
        }

        $this->lock->release();

        return $this->redirectToRoute(
            sprintf(
                '%s_%s_evaluador_index',
                $this->actual->getAplicacion()?->getRuta() ?? '',
                $this->actual->getRol()?->getRuta() ?? ''
            ),
            ['id' => $cuestionario->getId()]
        );
    }

    /** Cargar datos que relacionan empleado con su evaluador para el cuestionario indicado. */
    #[Route(
        path: '/admin/cuestionario/{id}/evaluador/carga',
        name: 'admin_evaluador_carga',
        defaults: ['titulo' => 'Cargar Evaluadores de Empleados'],
        methods: ['GET', 'POST']
    )]
    public function cargarEvaluacion(
        Request            $request,
        EmpleadoRepository $empleadoRepository,
        Cuestionario       $cuestionario,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        if ($cuestionario->getAplicacion() !== $this->actual->getAplicacion()) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return $this->redirectToRoute($this->actual->getAplicacion()?->getRuta() ?? 'intranet_inicio');
        }

        $campos = [
            'DNI USUARIO',      // Documento empleado
            'DNI VALIDADOR',    // Documento evaluador
        ];
        $form = $this->createForm(VolcadoType::class, ['maxSize' => '256k']);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            set_time_limit(60); // La carga completa puede tardar más de los 30 s. por defecto
            $inicio = microtime(true);
            $this->redis = RedisAdapter::createConnection($request->server->getString('REDIS_URL'));
            $lineas = [];
            $nuevos = 0;
            $descartados = 0;
            // Cargar fichero CSV
            /** @var UploadedFile $fichero */
            $fichero = $form->get('fichero_csv')->getData();
            $csv = new Csv();
            $csv->abrir($fichero);
            if (!$csv->comprobarCabeceras($campos)) {
                $this->generator->logAndFlash('error', 'No se puede abrir el fichero de datos o no es correcto', [
                    'fichero' => $fichero->getClientOriginalName(),
                ]);

                return $this->redirectToRoute($request->attributes->getString('_route'), ['id' => $cuestionario->getId()]);
            }

            while (($datos = $csv->leer($campos)) !== null) {
                $lineas[] = $datos;
            }

            $csv->cerrar();

            // Grabar datos
            /** @var string[] $linea */
            foreach ($lineas as $linea) {
                $empleado = $empleadoRepository->findOneByDocumento($linea['DNI USUARIO']);
                $evaluador = $empleadoRepository->findOneByDocumento($linea['DNI VALIDADOR']);
                if ($empleado instanceof Empleado && $evaluador instanceof Empleado) {
                    if (0 === $this->evaluaRepository->count(['empleado' => $empleado, 'evaluador' => $evaluador, 'cuestionario' => $cuestionario])) {
                        $evaluacion = new Evalua();
                        $evaluacion
                            ->setCuestionario($cuestionario)
                            ->setEmpleado($empleado)
                            ->setEvaluador($evaluador)
                        ;
                        $this->evaluaRepository->save($evaluacion, true);
                        ++$nuevos;
                    } else {
                        ++$descartados;
                    }
                } else {
                    ++$descartados;
                }
            }

            if ($nuevos > 0) {
                $this->generator->logAndFlash('info', 'Nuevos evaluadores cargados', [
                    'nuevos' => $nuevos,
                    'descartados' => $descartados,
                    'duracion' => microtime(true) - $inicio,
                ]);
            } else {
                $this->generator->logAndFlash('warning', 'No se han cargado evaluadores nuevos', [
                    'descartados' => $descartados,
                    'duracion' => microtime(true) - $inicio,
                ]);
            }

            return $this->redirectToRoute('intranet_desempenyo_admin_evaluador_index', [
                'id' => $cuestionario->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('intranet/desempenyo/admin/evaluador/volcado.html.twig', [
            'form' => $form->createView(),
            'cuestionario' => $cuestionario,
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
        EvaluaRepository $evaluaRepository,
        Cuestionario     $cuestionario,
        Empleado         $empleado,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        // Comprobar si el empleado puede autoevaluarse
        $evalua = $evaluaRepository->findOneBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'evaluador' => $empleado,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->generator->logAndFlash('warning', 'El empleado no existe o no es evaluable', [
                'cuestionario' => $cuestionario->getCodigo(),
                'usuario' => $empleado->getPersona()->getUsuario()->getUvus() ?? $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->redirectToRoute('intranet_desempenyo_admin_evaluador_index', ['id' => $cuestionario->getId()]);
        }

        $evalua
            ->setEvaluador(null)
            ->setFechaRechazo(new DateTimeImmutable())
        ;
        $evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'El empleado ha sido marcado como no evaluable', [
            'cuestionario' => $cuestionario->getCodigo(),
            'empleado' => $empleado->getPersona()?->getUsuario()->getUvus(),
        ]);

        return $this->redirectToRoute('intranet_desempenyo_admin_evaluador_index', ['id' => $cuestionario->getId()]);
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
            'evaluador' => $empleado,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->generator->logAndFlash('warning', 'El empleado no existe o no es evaluable', [
                'cuestionario' => $cuestionario->getCodigo(),
                'usuario' => $empleado?->getPersona()->getUsuario()->getUvus() ?? $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->redirectToRoute('intranet_desempenyo');
        }

        $evalua
            ->setEvaluador(null)
            ->setFechaRechazo(new DateTimeImmutable())
        ;
        $evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'El empleado ha solicitado no ser evaluable', [
            'cuestionario' => $cuestionario?->getCodigo(),
            'empleado' => $empleado?->getPersona()->getUsuario()->getUvus(),
        ]);

        return $this->redirectToRoute('intranet_desempenyo');
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
            'evaluador' => null,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->generator->logAndFlash('warning', 'El empleado no existe o no había solicitado rechazar evaluación', [
                'cuestionario' => $cuestionario->getCodigo(),
                'usuario' => $empleado?->getPersona()->getUsuario()->getUvus() ?? $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->redirectToRoute('intranet_desempenyo_admin_evaluador_index', ['id' => $cuestionario->getId()]);
        }

        $evalua
            ->setEvaluador($empleado)
            ->setFechaRechazo(null)
        ;
        $evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'El empleado vuelve a ser evaluable', [
            'cuestionario' => $cuestionario->getCodigo(),
            'empleado' => $empleado?->getPersona()->getUsuario()->getUvus(),
        ]);

        return $this->redirectToRoute('intranet_desempenyo_admin_evaluador_index', ['id' => $cuestionario->getId()]);
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
            'evaluador' => null,
        ]);
        if (!$evalua instanceof Evalua) {
            $this->generator->logAndFlash('warning', 'El empleado no existe o no había solicitado rechazar evaluación', [
                'cuestionario' => $cuestionario?->getCodigo(),
                'usuario' => $empleado?->getPersona()->getUsuario()->getUvus() ?? $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->redirectToRoute('intranet_desempenyo');
        }

        $evalua
            ->setEvaluador($empleado)
            ->setFechaRechazo(null)
        ;
        $evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'El empleado vuelve a ser evaluable', [
            'cuestionario' => $cuestionario?->getCodigo(),
            'empleado' => $empleado?->getPersona()->getUsuario()->getUvus(),
        ]);

        return $this->redirectToRoute('intranet_desempenyo');
    }
}

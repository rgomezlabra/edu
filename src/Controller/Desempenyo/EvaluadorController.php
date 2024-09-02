<?php

declare(strict_types=1);

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Desempenyo\Evalua;
use App\Entity\Plantilla\Empleado;
use App\Entity\Sistema\Usuario;
use App\Form\Util\VolcadoType;
use App\Repository\Desempenyo\EvaluaRepository;
use App\Repository\Plantilla\EmpleadoRepository;
use App\Repository\Sistema\PersonaRepository;
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

#[Route(path: '/intranet/desempenyo/admin/cuestionario', name: 'intranet_desempenyo_admin_cuestionario_')]
class EvaluadorController extends AbstractController
{
    private object $redis;

    public function __construct(
        private readonly MessageGenerator $generator,
        private readonly SirhusLock $lock,
        private readonly RutaActual $actual,
        private readonly EvaluaRepository $evaluaRepository,
    ) {
    }

    #[Route(
        path: '/{id}/evaluador/',
        name: 'evaluador_index',
        defaults: ['titulo' => 'Evaluadores de Cuestionario de Competencias'],
        methods: ['GET']
    )]
    public function index(Request $request, Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        //$evaluaciones = $this->evaluaRepository->findByEvaluacion($cuestionario, EvaluaRepository::AUTOEVALUACION);
        $evaluaciones = $this->evaluaRepository->findAll();
        $this->redis = RedisAdapter::createConnection((string) $request->server->get('REDIS_URL'));
        $ultimo = null;

        try {
            /** @var array<array-key, mixed> $datos */
            $datos = json_decode((string) $this->redis->get('autoevaluacion'), true);
            if (true === $datos['finalizado']) {
                $ultimo = new DateTimeImmutable(
                    (string) $datos['inicio']['date'],
                    new DateTimeZone((string) $datos['inicio']['timezone'])
                );
            }
        } catch (Exception) {
        }

        return $this->render('intranet/desempenyo/admin/evaluador/index.html.twig', [
            'cuestionario' => $cuestionario,
            'evaluaciones' => $evaluaciones,
            'volcado_empleados' => $ultimo,
        ]);
    }

    /** Cargar empleados activos para autoevaluación que no hayan solicitado exclusión en un cuestionario. */
    #[Route(
        path: '/{id}/evaluador/auto',
        name: 'evaluador_auto',
        defaults: ['titulo' => 'Cargar Empleados para Autoevaluación'],
        methods: ['GET']
    )]
    public function cargarAutoevaluacion(
        Request            $request,
        EmpleadoRepository $empleadoRepository,
        Cuestionario       $cuestionario,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        if ($cuestionario->getAplicacion() !== $this->actual->getAplicacion()) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return $this->redirectToRoute($this->actual->getAplicacion()?->getRuta() ?? 'intranet_inicio');
        } elseif (null === $this->lock->acquire()) {
            $this->addFlash('warning', 'Recurso bloqueado por otra operación de carga.');

            return $this->redirectToRoute((string) $request->attributes->get('_route'));
        }

        $inicio = microtime(true);
        $this->redis = RedisAdapter::createConnection((string) $request->server->get('REDIS_URL'));
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
            '%s_%s_cuestionario_evaluador_index',
                $this->actual->getAplicacion()?->getRuta() ?? '',
                $this->actual->getRol()?->getRuta() ?? ''
            ), [
                'id' => $cuestionario->getId(),
            ]
        );
    }

    /** Cargar datos que relacionan empleado con su evaluador para el cuestionario indicado. */
    #[Route(
        path: '/{id}/evaluador/carga',
        name: 'evaluador_carga',
        defaults: ['titulo' => 'Cargar Evaluadores de Empleados'],
        methods: ['GET', 'POST']
    )]
    public function cargarEvaluacion(
        Request            $request,
        EmpleadoRepository $empleadoRepository,
        PersonaRepository  $personaRepository,
        Cuestionario       $cuestionario,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        if ($cuestionario->getAplicacion() !== $this->actual->getAplicacion()) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return $this->redirectToRoute($this->actual->getAplicacion()?->getRuta() ?? 'intranet_inicio');
        }

        $form = $this->createForm(VolcadoType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $inicio = microtime(true);
            $this->redis = RedisAdapter::createConnection((string) $request->server->get('REDIS_URL'));
            $campos = [
                'EMPLEADO',     // Documento empleado
                'EVALUADOR',    // Documento evaluador
            ];
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

                return $this->redirectToRoute((string)$request->attributes->get('_route'));
            }

            while (($datos = $csv->leer($campos)) !== null) {
                $lineas[] = $datos;
            }

            $csv->cerrar();

            // Grabar datos
            /** @var string[] $linea */
            foreach ($lineas as $linea) {
                $persona = $personaRepository->findOneBy(['doc_identidad' => $linea['EMPLEADO']]);
                $empleado = $empleadoRepository->findOneBy(['persona' => $persona]);
                $persona = $personaRepository->findOneBy(['doc_identidad' => $linea['VALIDADOR']]);
                $validador = $empleadoRepository->findOneBy(['persona' => $persona]);
                if ($empleado instanceof Empleado && $validador instanceof Empleado) {
                    if (0 === $this->evaluaRepository->count(['empleado' => $empleado, 'validador' => $validador, 'cuestionario' => $cuestionario])) {
                        $evaluacion = new Evalua();
                        $evaluacion
                            ->setCuestionario($cuestionario)
                            ->setEmpleado($empleado)
                            ->setEvaluador($validador)
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

            return $this->redirectToRoute('intranet_desempenyo_admin_cuestionario_evaluador_index', [
                'id' => $cuestionario,
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('intranet/desempenyo/admin/evaluador/volcado.html.twig', [
            'form' => $form->createView(),
            'cuestionario' => $cuestionario,
        ]);
    }

    /** Rechazar la evaluación de un empleado. */
    #[Route(
        path: '/{cuestionario}/evaluador/rechaza/{empleado?}',
        name: 'evaluador_rechaza',
        methods: ['GET']
    )]
    public function rechaza(
        EmpleadoRepository $empleadoRepository,
        EvaluaRepository $evaluaRepository,
        Cuestionario     $cuestionario,
        ?Empleado        $empleado = null,
    ): Response {
        if ($empleado instanceof Empleado) {
            // Solo administrador puede rechazar a otro empleado
            $this->denyAccessUnlessGranted('admin');
            $ruta = sprintf(
                '%s_%s_cuestionario_evaluador_index',
                $this->actual->getAplicacion()?->getRuta() ?? '',
                $this->actual->getRol()?->getRuta() ?? ''
            );
        } else {
            // Buscar usuario actual como empleado
            /** @var Usuario $usuario */
            $usuario = $this->getUser();
            $empleado = $empleadoRepository->findOneByUsuario($usuario);
            $ruta = $this->actual->getAplicacion()?->getRuta() ?? 'intranet_inicio';
        }

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

            return $this->redirectToRoute($ruta, ['id' => $cuestionario->getId()]);
        }

        $evalua
            ->setEvaluador(null)
            ->setFechaRechazo(new DateTimeImmutable())
        ;
        $evaluaRepository->save($evalua, true);
        $this->generator->logAndFlash('info', 'El empleado ha sido marcado como no evaluable', [
            'cuestionario' => $cuestionario->getCodigo(),
            'empleado' => $empleado?->getPersona()->getUsuario()->getUvus(),
        ]);

        return $this->redirectToRoute($ruta, ['id' => $cuestionario->getId()]);
    }

    /** Recupera la evaluación de un empleado que la había rechazado previamente. */
    #[Route(
        path: '/{cuestionario}/evaluador/recupera/{empleado?}',
        name: 'evaluador_recupera',
        methods: ['GET']
    )]
    public function recupera(
        EmpleadoRepository $empleadoRepository,
        EvaluaRepository $evaluaRepository,
        Cuestionario     $cuestionario,
        ?Empleado        $empleado = null,
    ): Response {
        if ($empleado instanceof Empleado) {
            // Solo administrador puede recuperar a otro empleado
            $this->denyAccessUnlessGranted('admin');
            $ruta = sprintf(
                '%s_%s_cuestionario_evaluador_index',
                $this->actual->getAplicacion()?->getRuta() ?? '',
                $this->actual->getRol()?->getRuta() ?? ''
            );
        } else {
            // Buscar usuario actual como empleado
            /** @var Usuario $usuario */
            $usuario = $this->getUser();
            $empleado = $empleadoRepository->findOneByUsuario($usuario);
            $ruta = $this->actual->getAplicacion()?->getRuta() ?? 'intranet_inicio';
        }

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

            return $this->redirectToRoute($ruta, ['id' => $cuestionario->getId()]);
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

        return $this->redirectToRoute($ruta, ['id' => $cuestionario->getId()]);
    }
}

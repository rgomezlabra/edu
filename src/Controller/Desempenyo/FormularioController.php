<?php

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Formulario;
use App\Entity\Cuestiona\Pregunta;
use App\Entity\Cuestiona\Respuesta;
use App\Entity\Desempenyo\Evalua;
use App\Entity\Plantilla\Empleado;
use App\Entity\Sistema\Usuario;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Cuestiona\FormularioRepository;
use App\Repository\Cuestiona\PreguntaRepository;
use App\Repository\Cuestiona\RespuestaRepository;
use App\Repository\Desempenyo\EvaluaRepository;
use App\Repository\Plantilla\EmpleadoRepository;
use App\Service\MessageGenerator;
use App\Service\RutaActual;
use App\Service\SirhusLock;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function Symfony\Component\String\u;

/**
 * Controlador para gestionar formularios de evaluación del desempeño.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[Route(path: '/intranet/desempenyo', name: 'intranet_desempenyo_')]
class FormularioController extends AbstractController
{
    private int $ttl;   // Tiempo de bloqueo
    private string $rutaBase;   // Ruta base de la aplicación actual

    public function __construct(
        private readonly MessageGenerator $generator,
        private readonly RutaActual       $actual,
        private readonly SirhusLock       $lock,
        private readonly EvaluaRepository $evaluaRepository,
    ) {
        $this->rutaBase = $this->actual->getAplicacion()?->getRuta() ?? 'intranet_inicio';
        $this->ttl = 300;
    }

    #[Route(
        path: '/admin/formulario/',
        name: 'admin_formulario_index',
        defaults: ['titulo' => 'Formularios Entregados'],
        methods: ['GET']
    )]
    public function index(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EvaluaRepository       $evaluaRepository,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        $cuestionario = $cuestionarioRepository->find($request->query->getString('cuestionario'));
        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return $this->redirectToRoute($this->rutaBase);
        }

        $formularios = $evaluaRepository->findByFormularios(['cuestionario' => $cuestionario, 'entregados' => true]);
        $puntos = [];
        foreach ($formularios as $formulario) {
            $total = 0;
            /** @var Respuesta[] $respuestas */
            $respuestas = $formulario->getFormulario()?->getRespuestas();
            foreach ($respuestas as $respuesta) {
                $valor = $respuesta->getValor();
                $total += (int) $valor['valor'];
            }
            if ($total > 0) {
                $total /= count($respuestas);
            }
            $puntos[(int) $formulario->getEmpleado()?->getId()][$formulario->getTipoEvaluador()] = $total;
        }

        return $this->render('intranet/desempenyo/admin/cuestionario/resultado.html.twig', [
            'cuestionario' => $cuestionario,
            'formularios' => $formularios,
            'puntos' => $puntos,
        ]);
    }

    #[Route(
        path: '/admin/formulario/{id}',
        name: 'admin_formulario_show',
        defaults: ['titulo' => 'Formulario Enviado'],
        methods: ['GET']
    )]
    public function show(Evalua $evalua): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $formulario = $evalua->getFormulario();
        if (!$formulario instanceof Formulario) {
            $this->addFlash('warning', 'Evaluación sin cuestionario asociado.');

            return $this->redirectToRoute($this->rutaBase);
        }

        return $this->render('intranet/cuestiona/admin/formulario/show.html.twig', [
            'cuestionario' => $formulario->getCuestionario(),
            'evaluacion' => $evalua,
        ]);
    }

    #[Route(
        path: '/formulario/{codigo}/evaluador',
        name: 'formulario_evaluador_index',
        defaults: ['titulo' => 'Evaluadores Asignados para el Cuestionario'],
        methods: ['GET']
    )]
    public function indexEvaluador(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $empleado = $empleadoRepository->findOneByUsuario($usuario);
        $cuestionario = $cuestionarioRepository->findOneBy([
            'url' => u($request->getRequestUri())->beforeLast("/")->toString(),
        ]);
        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (!$empleado instanceof Empleado) {
            $this->addFlash('warning', 'No se encuentran datos de empleado.');

            return $this->redirectToRoute($this->rutaBase);
        }

        return $this->render(
            sprintf('%s/evaluador.html.twig', $this->actual->getAplicacion()?->rutaToTemplateDir() ?? ''),
            [
                'evaluaciones' => $this->evaluaRepository->findByEvaluacion([
                    'cuestionario' => $cuestionario,
                    'empleado' => $empleado,
                    'tipo' => [Evalua::EVALUA_RESPONSABLE, Evalua::EVALUA_OTRO],
                ]),
                'cuestionario' => $cuestionario,
                'empleado' => $empleado,
            ]
        );
    }

    #[Route(
        path: '/formulario/{codigo}/empleado',
        name: 'formulario_empleado_index',
        defaults: ['titulo' => 'Empleados Asignados para el Cuestionario'],
        methods: ['GET']
    )]
    public function indexEmpleado(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $evaluador = $empleadoRepository->findOneByUsuario($usuario);
        $cuestionario = $cuestionarioRepository->findOneBy([
            'url' => u($request->getRequestUri())->beforeLast("/")->toString(),
        ]);
        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (!$evaluador instanceof Empleado) {
            $this->addFlash('warning', 'No se encuentran datos de empleado.');

            return $this->redirectToRoute($this->rutaBase);
        }

        $tipo = $request->query->getInt('tipo');
        if (Evalua::EVALUA_OTRO !== $tipo) {
            $tipo = Evalua::EVALUA_RESPONSABLE;
        }
        $evaluaciones = $this->evaluaRepository->findByEvaluacion([
            'cuestionario' => $cuestionario,
            'evaluador' => $evaluador,
            'tipo' => $tipo,
        ]);
        if ([] === $evaluaciones) {
            $this->addFlash('warning', 'El usuario no es un evaluador.');

            return $this->redirectToRoute($this->rutaBase);
        }

        return $this->render(
            sprintf('%s/empleado.html.twig', $this->actual->getAplicacion()?->rutaToTemplateDir() ?? ''),
            [
                'evaluaciones' => $evaluaciones,
                'cuestionario' => $cuestionario,
                'evaluador' => $evaluador,
            ]
        );
    }

    /** Rellenar formulario. */
    #[Route(
        path: '/formulario/{codigo}/{id?}',
        name: 'formulario_rellenar',
        requirements: ['codigo' => '[a-z0-9-]+', 'evalua' => '\d+'],
        methods: ['GET']
    )]
    public function rellenar(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
        string                 $codigo,
        ?int                   $id,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        if (null === $id) {
            $cuestionario = $cuestionarioRepository->findOneBy(['url' => $request->getRequestUri()]);
            $empleado = $empleadoRepository->findOneByUsuario($usuario);
            $evaluador = null;
        } else {
            $cuestionario = $cuestionarioRepository->findOneBy([
                'url' => u($request->getRequestUri())->before('/' . $id)->toString(),
            ]);
            $empleado = $empleadoRepository->find($id);
            $evaluador = $empleadoRepository->findOneByUsuario($usuario);
        }

        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (!$empleado instanceof Empleado || (null !== $id && !$evaluador instanceof Empleado)) {
            $this->addFlash('warning', 'No se encuentran datos de empleado.');

            return $this->redirectToRoute($this->rutaBase);
        }
        $evalua = $this->getEvalua($cuestionario, $empleado, $evaluador);
        if (!$evalua instanceof Evalua) {
            $this->addFlash('warning', 'El empleado no es evaluable.');

            return $this->redirectToRoute($this->rutaBase);
        }

        $respuestas = [];
        $formulario = $evalua->getFormulario();
        if ($formulario instanceof Formulario) {
            foreach ($formulario->getRespuestas() as $respuesta) {
                $pregunta = $respuesta->getPregunta();
                if ($pregunta instanceof Pregunta) {
                    $respuestas[(int) $pregunta->getId()] = $respuesta->getValor();
                }
            }

            if ($formulario->getFechaEnvio() instanceof DateTimeImmutable) {
                return $this->render('intranet/desempenyo/formulario_ver.html.twig', [
                    'formulario' => $evalua,
                    'respuestas' => $respuestas,
                    'codigo' => $codigo,
                ]);
            }
        } elseif (null === $this->lock->acquire($this->ttl)) {
            $this->addFlash('warning', 'Esta evaluación ya está abierta.');

            return $this->redirectToRoute($this->rutaBase);
        }

        return $this->render('intranet/desempenyo/formulario.html.twig', [
            'evalua' => $evalua,
            'respuestas' => $respuestas,
            'codigo' => $codigo,
            'sesion' => $this->lock->getRemainingLifetime(),
        ]);
    }

    /** Guardar el formulario. */
    #[Route(
        path: '/formulario/{codigo}',
        name: 'formulario_guardar',
        methods: ['POST']
    )]
    public function guardar(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
        FormularioRepository   $formularioRepository,
        PreguntaRepository     $preguntaRepository,
        RespuestaRepository    $respuestaRepository,
        string                 $codigo,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $empleado = $empleadoRepository->find($request->request->getInt('empleado'));
        $idEvaluador = $request->request->getInt('evaluador');
        $evaluador = $empleadoRepository->find($idEvaluador);
        $cuestionario = $cuestionarioRepository->findOneBy([
            'url' => sprintf('/%s/formulario/%s', $this->actual->getAplicacion()?->rutaToTemplateDir() ?? '', $codigo),
        ]);
        $token = sprintf('%s.%d', $codigo, (int) $cuestionario?->getId());
        $enviado = $request->request->getBoolean('enviado');
        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (!$this->isCsrfTokenValid($token, $request->request->getString('_token'))) {
            $this->generator->logAndFlash('error', 'Token de validación incorrecto');

            return $this->redirectToRoute($this->rutaBase);
        } elseif ($this->lock->isExpired()) {
            $this->generator->logAndFlash('error', 'El tiempo de validez del cuestionario ha caducado.');

            return $this->redirectToRoute('intranet_desempenyo');
        } elseif (!$empleado instanceof Empleado || (0 !== $idEvaluador && !$evaluador instanceof Empleado)) {
            $this->addFlash('warning', 'No se encuentran datos de empleado.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (0 === $idEvaluador && $empleado !== $empleadoRepository->findOneByUsuario($usuario)) {
            $this->addFlash('warning', 'El usuario no corresponde con el empleado del formulario.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (0 !== $idEvaluador && $evaluador !== $empleadoRepository->findOneByUsuario($usuario)) {
            $this->addFlash('warning', 'El usuario no corresponde con el evaluador del formulario.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (!$this->getEvalua($cuestionario, $empleado, $evaluador) instanceof Evalua) {
            $this->addFlash('warning', 'El empleado no es evaluable.');

            return $this->redirectToRoute($this->rutaBase);
        }

        $evalua = $this->evaluaRepository->findBy([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'evaluador' => $evaluador,
        ])[0];
        $formulario = $evalua->getFormulario();
        if (!$formulario instanceof Formulario) {
            // Nuevo formulario
            $formulario = new Formulario();
            $formulario
                ->setCuestionario($cuestionario)
                ->setUsuario($usuario)
            ;
            $evalua->setFormulario($formulario);
        }

        /**
         * @var string $clave
         * @var string|string[] $valor
         */
        foreach ($request->request->all() as $clave => $valor) {
            $pregunta = $preguntaRepository->find((int) u($clave)->after('_')->toString());
            if ($pregunta instanceof Pregunta) {
                $respuesta = $formulario->getRespuestas()->filter(
                    static fn (Respuesta $respuesta) =>
                        $respuesta->getFormulario() === $formulario && $respuesta->getPregunta() === $pregunta
                )->first();
                if (!$respuesta instanceof Respuesta) {
                    $respuesta = new Respuesta();
                    $respuesta
                        ->setFormulario($formulario)
                        ->setPregunta($pregunta)
                    ;
                }

                // TODO puede ser necesario verificar validez de datos
                if (0 === u($clave)->indexOf('preg_')) {
                    $respuesta->setValor([...$respuesta->getValor(), ...['valor' => $valor]]);
                } elseif (0 === u($clave)->indexOf('observa_')) {
                    $respuesta->setValor([...$respuesta->getValor(), ...['observa' => $valor]]);
                }

                $respuestaRepository->save($respuesta);
                $formulario->addRespuesta($respuesta);
            }
        }

        $formulario->setFechaGrabacion(new DateTimeImmutable());
        if ($enviado) {
            $formulario->setFechaEnvio(new DateTimeImmutable());
        }

        $formularioRepository->save($formulario);
        $this->evaluaRepository->save($evalua, true);
        if ($enviado) {
            $this->lock->release();
            $this->generator->logAndFlash('info', 'Formulario enviado correctamente.', [
                'codigo' => $codigo,
                'empleado' => $empleado,
                'evaluador' => $evaluador,
            ]);

            return $this->redirectToRoute($this->rutaBase);
        } else {
            $this->addFlash('info', 'Formulario guardado sin enviar.');
        }

        return $this->redirectToRoute($this->rutaBase . '_formulario_rellenar', [
            'codigo' => $codigo,
            'id' => 0 === $idEvaluador ? null : $empleado->getId(),
        ]);
    }

    /** Devuelve el permiso de evaluación para un evaluador sobre un empleado en un cuestionario dado. */
    private function getEvalua(
        Cuestionario $cuestionario,
        Empleado     $empleado,
        ?Empleado    $evaluador = null,
    ): ?Evalua {
        $criterios = [
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
        ];
        $evalua = $this->evaluaRepository->findOneBy($criterios + ['tipo_evaluador' => Evalua::NO_EVALUACION]);
        if ($evalua instanceof Evalua) {
            return $evalua;
        }

        $criterios += null === $evaluador ? ['tipo_evaluador' => Evalua::AUTOEVALUACION] : ['evaluador' => $evaluador];

        return $this->evaluaRepository->findOneBy($criterios);
    }
}

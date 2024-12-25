<?php

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Formulario;
use App\Entity\Cuestiona\Pregunta;
use App\Entity\Cuestiona\Respuesta;
use App\Entity\Desempenyo\Evalua;
use App\Entity\Estado;
use App\Entity\Plantilla\Empleado;
use App\Entity\Usuario;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Cuestiona\FormularioRepository;
use App\Repository\Cuestiona\PreguntaRepository;
use App\Repository\Cuestiona\RespuestaRepository;
use App\Repository\Desempenyo\EvaluaRepository;
use App\Repository\Plantilla\EmpleadoRepository;
use App\Service\MessageGenerator;
use App\Service\RutaActual;
use App\Service\SirhusLock;
use App\Service\Slug;
use DateTimeImmutable;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function Symfony\Component\String\u;

/**
 * Controlador para gestionar formularios de evaluación del desempeño.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[Route(path: '/desempenyo', name: 'desempenyo_')]
class FormularioController extends AbstractController
{
    /** @var string $rutaBase Ruta base de la aplicación actual */
    private readonly string $rutaBase;
    /** @var int $ttl Tiempo de bloqueo en s. */
    private readonly int $ttl;

    /** @var int Diferencia de puntos para considerar que las valoraciones son dispares */
    public const int RANGO_COMPARA = 5;

    public function __construct(
        private readonly MessageGenerator $generator,
        private readonly RutaActual       $actual,
        private readonly SirhusLock       $lock,
        private readonly EvaluaRepository $evaluaRepository,
    ) {
        $this->rutaBase = $this->actual->getAplicacion()?->getRuta() ?? 'inicio';
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

        $datos = [];
        foreach ($evaluaRepository->findByEntregados(['cuestionario' => $cuestionario]) as $formulario) {
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
            $datos[(int) $formulario->getEmpleado()?->getId()][$formulario->getTipoEvaluador()] = [
                'formulario' => $formulario,
                'puntos' => $total,
            ];
        }

        return $this->render('desempenyo/admin/cuestionario/resultado.html.twig', [
            'cuestionario' => $cuestionario,
            'datos' => $datos,
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

        return $this->render('cuestiona/admin/formulario/show.html.twig', [
            'cuestionario' => $formulario->getCuestionario(),
            'evaluacion' => $evalua,
        ]);
    }

    /** Obtener la matriz comparativa con los formularios entregados para un empleado. */
    #[Route(
        path: '/admin/cuestionario/{cuestionario}/formulario/empleado/{empleado}',
        name: 'admin_cuestionario_formulario_matriz',
        defaults: ['titulo' => 'Matriz de Formularios Entregados'],
        methods: ['GET']
    )]
    public function matriz(
        Request          $request,
        EvaluaRepository $evaluaRepository,
        Cuestionario     $cuestionario,
        Empleado         $empleado
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        /** @var array<Respuesta[]> $respuestas */
        $respuestas = [];
        /** @var float[] $medias */
        $medias = [];
        $formularios = $evaluaRepository->findByEntregados([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
        ]);
        foreach ($formularios as $formulario) {
            $total = 0;
            $n = 0;
            foreach ($formulario->getFormulario()?->getRespuestas() ?? [] as $respuesta) {
                $pregunta = $respuesta->getPregunta();
                if ($pregunta instanceof Pregunta) {
                    $respuestas[$formulario->getTipoEvaluador()][(int) $pregunta->getId()] = $respuesta->getValor();
                    $total += (float) $respuesta->getValor()['valor'];
                    $n++;
                }
            }
            $medias[$formulario->getTipoEvaluador()] = $total / $n;
        }

        return $this->render('desempenyo/admin/cuestionario/matriz.html.twig', [
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'formularios' => $formularios,
            'respuestas' => $respuestas,
            'medias' => $medias,
            'detalle' => $request->query->getBoolean('detalle'),
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

    /** Generar PDF de formulario. */
    #[Route(
        path: '/formulario/{codigo}/pdf/{id?}',
        name: 'formulario_pdf',
        requirements: ['codigo' => '[a-z0-9-]+', 'id' => '\d+'],
        methods: ['GET']
    )]
    public function pdf(
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
        string                 $codigo,
        ?int                   $id,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $cuestionario = $cuestionarioRepository->findOneBy([
            'url' => sprintf('/%s/formulario/%s', $this->actual->getAplicacion()?->rutaToTemplateDir() ?? '', $codigo),
        ]);
        if (null === $id) {
            $empleado = $empleadoRepository->findOneByUsuario($usuario);
            $evaluador = null;
        } else {
            $empleado = $empleadoRepository->find($id);
            $evaluador = $empleadoRepository->findOneByUsuario($usuario);
        }
        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (!$empleado instanceof Empleado) {
            $this->addFlash('warning', 'No se encuentran datos de empleado.');

            return $this->redirectToRoute($this->rutaBase);
        }
        $evalua = $this->evaluaRepository->findByEntregados([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'evaluador' => $evaluador,
        ])[0] ?? null;
        if (!$evalua instanceof Evalua) {
            $this->addFlash('warning', 'El empleado no es evaluable.');

            return $this->redirectToRoute($this->rutaBase);
        }
        $formulario = $evalua->getFormulario();
        if (!$formulario instanceof Formulario) {
            $this->addFlash('warning', 'El formulario solicitado no existe o no esta disponible.');

            return $this->redirectToRoute($this->rutaBase);
        }
        $respuestas = [];
        foreach ($formulario->getRespuestas() as $respuesta) {
            $pregunta = $respuesta->getPregunta();
            if ($pregunta instanceof Pregunta) {
                $respuestas[(int) $pregunta->getId()] = $respuesta->getValor();
            }
        }

        // Generar PDF
        $html = $this->renderView('desempenyo/formulario_pdf.html.twig', [
            'codigo' => $codigo,
            'evalua' => $evalua,
            'respuestas' => $respuestas,
        ]);
        $opciones = new Options();
        $opciones
            ->setDefaultFont('sans-serif')
            ->setDefaultPaperSize('A4')
            ->setDefaultPaperOrientation('landscape')
        ;
        $dompdf = new Dompdf($opciones);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        ob_start();
        $dompdf->stream(sprintf('autoevaluacion-%s.pdf', (new Slug())((string) $cuestionario->getCodigo())));

        return new Response();
    }

    /** Rellenar formulario. */
    #[Route(
        path: '/formulario/{codigo}/{id?}',
        name: 'formulario_rellenar',
        requirements: ['codigo' => '[a-z0-9-]+', 'id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function rellenar(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
        FormularioRepository   $formularioRepository,
        PreguntaRepository     $preguntaRepository,
        RespuestaRepository    $respuestaRepository,
        string                 $codigo,
        ?int                   $id,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        if (null === $id) {
            $cuestionario = $cuestionarioRepository->findOneBy(['url' => $request->getRequestUri()]);
        } else {
            $cuestionario = $cuestionarioRepository->findOneBy([
                'url' => u($request->getRequestUri())->before('/' . $id)->toString(),
            ]);
        }

        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (Estado::PUBLICADO !== $cuestionario->getEstado()?->getNombre()) {
            $this->addFlash('warning', 'El cuestionario no está activo.');

            return $this->redirectToRoute($this->rutaBase);
        }

        if ('GET' === $request->getMethod()) {
            // Rellenar el formulario
            if (null === $this->lock->acquire($this->ttl)) {
                $this->addFlash('warning', 'Esta evaluación ya está abierta.');

                return $this->redirectToRoute($this->rutaBase);
            }

            if (null === $id) {
                $empleado = $empleadoRepository->findOneByUsuario($usuario);
                $evaluador = null;
            } else {
                $empleado = $empleadoRepository->find($id);
                $evaluador = $empleadoRepository->findOneByUsuario($usuario);
            }

            if (!$empleado instanceof Empleado || (null !== $id && !$evaluador instanceof Empleado)) {
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
                    return $this->render('desempenyo/formulario.html.twig', [
                        'evalua' => $evalua,
                        'respuestas' => $respuestas,
                        'codigo' => $codigo,
                    ]);
                }
            }

            return $this->render('desempenyo/formulario.html.twig', [
                'evalua' => $evalua,
                'respuestas' => $respuestas,
                'codigo' => $codigo,
                'sesion' => $this->lock->getRemainingLifetime(),
            ]);
        }

        // Grabar el formulario relleno
        $empleado = $empleadoRepository->find($request->request->getInt('empleado'));
        $idEvaluador = $request->request->getInt('evaluador');
        $evaluador = $empleadoRepository->find($idEvaluador);
        $enviado = $request->request->getBoolean('enviado');
        if ($this->lock->isExpired()) {
            $this->lock->release();
            $this->addFlash('danger', 'El tiempo de validez del cuestionario ha caducado.');

            return $this->redirectToRoute($this->rutaBase);
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
        $token = sprintf('%s.%d', $codigo, (int) $evalua->getId());
        if (!$this->isCsrfTokenValid($token, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token de validación incorrecto.');

            return $this->redirectToRoute($this->rutaBase);
        }

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
            $this->generator->logAndFlash('info', 'Formulario enviado', [
                'codigo' => $codigo,
                'empleado' => $evalua->getEmpleado()?->getPersona()->getDocIdentidad(),
                'evaluador' => $evalua->getEvaluador()?->getPersona()->getDocIdentidad(),
                'tipo' => $evalua->getTipoEvaluador(),
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

    /** Compara las respuestas con diferencia mayor de 5 entre autoevaluación y evaluación principal. */
    #[Route(
        path: '/formulario/{codigo}/compara',
        name: 'formulario_evaluador_compara',
        defaults: ['titulo' => 'Valoraciones Dispares'],
        methods: ['GET']
    )]
    public function comparar(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
        string                 $codigo,
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

        $auto = $this->evaluaRepository->findByEvaluacion([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo' => Evalua::AUTOEVALUACION,
        ])[0] ?? null;
        $principal = $this->evaluaRepository->findByEntregados([
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
            'tipo' => Evalua::EVALUA_RESPONSABLE,
        ])[0] ?? null;
        if (!$auto instanceof Evalua || !$principal instanceof Evalua) {
            $this->addFlash('warning', 'Deben estar entregadas la autoevaluación y la evaluación del responsable.');

            return $this->redirectToRoute($this->rutaBase);
        }

        /** @var array<Respuesta[]> $respuestas */
        $respuestas = [];
        foreach ($auto->getFormulario()?->getRespuestas() ?? [] as $respuesta) {
            if ($respuesta->getPregunta()?->isActiva()) {
                $respPrincipal = $principal->getFormulario()?->getRespuestas()->filter(
                    static fn (Respuesta $resp) => $resp->getPregunta()?->getId() === $respuesta->getPregunta()->getId()
                )->first();
                if ($respPrincipal instanceof Respuesta &&
                    abs((int) $respuesta->getValor()['valor'] - (int) $respPrincipal->getValor()['valor']) > self::RANGO_COMPARA) {
                    $respuestas[] = [
                        'pregunta' => $respuesta->getPregunta(),
                        'auto' => $respuesta->getValor(),
                        'principal' => $respPrincipal->getValor(),
                    ];
                }
            }
        }

        return $this->render('desempenyo/formulario_compara.html.twig', [
            'respuestas' => $respuestas,
            'codigo' => $codigo,
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

<?php

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Formulario as CuestionaFormulario;
use App\Entity\Cuestiona\Pregunta;
use App\Entity\Cuestiona\Respuesta;
use App\Entity\Desempenyo\Evalua;
use App\Entity\Desempenyo\Formulario;
use App\Entity\Plantilla\Empleado;
use App\Entity\Sistema\Usuario;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Cuestiona\FormularioRepository as CuestionaFormularioRepository;
use App\Repository\Cuestiona\PreguntaRepository;
use App\Repository\Cuestiona\RespuestaRepository;
use App\Repository\Desempenyo\EvaluaRepository;
use App\Repository\Desempenyo\FormularioRepository;
use App\Repository\Plantilla\EmpleadoRepository;
use App\Service\MessageGenerator;
use App\Service\RutaActual;
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
class FormularioController extends AbstractController
{
    public function __construct(
        private readonly MessageGenerator     $generator,
        private readonly RutaActual           $actual,
        private readonly EvaluaRepository     $evaluaRepository,
        private readonly FormularioRepository $formularioRepository,
    ) {
    }

    #[Route(
        path: '/intranet/cuestiona/admin/formulario/',
        name: 'intranet_cuestiona_admin_formulario_index',
        defaults: ['titulo' => 'Formularios Entregados'],
        methods: ['GET']
    )]
    public function index(Request $request, CuestionarioRepository $cuestionarioRepository): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $cuestionario = $cuestionarioRepository->find($request->query->getString('cuestionario'));
        if (!$cuestionario instanceof Cuestionario || $cuestionario->getAutor() !== $this->getUser()) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return $this->redirectToRoute($this->actual->getAplicacion()?->getRuta() ?? 'inicio');
        }

        return $this->render('intranet/cuestiona/admin/formulario/index.html.twig', [
            'cuestionario' => $cuestionario,
            'formularios' => $this->formularioRepository->findByCuestionario($cuestionario),
        ]);
    }

    #[Route(
        path: '/intranet/cuestiona/admin/formulario/{id}',
        name: 'intranet_cuestiona_admin_formulario_show',
        defaults: ['titulo' => 'Formulario Enviado'],
        methods: ['GET']
    )]
    public function show(Formulario $formulario): Response
    {
        $this->denyAccessUnlessGranted('admin');

        return $this->render('intranet/cuestiona/admin/formulario/show.html.twig', [
            'cuestionario' => $formulario->getFormulario()?->getCuestionario(),
            'formulario' => $formulario,
        ]);
    }

    #[Route(
        path: '/intranet/desempenyo/formulario/{codigo}/evaluador',
        name: 'intranet_desempenyo_formulario_evaluador_index',
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

            return $this->redirectToRoute('intranet_desempenyo');
        } elseif (!$empleado instanceof Empleado) {
            $this->addFlash('warning', 'No se encuentran datos de empleado.');

            return $this->redirectToRoute('intranet_desempenyo');
        }

        return $this->render(sprintf('%s/evaluador.html.twig', $this->actual->getAplicacion()?->rutaToTemplateDir() ?? ''), [
            'evaluaciones' => $this->evaluaRepository->findByEvaluacion([
                'cuestionario' => $cuestionario,
                'empleado' => $empleado,
                'tipo' => EvaluaRepository::EVALUACION,
            ]),
            'cuestionario' => $cuestionario,
            'empleado' => $empleado,
        ]);
    }

    #[Route(
        path: '/intranet/desempenyo/formulario/{codigo}/empleado',
        name: 'intranet_desempenyo_formulario_empleado_index',
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

            return $this->redirectToRoute('inicio');
        } elseif (!$evaluador instanceof Empleado) {
            $this->addFlash('warning', 'No se encuentran datos de empleado.');

            return $this->redirectToRoute('inicio');
        }

        $evaluaciones = $this->evaluaRepository->findByEvaluacion([
            'cuestionario' => $cuestionario,
            'evaluador' => $evaluador,
            'tipo' => EvaluaRepository::EVALUACION,
        ]);
        if ([] === $evaluaciones) {
            $this->addFlash('warning', 'El usuario es un evaluador.');

            return $this->redirectToRoute($this->actual->getAplicacion()?->getRuta() ?? 'inicio');
        }

        return $this->render(sprintf('%s/empleado.html.twig', $this->actual->getAplicacion()?->rutaToTemplateDir() ?? ''), [
            'evaluaciones' => $evaluaciones,
            'cuestionario' => $cuestionario,
            'evaluador' => $evaluador,
        ]);
    }

    /** Rellenar formulario. */
    #[Route(
        path: '/intranet/desempenyo/formulario/{codigo}/{evalua?}',
        name: 'intranet_desempenyo_formulario_rellenar',
        requirements: ['codigo' => '[a-z0-9-]+', 'evalua' => '\d+'],
        methods: ['GET']
    )]
    public function rellenar(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
        string                 $codigo,
        ?int                   $evalua,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        if (null === $evalua) {
            $cuestionario = $cuestionarioRepository->findOneBy(['url' => $request->getRequestUri()]);
            $empleado = $empleadoRepository->findOneByUsuario($usuario);
            $evaluador = $empleado;
        } else {
            $cuestionario = $cuestionarioRepository->findOneBy([
                'url' => u($request->getRequestUri())->before('/' . $evalua)->toString(),
            ]);
            $empleado = $empleadoRepository->find($evalua);
            $evaluador = $empleadoRepository->findOneByUsuario($usuario);
        }

        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute('intranet_desempenyo');
        } elseif (!$empleado instanceof Empleado || !$evaluador instanceof Empleado) {
            $this->addFlash('warning', 'No se encuentran datos de empleado.');

            return $this->redirectToRoute('intranet_desempenyo');
        } elseif (!$this->isEvaluable($cuestionario, $empleado, $evaluador)) {
            return $this->redirectToRoute('intranet_desempenyo');
        }

        $respuestas = [];
        $formulario = $this->formularioRepository->findByCuestionario($cuestionario, $empleado, $evaluador)[0] ?? null;
        if ($formulario instanceof Formulario) {
            foreach ($formulario->getFormulario()?->getRespuestas() ?? [] as $respuesta) {
                if ($respuesta->getPregunta() instanceof Pregunta) {
                    $respuestas[$respuesta->getPregunta()->getId()] = $respuesta->getValor();
                }
            }

            if ($formulario->getFormulario()?->getFechaEnvio() instanceof DateTimeImmutable) {
                return $this->render('intranet/desempenyo/formulario_ver.html.twig', [
                    'formulario' => $formulario,
                    'respuestas' => $respuestas,
                    'codigo' => $codigo,
                ]);
            }
        } else {
            $cuestionaFormulario = new CuestionaFormulario();
            $cuestionaFormulario
                ->setCuestionario($cuestionario)
                ->setUsuario($usuario)
            ;
            $formulario = new Formulario();
            $formulario
                ->setFormulario($cuestionaFormulario)
                ->setEmpleado($empleado)
                ->setEvaluador($evaluador)
            ;
        }

        return $this->render('intranet/desempenyo/formulario.html.twig', [
            'formulario' => $formulario,
            'respuestas' => $respuestas,
            'codigo' => $codigo,
        ]);
    }

    /** Guardar el formulario. */
    #[Route(
        path: '/intranet/desempenyo/formulario/{codigo}',
        name: 'intranet_desempenyo_formulario_guardar',
        methods: ['POST']
    )]
    public function guardar(
        Request                       $request,
        CuestionarioRepository        $cuestionarioRepository,
        EmpleadoRepository            $empleadoRepository,
        CuestionaFormularioRepository $cuestionaFormularioRepository,
        PreguntaRepository            $preguntaRepository,
        RespuestaRepository           $respuestaRepository,
        string                        $codigo,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $empleado = $empleadoRepository->find($request->request->getInt('empleado'));
        $evaluador = $empleadoRepository->find($request->request->getInt('evaluador'));
        $cuestionario = $cuestionarioRepository->findOneBy([
            'url' => sprintf('/%s/formulario/%s', $this->actual->getAplicacion()?->rutaToTemplateDir() ?? '', $codigo),
        ]);
        $token = sprintf('%s.%d', $codigo, (int) $cuestionario?->getId());
        $enviado = $request->request->getBoolean('enviado');
        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute('intranet_desempenyo');
        } elseif (!$this->isCsrfTokenValid($token, $request->request->getString('_token'))) {
            $this->generator->logAndFlash('error', 'Token de validación incorrecto');

            return $this->redirectToRoute('intranet_desempenyo');
        } elseif (!$empleado instanceof Empleado || !$evaluador instanceof Empleado) {
            $this->addFlash('warning', 'No se encuentran datos de empleado.');

            return $this->redirectToRoute('intranet_desempenyo');
        } elseif ($evaluador !== $empleadoRepository->findOneByUsuario($usuario)) {
            $this->addFlash('warning', 'El usuario no corresponde con el evaluador del formulario.');

            return $this->redirectToRoute('inicio');
        } elseif (!$this->isEvaluable($cuestionario, $empleado, $evaluador)) {
            return $this->redirectToRoute('intranet_desempenyo');
        }

        $formulario = $this->formularioRepository->findByCuestionario($cuestionario, $empleado, $evaluador)[0] ?? null;
        if ($formulario instanceof Formulario) {
            $cuestionaFormulario = $formulario->getFormulario();
        } else {
            // Nuevo formulario
            $cuestionaFormulario = new CuestionaFormulario();
            $cuestionaFormulario
                ->setCuestionario($cuestionario)
                ->setUsuario($usuario)
            ;
            $formulario = new Formulario();
            $formulario
                ->setFormulario($cuestionaFormulario)
                ->setEmpleado($empleado)
                ->setEvaluador($evaluador)
            ;
        }

        /**
         * @var string $clave
         * @var string|string[] $valor
         */
        foreach ($request->request->all() as $clave => $valor) {
            $pregunta = $preguntaRepository->find((int) u($clave)->after('_')->toString());
            if ($pregunta instanceof Pregunta) {
                $respuesta = $cuestionaFormulario?->getRespuestas()->filter(
                    static fn (Respuesta $respuesta) => $respuesta->getFormulario() === $formulario->getFormulario() && $respuesta->getPregunta() === $pregunta
                )->first();
                if (!$respuesta instanceof Respuesta) {
                    $respuesta = new Respuesta();
                    $respuesta
                        ->setFormulario($cuestionaFormulario)
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
                $cuestionaFormulario->addRespuesta($respuesta);
            }
        }

        $cuestionaFormulario->setFechaGrabacion(new DateTimeImmutable());
        if ($enviado) {
            $cuestionaFormulario->setFechaEnvio(new DateTimeImmutable());
        }

        $cuestionaFormularioRepository->save($cuestionaFormulario);
        $this->formularioRepository->save($formulario, true);
        if ($enviado) {
            $this->generator->logAndFlash('info', 'Formulario enviado correctamente.', [
                'codigo' => $codigo,
                'empleado' => $empleado,
                'evaluador' => $evaluador,
            ]);

            return $this->redirectToRoute('intranet_desempenyo');
        } else {
            $this->addFlash('info', 'Formulario guardado sin enviar.');
        }

        return $this->redirectToRoute('intranet_desempenyo_formulario_rellenar', [
            'codigo' => $codigo,
            'evalua' => $empleado->getId(),
        ]);
    }

    /** Comprobar si el empleado puede ser evaluado por el evaluador. */
    private function isEvaluable(Cuestionario $cuestionario, Empleado $empleado, Empleado $evaluador): bool
    {
        if (!$this->evaluaRepository->findOneBy([
                'cuestionario' => $cuestionario,
                'empleado' => $empleado,
                'evaluador' => $evaluador,
            ]) instanceof Evalua) {
            $this->addFlash('warning', 'El empleado no es evaluable.');

            return false;
        } elseif ($this->evaluaRepository->findOneBy([
                'cuestionario' => $cuestionario,
                'empleado' => $empleado,
                'evaluador' => null,
            ]) instanceof Evalua) {
            $this->addFlash('warning', 'El empleado ha solicitado no ser evaluado.');

            return false;
        }

        return true;
    }
}

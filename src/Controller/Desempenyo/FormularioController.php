<?php

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Formulario as CuestionaFormulario;
use App\Entity\Cuestiona\Pregunta;
use App\Entity\Cuestiona\Respuesta;
use App\Entity\Desempenyo\Formulario;
use App\Entity\Sistema\Usuario;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Cuestiona\PreguntaRepository;
use App\Repository\Cuestiona\RespuestaRepository;
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

    /** Rellenar formulario. */
    #[Route(
        path: '/intranet/desempenyo/formulario/{codigo}',
        name: 'intranet_desempenyo_formulario_rellenar',
        methods: ['GET']
    )]
    public function rellenar(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
        string                 $codigo
    ): Response {
        $cuestionario = $cuestionarioRepository->findOneBy(['url' => $request->getRequestUri()]);
        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute('inicio');
        }

        // TODO por el momento, solo autoevaluación
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $empleado = $empleadoRepository->findOneByUsuario($usuario);
        $formulario = $this->formularioRepository->findByCuestionario($cuestionario, $empleado, $empleado)[0] ?? null;
        if ($formulario instanceof Formulario) {
            if ($formulario->getFormulario()?->getFechaEnvio() instanceof DateTimeImmutable) {
                $this->addFlash('warning', 'El formulario ya ha sido enviado previamente.');

                return $this->redirectToRoute('inicio');
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
                ->setEvaluador($empleado)
            ;
        }

        return $this->render(sprintf('%s/formulario.html.twig', $this->actual->getAplicacion()?->rutaToTemplateDir() ?? ''), [
            'formulario' => $formulario,
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
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        FormularioRepository   $formularioRepository,
        PreguntaRepository     $preguntaRepository,
        RespuestaRepository    $respuestaRepository,
        string                 $codigo,
    ): Response {
        $cuestionario = $cuestionarioRepository->findOneBy(['url' => '/cuestiona/' . $codigo]);
        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe.');

            return $this->redirectToRoute('inicio');
        }

        $token = sprintf('%s.%d', $codigo, (int) $cuestionario->getId());
        if (!$this->isCsrfTokenValid($token, (string) $request->request->get('_token'))) {
            $this->generator->logAndFlash('error', 'Token de validación incorrecto');

            return $this->redirectToRoute('inicio');
        }

        // TODO verificar si el formulario es editable
        $formulario = $formularioRepository->findOneBy(['cuestionario' => $cuestionario, 'usuario' => $this->getUser()]);
        if (!$formulario instanceof Formulario) {
            // Nuevo formulario
            $formulario = new Formulario();
            $formulario
                ->setCuestionario($cuestionario)
                ->setUsuario($this->getUser());
        }

        /**
         * @var string $clave
         * @var string|string[] $valor
         */
        foreach ($request->request->all() as $clave => $valor) {
            $pregunta = $preguntaRepository->find((int) u($clave)->after('-')->toString());
            if ($pregunta instanceof Pregunta) {
                $respuesta = $formulario->getRespuestas()->filter(static fn (Respuesta $respuesta) => $respuesta->getFormulario() === $formulario && $respuesta->getPregunta() === $pregunta)->first();
                if (!$respuesta instanceof Respuesta) {
                    $respuesta = new Respuesta();
                    $respuesta
                        ->setFormulario($formulario)
                        ->setPregunta($pregunta)
                    ;
                }

                // TODO puede ser necesario verificar validez de datos
                if (0 === u($clave)->indexOf('preg-')) {
                    $respuesta->setValor([...$respuesta->getValor(), ...['valor' => $valor]]);
                } elseif (0 === u($clave)->indexOf('observa-')) {
                    $respuesta->setValor([...$respuesta->getValor(), ...['observa' => $valor]]);
                }

                $formulario->addRespuesta($respuesta);
            }
        }

        // Grabar datos
        foreach ($formulario->getRespuestas() as $respuesta) {
            $respuestaRepository->save($respuesta);
        }

        $formulario->setEnviado(new DateTimeImmutable());
        $formularioRepository->save($formulario, true);

        return $this->redirectToRoute('cuestiona_index');
    }
}

<?php

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Grupo;
use App\Entity\Cuestiona\Pregunta;
use App\Entity\Sistema\Aplicacion;
use App\Entity\Sistema\Estado;
use App\Form\Cuestiona\PreguntaType;
use App\Repository\Cuestiona\PreguntaRepository;
use App\Service\MessageGenerator;
use App\Service\RutaActual;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador para gestionar preguntas para cuestionarios.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class PreguntaController extends AbstractController
{
    // Opciones por defecto para las preguntas soportadas por la aplicación
    /** @var array<array-key, int[]|bool[]> $opciones */
    private array $opciones = [
        Pregunta::RANGO => [
            'min' => 1,
            'max' => 10,
            'observaciones' => true,
        ],
    ];

    private ?Aplicacion $aplic;

    public function __construct(
        private readonly MessageGenerator   $generator,
        private readonly RutaActual         $actual,
        private readonly PreguntaRepository $preguntaRepository,
    ) {
        $this->aplic = $this->actual->getAplicacion();
    }

    #[Route(
        path: [
            '/intranet/desempenyo/admin/cuestionario/{cuestionario}/grupo/{grupo}/pregunta',
            '/{cuestionario}/pregunta',
        ],
        name: 'intranet_desempenyo_admin_pregunta_index',
        defaults: ['titulo' => 'Preguntas'],
        methods: ['GET']
    )]
    public function index(Cuestionario $cuestionario, ?Grupo $grupo): Response
    {
        $this->denyAccessUnlessGranted('admin');
        if (!$this->checkAcceso($cuestionario, $grupo)) {
            return $this->redirectToRoute($this->aplic?->getRuta() ?? 'intranet_inicio');
        }

        // Preguntas de un grupo o de cuestionario completo
        $preguntas = $grupo instanceof Grupo ? $grupo->getPreguntas() : $this->preguntaRepository->findByCuestionario(
            $cuestionario
        );

        return $this->render(sprintf('%s/admin/pregunta/index.html.twig', $this->aplic?->rutaToTemplateDir() ?? ''), [
            'cuestionario' => $cuestionario,
            'grupo' => $grupo,
            'preguntas' => $preguntas,
            'opciones' => $this->opciones,
        ]);
    }

    #[Route(
        path: '/intranet/desempenyo/admin/cuestionario/{cuestionario}/grupo/{grupo}/pregunta/new',
        name: 'intranet_desempenyo_admin_pregunta_new',
        defaults: ['titulo' => 'Nueva Pregunta de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function new(Request $request, Cuestionario $cuestionario, Grupo $grupo): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $tipos = $this->cargarTipos();
        $tipo = $request->query->getInt('tipo');
        if (!$this->checkAcceso($cuestionario, $grupo)) {
            return $this->redirectToRoute($this->aplic?->getRuta() ?? 'intranet_inicio');
        } elseif (Estado::BORRADOR !== $cuestionario->getEstado()?->getNombre()) {
            $this->addFlash('warning', 'El cuestionario no puede ser modificado.');

            return $this->redirectToRoute($this->aplic?->getRuta() ?? 'intranet_inicio');
        } elseif (!array_key_exists($tipo, $tipos)) {
            $this->addFlash('warning', 'El tipo de pregunta no existe.');

            return $this->redirectToRoute($this->aplic?->getRuta() ?? 'intranet_inicio');
        }

        $pregunta = new Pregunta();
        $pregunta
            ->setGrupo($grupo)
            ->setTipo($tipo)
            ->setOpciones($this->opciones[$tipo])
        ;
        $form = $this->createForm(PreguntaType::class, $pregunta, [
            'tipos' => $tipos,
        ]);
        $form
            ->add('reducida', null, [
                'label' => 'Solo en cuestionario reducido',
                'help' => 'Marcar para que la pregunta se incluya solo en el cuestionario reducido del tercer agente evaluador.',
            ])
            ->remove('orden')
            ->remove('opcional')
        ;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pregunta->setOrden(-1);
            $this->preguntaRepository->save($pregunta, true);
            $this->generator->logAndFlash('info', 'Nueva pregunta de desempeño', [
                'id' => $cuestionario->getId(),
                'cuestionario' => $cuestionario->getCodigo(),
                'pregunta' => $pregunta->getCodigo(),
            ]);

            return $this->redirectToRoute(
                sprintf('%s_admin_pregunta_index', $this->aplic?->getRuta() ?? ''),
                ['cuestionario' => $cuestionario->getId(), 'grupo' => $grupo->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render(sprintf('%s/admin/pregunta/new.html.twig', $this->aplic?->rutaToTemplateDir() ?? ''), [
            'cuestionario' => $cuestionario,
            'grupo' => $grupo,
            'pregunta' => $pregunta,
            'tipos' => $tipos,
            'opciones' => $this->opciones,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        path: '/intranet/desempenyo/admin/cuestionario/{cuestionario}/grupo/{grupo}/pregunta/{pregunta}/edit',
        name: 'intranet_desempenyo_admin_pregunta_edit',
        defaults: ['titulo' => 'Editar Pregunta de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function edit(Request $request, Cuestionario $cuestionario, Grupo $grupo, Pregunta $pregunta): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $tipos = $this->cargarTipos();
        if (!$this->checkAcceso($cuestionario, $grupo, $pregunta)) {
            return $this->redirectToRoute($this->aplic?->getRuta() ?? 'intranet_inicio');
        } elseif (Estado::BORRADOR !== $cuestionario->getEstado()?->getNombre()) {
            $this->addFlash('warning', 'El cuestionario no puede ser modificado.');

            return $this->redirectToRoute($this->aplic?->getRuta() ?? 'intranet_inicio');
        } elseif (!array_key_exists($pregunta->getTipo(), $tipos)) {
            $this->addFlash('warning', 'El tipo de pregunta no es válido.');

            return $this->redirectToRoute($this->aplic?->getRuta() ?? 'intranet_inicio');
        }

        $form = $this->createForm(PreguntaType::class, $pregunta, [
            'tipos' => $tipos,
        ]);
        $form
            ->add('reducida', null, [
                'label' => 'Solo en cuestionario reducido',
                'help' => 'Marcar para que la pregunta se incluya solo en el cuestionario reducido del tercer agente evaluador.',
            ])
            ->remove('orden')
            ->remove('opcional')
        ;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->preguntaRepository->save($pregunta, true);
            $this->generator->logAndFlash('info', 'Pregunta de desempeño modificada', [
                'id' => $cuestionario->getId(),
                'cuestionario' => $cuestionario->getCodigo(),
                'pregunta' => $pregunta->getCodigo(),
            ]);

            return $this->redirectToRoute(
                sprintf('%s_admin_pregunta_index', $this->aplic?->getRuta() ?? ''),
                ['cuestionario' => $cuestionario->getId(), 'grupo' => $grupo->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render(sprintf('%s/admin/pregunta/edit.html.twig', $this->aplic?->rutaToTemplateDir() ?? ''), [
            'cuestionario' => $cuestionario,
            'grupo' => $grupo,
            'pregunta' => $pregunta,
            'tipos' => $tipos,
            'opciones' => $this->opciones,
            'form' => $form->createView(),
        ]);
    }

    /** Comprobar si los parámetros son correctos para acceder al controlador. */
    private function checkAcceso(Cuestionario $cuestionario, ?Grupo $grupo = null, ?Pregunta $pregunta = null): bool
    {
        if (!$this->aplic instanceof Aplicacion && $cuestionario->getAplicacion() !== $this->aplic) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return false;
        } elseif ($grupo instanceof Grupo && $grupo->getCuestionario() !== $cuestionario) {
            $this->addFlash('warning', 'El grupo no corresponde al cuestionario especificado.');

            return false;
        } elseif ($grupo instanceof Grupo && $pregunta instanceof Pregunta && $pregunta->getGrupo() !== $grupo) {
            $this->addFlash('warning', 'La pregunta no corresponde al grupo especificado.');

            return false;
        }

        return true;
    }

    /**
     * Cargar configuración de tipos de preguntas.
     * @return array<array-key, int[]|bool[]>
     */
    private function cargarTipos(): array
    {
        /** @var array<array-key, int[]|bool[]> $tipos */
        $tipos = json_decode(
            (string) $this->forward('\App\Controller\Cuestiona\PreguntaController::getTipos')->getContent(),
            true
        );

        return array_intersect_key($tipos, $this->opciones);
    }
}


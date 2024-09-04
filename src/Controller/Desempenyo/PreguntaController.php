<?php

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Grupo;
use App\Entity\Cuestiona\Pregunta;
use App\Repository\Cuestiona\PreguntaRepository;
use App\Service\RutaActual;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador para gestionar preguntas para cuestionarios.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class PreguntaController extends AbstractController
{
    public function __construct(
        private readonly RutaActual $actual,
        private readonly PreguntaRepository $preguntaRepository,
    ) {
    }

    #[Route(
        path: ['/intranet/desempenyo/admin/cuestionario/{cuestionario}/grupo/{grupo}/pregunta', '/{cuestionario}/pregunta'],
        name: 'intranet_desempenyo_admin_pregunta_index',
        defaults: ['titulo' => 'Preguntas'],
        methods: ['GET']
    )]
    public function index(Cuestionario $cuestionario, ?Grupo $grupo): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $aplic = $this->actual->getAplicacion();
        if ($cuestionario->getAplicacion() !== $this->actual->getAplicacion()) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return $this->redirectToRoute($aplic?->getRuta() ?? 'intranet_inicio');
        } elseif ($grupo instanceof Grupo && $grupo->getCuestionario() !== $cuestionario) {
            $this->addFlash('warning', 'El grupo no corresponde al cuestionario especificado.');

            return $this->redirectToRoute($aplic?->getRuta() ?? 'intranet_inicio');
        }

        // Preguntas de un grupo o de cuestionario completo
        $preguntas = $grupo instanceof Grupo ? $grupo->getPreguntas() : $this->preguntaRepository->findByCuestionario(
            $cuestionario
        );
        // Opciones para las preguntas por defecto
        /** @var array<array-key, int[]|bool[]> $opciones */
        $opciones = [
            Pregunta::RANGO => [
                'min' => 1,
                'max' => 10,
                'observaciones' => true,
            ]
        ];

        return $this->render(sprintf('%s/admin/pregunta/index.html.twig', $aplic?->rutaToTemplateDir() ?? ''), [
            'cuestionario' => $cuestionario,
            'grupo' => $grupo,
            'preguntas' => $preguntas,
            'opciones' => $opciones,
        ]);
    }
}

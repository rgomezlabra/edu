<?php

namespace App\Controller\Cuestiona;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Grupo;
use App\Entity\Cuestiona\Pregunta;
use App\Form\Cuestiona\OpcionesNumeroType;
use App\Form\Cuestiona\OpcionesRangoType;
use App\Repository\Cuestiona\PreguntaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador para gestionar preguntas para cuestionarios.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[Route(path: '/desempenyo/admin/cuestionario', name: 'desempenyo_admin_pregunta_')]
class PreguntaController extends AbstractController
{
    /** @var array<array-key, string[]|bool[]> $tipos */
    private array $tipos = [
        Pregunta::NUMERO => [
            'clase' => OpcionesNumeroType::class,
            'leyenda' => 'Número',
            'fichero' => 'numero',
            'etiqueta' => 'Respuesta única',
            'dinamico' => false,
        ],
        Pregunta::RANGO => [
            'clase' => OpcionesRangoType::class,
            'leyenda' => 'Rango numérico',
            'fichero' => 'rango',
            'etiqueta' => 'Respuesta única',
            'dinamico' => false,
        ],
    ];

    public function __construct(private readonly PreguntaRepository $preguntaRepository)
    {
    }

    /** Reordena las preguntas dentro un grupo de un cuestionario según los datos recibidos. */
    #[Route(
        path: '/{cuestionario}/grupo/{grupo}/pregunta/orden',
        name: 'orden',
        methods: ['POST']
    )]
    public function ordenAjax(Request $request, Cuestionario $cuestionario, Grupo $grupo): Response
    {
        $this->denyAccessUnlessGranted('admin');
        if (!$request->isXmlHttpRequest() || !$request->request->has('orden')) {
            return $this->json(['mensaje' => 'Petición incorrecta.'], Response::HTTP_BAD_REQUEST);
        } elseif ($grupo->getCuestionario() !== $cuestionario) {
            return $this->json(['mensaje' => 'Datos incorrectos.'], Response::HTTP_BAD_REQUEST);
        }

        $preguntas = $grupo->getPreguntas();
        /** @var int[] $datos */
        $datos = $request->request->all()['orden'] ?? [];
        if (count($preguntas) !== count($datos)) {
            return $this->json(['mensaje' => 'Datos incorrectos.'], Response::HTTP_BAD_REQUEST);
        }

        $i = 0;
        foreach ($datos as $dato) {
            $pregunta = $this->preguntaRepository->find($dato);
            if ($pregunta instanceof Pregunta && $preguntas->contains($pregunta)) {
                $pregunta->setOrden($i);
                $this->preguntaRepository->save($pregunta, true);
                ++$i;
            }
        }

        return $this->json($i);
    }

    /** Mostrar los datos predefinidos para los tipos de preguntas (usada en plantillas). */
    public function getTipos(): Response
    {
        return $this->json($this->tipos);
    }
}

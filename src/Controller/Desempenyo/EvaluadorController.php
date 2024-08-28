<?php

declare(strict_types=1);

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Repository\Desempenyo\EvaluaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/intranet/desempenyo/admin/cuestionario', name: 'intranet_desempenyo_admin_cuestionario_')]
class EvaluadorController extends AbstractController
{
    public function __construct(
        private readonly EvaluaRepository $evaluaRepository,
    ) {
    }

    #[Route(
        path: '/{id}/evaluador/',
        name: 'evaluador_index',
        defaults: ['titulo' => 'Evaluadores de Cuestionario de Competencias'],
        methods: ['GET']
    )]
    public function index(Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        $evaluaciones = $this->evaluaRepository->findByEvaluacion($cuestionario, EvaluaRepository::EVALUACION);

        return $this->render('intranet/desempenyo/admin/evaluador/index.html.twig', [
            'cuestionario' => $cuestionario,
            'evaluaciones' => $evaluaciones,
        ]);
    }
}

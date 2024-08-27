<?php

declare(strict_types=1);

namespace App\Controller\Desempenyo;

use App\Entity\Sistema\Estado;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Sistema\EstadoRepository;
use App\Service\RutaActual;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('intranet/desempenyo/evalua', name: 'intranet_desempenyo_evalua_')]
class EvaluaController extends AbstractController
{
    public function __construct(
        private readonly RutaActual $actual,
        private readonly CuestionarioRepository $cuestionarioRepository,
        private readonly EstadoRepository $estadoRepository,
    ) {
    }

    #[Route(
        path: '/',
        name: 'index',
        defaults: ['titulo' => 'Evaluar Competencias'],
        methods: ['GET']
    )]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        if (!$this->checkActivos()) {
            return $this->redirectToRoute($this->actual->getAplicacion()?->getRuta() ?? '/');
        }

        return $this->render('intranet/desempenyo/evalua/index.html.twig');
    }

    private function checkActivos(): bool
    {
        if (0 === $this->cuestionarioRepository->count([
                'aplicacion' => $this->actual->getAplicacion(),
                'estado' => $this->estadoRepository->findOneBy(['nombre' => Estado::PUBLICADO]),
            ])) {
            $this->addFlash('warning', 'No hay cuestionarios de evaluación activos.');

            return false;
        }

        return true;
    }
}

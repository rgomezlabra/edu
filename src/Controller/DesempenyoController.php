<?php

namespace App\Controller\Intranet;

use App\Entity\Sistema\Estado;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Sistema\EstadoRepository;
use App\Service\RutaActual;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: 'intranet/desempenyo', name: 'intranet_desempenyo')]
class DesempenyoController extends AbstractController
{
    #[Route(
        path: '/',
        name: ''
    )]
    public function inicio(): Response
    {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);

        return $this->render('intranet/desempenyo/index.html.twig');
    }

    #[Route(
        path: '/admin',
        name: '_admin',
        defaults: ['titulo' => 'Administración de Evaluación de Desempeño'],
        methods: ['GET']
    )]
    public function admin(
        RutaActual             $actual,
        CuestionarioRepository $cuestionarioRepository,
        EstadoRepository       $estadoRepository,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        if (0 === $cuestionarioRepository->count([
                'aplicacion' => $actual->getAplicacion(),
                'estado' => $estadoRepository->findOneBy(['nombre' => Estado::PUBLICADO]),
            ])) {
            $this->addFlash('warning', 'No hay cuestionarios de evaluación activos.');
        }

        return $this->render('intranet/desempenyo/admin/index.html.twig');
    }
}

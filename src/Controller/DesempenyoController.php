<?php

namespace App\Controller\Intranet;

use App\Entity\Sistema\Estado;
use App\Entity\Sistema\Usuario;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Sistema\EstadoRepository;
use App\Service\RutaActual;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: 'intranet/desempenyo',
    name: 'intranet_desempenyo'
)]
class DesempenyoController extends AbstractController
{
    #[Route(
        path: '/',
        name: ''
    )]
    public function inicio(RutaActual $actual): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        if (true !== $actual->getAplicacion()?->getRelaciones()->reduce(
            function ($valor, $relacion) use ($usuario) { return $usuario->getRelaciones()->contains($relacion); })
        ) {
            $this->addFlash('warning', 'Sin permiso para acceder a la aplicación de Evaluación de Desempeño');

            return $this->redirectToRoute('intranet');
        }

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
    ): Response
    {
        $this->denyAccessUnlessGranted('admin');
        if (0 === $cuestionarioRepository->count([
                'aplicacion' => $actual->getAplicacion(),
                'estado' => $estadoRepository->findOneBy(['nombre' => Estado::PUBLICADO]),
            ])) {
            $this->addFlash('warning', 'No hay cuestionarios de evaluación activos.');
        }

        return $this->render('intranet/desempenyo/admin/index.html.twig');
    }

    #[Route(
        path: '/admin',
        name: '_evalua',
        defaults: ['titulo' => 'Evaluador de Competencias'],
        methods: ['GET']
    )]
    public function evaluador(): Response
    {
        $this->denyAccessUnlessGranted('evalua');

        return $this->render('intranet/desempenyo/evalua/index.html.twig');
    }
}

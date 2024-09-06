<?php

namespace App\Controller\Intranet;

use App\Entity\Sistema\Estado;
use App\Entity\Sistema\Usuario;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Desempenyo\EvaluaRepository;
use App\Repository\Desempenyo\FormularioRepository;
use App\Repository\Plantilla\EmpleadoRepository;
use App\Repository\Sistema\EstadoRepository;
use App\Service\RutaActual;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: 'intranet/desempenyo', name: 'intranet_desempenyo')]
class DesempenyoController extends AbstractController
{
    public function __construct(
        protected readonly RutaActual $actual,
    ) {
    }

    #[Route(
        path: '/',
        name: ''
    )]
    public function inicio(
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
        EvaluaRepository       $evaluaRepository,
        FormularioRepository   $formularioRepository,
    ): Response
    {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $empleado = $empleadoRepository->findOneByUsuario($usuario);

        return $this->render('intranet/desempenyo/index.html.twig', [
            'cuestionarios' => $cuestionarioRepository->findBy(['aplicacion' => $this->actual->getAplicacion()]),
            'evaluados' => $evaluaRepository->findBy(['empleado' => $empleado]),
            'evaluaciones' => $evaluaRepository->findBy(['evaluador' => $empleado]),
            'formularios' => $formularioRepository->findBy(['empleado' => $empleado]),
        ]);
    }

    #[Route(
        path: '/admin',
        name: '_admin',
        defaults: ['titulo' => 'Administración de Evaluación de Desempeño'],
        methods: ['GET']
    )]
    public function admin(
        CuestionarioRepository $cuestionarioRepository,
        EstadoRepository       $estadoRepository,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        if (0 === $cuestionarioRepository->count([
                'aplicacion' => $this->actual->getAplicacion(),
                'estado' => $estadoRepository->findOneBy(['nombre' => Estado::PUBLICADO]),
            ])) {
            $this->addFlash('warning', 'No hay cuestionarios de evaluación activos.');
        }

        return $this->render('intranet/desempenyo/admin/index.html.twig');
    }
}

<?php

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Sistema\Usuario;
use App\Form\Cuestiona\CuestionarioType;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Sistema\EstadoRepository;
use App\Service\MessageGenerator;
use App\Service\RutaActual;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador para gestionar cuestionarios para evaluación de competencias.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[Route(path: '/intranet/desempenyo/admin/cuestionario', name: 'intranet_desempenyo_admin_cuestionario_')]
class CuestionarioController extends AbstractController
{
    public function __construct(
        private readonly MessageGenerator       $generator,
        private readonly RutaActual             $actual,
        private readonly CuestionarioRepository $cuestionarioRepository,
    ) {
    }

    #[Route(
        path: '/',
        name: 'index',
        defaults: ['titulo' => 'Cuestionarios de Evaluación de Competencias'],
        methods: ['GET']
    )]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('admin');

        return $this->render('intranet/desempenyo/admin/cuestionario/index.html.twig', [
            'cuestionarios' => $this->cuestionarioRepository->findBy(['aplicacion' => $this->actual->getAplicacion()]),
        ]);
    }

    #[Route(
        path: '/new',
        name: 'new',
        defaults: ['titulo' => 'Nuevo Cuestionario de Evaluación de Competencias'],
        methods: ['GET', 'POST']
    )]
    public function new(Request $request, EstadoRepository $estadoRepository): Response
    {
        $this->denyAccessUnlessGranted('admin');
        /** @var Usuario $autor */
        $autor = $this->getUser();
        $cuestionario = new Cuestionario();
        $cuestionario
            ->setEstado($estadoRepository->findOneBy(['nombre' => 'Borrador']))
            ->setAplicacion($this->actual->getAplicacion())
            ->setAutor($autor)
        ;
        $form = $this->createForm(CuestionarioType::class, $cuestionario, [
            'de_aplicacion' => true,
            'con_fechas' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->cuestionarioRepository->save($cuestionario, true);
            $this->generator->logAndFlash('info', 'Nuevo cuestionario de evaluación', [
                'id' => $cuestionario->getId(),
                'codigo' => $cuestionario->getCodigo(),
            ]);

            return $this->redirectToRoute('intranet_desempenyo_admin_cuestionario_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('intranet/desempenyo/admin/cuestionario/new.html.twig', [
            'cuestionario' => $cuestionario,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        path: '/{id}',
        name: 'show',
        defaults: ['titulo' => 'Cuestionario de Evaluación de Competencias'],
        methods: ['GET']
    )]
    public function show(Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted('admin');
        if ($cuestionario->getAplicacion() !== $this->actual->getAplicacion()) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return $this->redirectToRoute($this->actual->getAplicacion()?->getRuta() ?? '/');
        }

        return $this->render('intranet/desempenyo/admin/cuestionario/show.html.twig', [
            'cuestionario' => $cuestionario,
        ]);
    }

    #[Route(
        path: '/{id}/edit',
        name: 'edit',
        defaults: ['titulo' => 'Editar Cuestionario de Evaluación de Competencias'],
        methods: ['GET', 'POST']
    )]
    public function edit(Request $request, Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted('admin');
        if ($cuestionario->getAplicacion() !== $this->actual->getAplicacion()) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return $this->redirectToRoute($this->actual->getAplicacion()?->getRuta() ?? '/');
        }

        $form = $this->createForm(CuestionarioType::class, $cuestionario, [
            'de_aplicacion' => true,
            'con_fechas' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // TODO revisar condiciones (por ejemplo fechas y estados)
            /** @var Usuario $autor */
            $autor = $this->getUser();
            $cuestionario->setAutor($autor);
            $this->cuestionarioRepository->save($cuestionario, true);
            $this->generator->logAndFlash('info', 'Cuestionario de evaluación modificado', [
                'id' => $cuestionario->getId(),
                'codigo' => $cuestionario->getCodigo(),
            ]);

            return $this->redirectToRoute('intranet_desempenyo_admin_cuestionario_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('intranet/desempenyo/admin/cuestionario/edit.html.twig', [
            'cuestionario' => $cuestionario,
            'form' => $form->createView(),
        ]);
    }
}

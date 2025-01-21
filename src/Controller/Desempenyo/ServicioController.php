<?php

namespace App\Controller\Desempenyo;

use App\Entity\Desempenyo\Servicio;
use App\Form\Desempenyo\ServicioType;
use App\Repository\Desempenyo\ServicioRepository;
use App\Service\MessageGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/desempenyo/admin/servicio', name: 'desempenyo_admin_servicio_')]
class ServicioController extends AbstractController
{
    public function __construct(private readonly MessageGenerator $generator)
    {
    }

    #[Route(
        path: '/',
        name: 'index',
        defaults: ['titulo' => 'Muestra los datos de los servicios definidos'],
        methods: ['GET']
    )]
    public function index(ServicioRepository $servicioRepository): Response
    {
        $this->denyAccessUnlessGranted('admin');

        return $this->render('desempenyo/admin/servicio/index.html.twig', [
            'servicios' => $servicioRepository->findAll(),
        ]);
    }

    #[Route(
        path: '/new',
        name: 'new',
        defaults: ['titulo' => 'Definir nuevo Servicio'],
        methods: ['GET', 'POST']
    )]
    public function new(Request $request, ServicioRepository $servicioRepository): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $servicio = new Servicio();
        $form = $this->createForm(ServicioType::class, $servicio);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $servicioRepository->save($servicio, true);
            $this->generator->logAndFlash('info', 'Nuevo servicio creado.', [
                'id' => $servicio->getId(),
                'codigo' => $servicio->getNombre(),
            ]);

            return $this->redirectToRoute('desempenyo_admin_servicio_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('desempenyo/admin/servicio/new.html.twig', [
            'servicio' => $servicio,
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/{id}',
        name: 'show',
        defaults: ['titulo' => 'Muestra los datos del servicio seleccionado'],
        methods: ['GET']
    )]
    public function show(Servicio $servicio): Response
    {
        $this->denyAccessUnlessGranted('admin');
        return $this->render('desempenyo/admin/servicio/show.html.twig', [
            'servicio' => $servicio,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request            $request,
        ServicioRepository $servicioRepository,
        Servicio           $servicio,
    ): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $form = $this->createForm(ServicioType::class, $servicio);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $servicioRepository->save($servicio, true);
            $this->generator->logAndFlash('info', 'Servicio modificado', [
                'id' => $servicio->getId(),
                'codigo' => $servicio->getNombre(),
            ]);

            return $this->redirectToRoute('desempenyo_admin_servicio_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('desempenyo/admin/servicio/edit.html.twig', [
            'servicio' => $servicio->getId(),
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/{id}',
        name: 'delete',
        defaults: ['titulo' => 'Eliminar servicio'],
        methods: ['POST']
    )]
    public function delete(
        Request            $request,
        ServicioRepository $servicioRepository,
        Servicio           $servicio,
    ): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $id = $servicio->getId();
        $nombre = $servicio->getNombre();
        if ($this->isCsrfTokenValid('delete' . (int)$id, (string)$request->request->get('_token'))) {
            $servicioRepository->remove($servicio, true);
            $this->generator->logAndFlash('info', 'Servicio eliminado', [
                'id' => $id,
                'nombre' => $nombre,
            ]);
        }
        return $this->redirectToRoute('desempenyo_admin_servicio_index', [], Response::HTTP_SEE_OTHER);
    }
}

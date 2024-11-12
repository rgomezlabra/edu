<?php

namespace App\Controller\Desempenyo;

use App\Entity\Desempenyo\TipoIncidencia;
use App\Form\Desempenyo\TipoIncidenciaType;
use App\Repository\Desempenyo\TipoIncidenciaRepository;
use App\Service\MessageGenerator;
use App\Service\RutaActual;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador para gestionar tipos de incidencias para evaluación de desempeño.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[Route(path: '/intranet/desempenyo/admin/tipo_incidencia', name: 'intranet_desempenyo_admin_tipo_incidencia_')]
class TipoIncidenciaController extends AbstractController
{
    /** @var string $rutaBase Ruta base de la aplicación actual */
    private readonly string $rutaBase;

    public function __construct(
        private readonly MessageGenerator         $generator,
        private readonly RutaActual               $actual,
        private readonly TipoIncidenciaRepository $tipoRepository,
    ) {
        $this->rutaBase = $this->actual->getAplicacion()?->getRuta() ?? 'intranet_inicio';
    }

    #[Route(
        path: '/',
        name: 'index',
        defaults: ['titulo' => 'Tipos de Incidencias para Evaluación de Desempeño'],
        methods: ['GET']
    )]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('admin');

        return $this->render('intranet/desempenyo/admin/tipo_incidencia/index.html.twig', [
            'tipos' => $this->tipoRepository->findAll(),
        ]);
    }

    #[Route(
        path: '/new',
        name: 'new',
        defaults: ['titulo' => 'Nuevo Tipo de Incidencias para Evaluación de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $tipo = new TipoIncidencia();
        $form = $this->createForm(TipoIncidenciaType::class, $tipo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tipoRepository->save($tipo, true);
            $this->generator->logAndFlash('info', 'Nuevo tipo de incidencias', [
                'id' => $tipo->getId(),
                'nombre' => $tipo->getNombre(),
            ]);

            return $this->redirectToRoute($this->rutaBase . '_admin_tipo_incidencia_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('intranet/desempenyo/admin/tipo_incidencia/new.html.twig', [
            'tipo' => $tipo,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        path: '/{id}',
        name: 'show',
        defaults: ['titulo' => 'Tipo de Incidencias para Evaluación de Desempeño'],
        methods: ['GET']
    )]
    public function show(TipoIncidencia $tipo): Response
    {
        $this->denyAccessUnlessGranted('admin');

        return $this->render('intranet/desempenyo/admin/tipo_incidencia/show.html.twig', [
            'tipo' => $tipo,
        ]);
    }

    #[Route(
        path: '/{id}/edit',
        name: 'edit',
        defaults: ['titulo' => 'Editar Tipo de Incidencias para Evaluación de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function edit(Request $request, TipoIncidencia $tipo): Response
    {
        $this->denyAccessUnlessGranted('admin');

        $form = $this->createForm(TipoIncidenciaType::class, $tipo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tipoRepository->save($tipo, true);
            $this->generator->logAndFlash('info', 'Tipo de incidencias modificado', [
                'id' => $tipo->getId(),
                'nombre' => $tipo->getNombre(),
            ]);

            return $this->redirectToRoute($this->rutaBase . '_admin_tipo_incidencia_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('intranet/desempenyo/admin/tipo_incidencia/edit.html.twig', [
            'tipo' => $tipo,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        path: '/{id}',
        name: 'delete',
        defaults: ['titulo' => 'Eliminar Tipo de Incidencias para Evaluación de Desempeño'],
        methods: ['POST']
    )]
    public function delete(Request $request, TipoIncidencia $tipo): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $id = $tipo->getId();
        if ($this->isCsrfTokenValid('delete' . (int) $id, $request->request->getString('_token'))) {
            $this->tipoRepository->remove($tipo, true);
            $this->generator->logAndFlash('info', 'Tipo de incidencias eliminado', [
                'id' => $id,
                'nombre' => $tipo->getNombre(),
            ]);
        }

        return $this->redirectToRoute($this->rutaBase . '_admin_tipo_incidencia_index', [], Response::HTTP_SEE_OTHER);
    }
}

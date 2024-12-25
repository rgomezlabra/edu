<?php

namespace App\Controller\Desempenyo;

use App\Entity\Desempenyo\TipoIncidencia;
use App\Form\Desempenyo\TipoIncidenciaType;
use App\Repository\Desempenyo\IncidenciaRepository;
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
#[Route(path: '/desempenyo/admin/tipo_incidencia', name: 'desempenyo_admin_tipo_incidencia_')]
class TipoIncidenciaController extends AbstractController
{
    /** @var string $rutaBase Ruta base de la aplicación actual */
    private readonly string $rutaBase;

    public function __construct(
        private readonly MessageGenerator         $generator,
        private readonly RutaActual               $actual,
        private readonly TipoIncidenciaRepository $tipoRepository,
    ) {
        $this->rutaBase = $this->actual->getRuta() ?? 'inicio';
    }

    #[Route(
        path: '/',
        name: 'index',
        defaults: ['titulo' => 'Tipos de Incidencias para Evaluación de Desempeño'],
        methods: ['GET']
    )]
    public function index(IncidenciaRepository $incidenciaRepository): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $numIncidencias = [];
        $tipos = $this->tipoRepository->findAll();
        foreach ($tipos as $tipo) {
            $numIncidencias[$tipo->getId() ?? 0] = $incidenciaRepository->count(['tipo' => $tipo]);
        }

        return $this->render('desempenyo/admin/tipo_incidencia/index.html.twig', [
            'tipos' => $this->tipoRepository->findAll(),
            'num_incidencias' => $numIncidencias,
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

        return $this->render('desempenyo/admin/tipo_incidencia/new.html.twig', [
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
    public function show(IncidenciaRepository $incidenciaRepository, TipoIncidencia $tipo): Response
    {
        $this->denyAccessUnlessGranted('admin');

        return $this->render('desempenyo/admin/tipo_incidencia/show.html.twig', [
            'tipo' => $tipo,
            'num_incidencias' => $incidenciaRepository->count(['tipo' => $tipo]),
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

        return $this->render('desempenyo/admin/tipo_incidencia/edit.html.twig', [
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
    public function delete(Request $request, IncidenciaRepository $incidenciaRepository, TipoIncidencia $tipo): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $id = $tipo->getId();
        if ($this->isCsrfTokenValid('delete' . (int) $id, $request->request->getString('_token'))) {
            if ($incidenciaRepository->count(['tipo' => $tipo]) > 0) {
                $this->generator->logAndFlash('error', 'No se puede eliminar un tipo con incidencias', [
                    'id' => $id,
                    'nombre' => $tipo->getNombre(),
                ]);
            } else {
                $this->tipoRepository->remove($tipo, true);
                $this->generator->logAndFlash('info', 'Tipo de incidencias eliminado', [
                    'id' => $id,
                    'nombre' => $tipo->getNombre(),
                ]);
            }
        }

        return $this->redirectToRoute($this->rutaBase . '_admin_tipo_incidencia_index', [], Response::HTTP_SEE_OTHER);
    }
}

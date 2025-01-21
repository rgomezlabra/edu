<?php

namespace App\Controller\Cuestiona;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Grupo;
use App\Form\Cuestiona\GrupoType;
use App\Repository\Cuestiona\GrupoRepository;
use App\Service\MessageGenerator;
use App\Service\RutaActual;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador para gestionar grupos de preguntas para cuestionarios.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[Route(path: '/desempenyo/admin/cuestionario', name: 'desempenyo_admin_grupo_')]
class GrupoController extends AbstractController
{
    public function __construct(
        private readonly MessageGenerator $generator,
        private readonly RutaActual       $actual,
        private readonly GrupoRepository  $grupoRepository,
    ) {
    }

    #[Route(
        path: '/{id}/grupo/',
        name: 'index',
        defaults: ['titulo' => 'Grupos de Preguntas'],
        methods: ['GET']
    )]
    public function index(Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted('admin');

        return $this->render('desempenyo/admin/grupo/index.html.twig', [
            'cuestionario' => $cuestionario,
            'grupos' => $this->grupoRepository->findBy(['cuestionario' => $cuestionario], ['orden' => 'ASC']),
        ]);
    }

    #[Route(
        path: '/{id}/grupo/new',
        name: 'new',
        defaults: ['titulo' => 'Nuevo Grupo de Preguntas'],
        methods: ['GET', 'POST']
    )]
    public function new(Request $request, Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted('admin');

        $grupo = new Grupo();
        $grupo->setCuestionario($cuestionario);

        $form = $this->createForm(GrupoType::class, $grupo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->grupoRepository->save($grupo, true);
            $this->generator->logAndFlash('info', 'Nuevo grupo de preguntas.', [
                'id' => $cuestionario->getId(),
                'cuestionario' => $cuestionario->getCodigo(),
            ]);

            return $this->redirectToRoute(
                'desempenyo_admin_grupo_index',
                ['id' => $cuestionario->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('desempenyo/admin/grupo/new.html.twig', [
            'cuestionario' => $cuestionario,
            'grupo' => $grupo,
            'form' => $form->createView(),
        ]);
    }

    /** Reordena los grupos de un cuestionario según los datos recibidos. */
    #[Route(
        path: '/{id}/grupo/orden',
        name: 'orden',
        methods: ['POST']
    )]
    public function ordenAjax(Request $request, Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted('admin');
        if (!$request->isXmlHttpRequest() || !$request->request->has('orden')) {
            return $this->json(['mensaje' => 'Petición incorrecta.'], Response::HTTP_BAD_REQUEST);
        }

        $grupos = $cuestionario->getGrupos();
        /** @var int[] $datos */
        $datos = $request->request->all()['orden'] ?? [];
        if (count($grupos) !== count($datos)) {
            return $this->json(['mensaje' => 'Datos incorrectos.'], Response::HTTP_BAD_REQUEST);
        }

        $i = 0;
        foreach ($datos as $dato) {
            $grupo = $this->grupoRepository->find($dato);
            if ($grupo instanceof Grupo && $grupos->contains($grupo)) {
                $grupo->setOrden($i);
                $this->grupoRepository->save($grupo, true);
                ++$i;
            }
        }

        return $this->json($i);
    }

    #[Route(
        path: '/{cuestionario}/grupo/{grupo}',
        name: 'show',
        defaults: ['titulo' => 'Grupo de Preguntas'],
        methods: ['GET']
    )]
    public function show(Cuestionario $cuestionario, Grupo $grupo): Response
    {
        $this->denyAccessUnlessGranted('admin');
        if ($grupo->getCuestionario() !== $cuestionario) {
            $this->addFlash('warning', 'El grupo no corresponde al cuestionario especificado.');

            return $this->redirectToRoute($this->actual->getRuta());
        }

        return $this->render('desempenyo/admin/grupo/show.html.twig', [
            'cuestionario' => $cuestionario,
            'grupo' => $grupo,
        ]);
    }

    #[Route(
        path: '/{cuestionario}/grupo/{grupo}/edit',
        name: 'edit',
        defaults: ['titulo' => 'Editar Grupo de Preguntas'],
        methods: ['GET', 'POST']
    )]
    public function edit(Request $request, Cuestionario $cuestionario, Grupo $grupo): Response
    {
        $this->denyAccessUnlessGranted('admin');
        if ($grupo->getCuestionario() !== $cuestionario) {
            $this->addFlash('warning', 'El grupo no corresponde al cuestionario especificado.');

            return $this->redirectToRoute($this->actual->getRuta());
        }

        $form = $this->createForm(GrupoType::class, $grupo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->grupoRepository->save($grupo, true);
            $this->generator->logAndFlash('info', 'grupo de preguntas modificado', [
                'id' => $cuestionario->getId(),
                'cuestionario' => $cuestionario->getCodigo(),
            ]);

            return $this->redirectToRoute(
                'desempenyo_admin_grupo_index',
                ['id' => $cuestionario->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('desempenyo/admin/grupo/edit.html.twig', [
            'cuestionario' => $cuestionario,
            'grupo' => $grupo,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        path: '/{cuestionario}/grupo/{grupo}',
        name: 'delete',
        defaults: ['titulo' => 'Eliminar Grupo de Preguntas'],
        methods: ['POST']
    )]
    public function delete(Request $request, Cuestionario $cuestionario, Grupo $grupo): Response
    {
        $this->denyAccessUnlessGranted('admin');
        if ($grupo->getCuestionario() !== $cuestionario) {
            $this->addFlash('warning', 'El grupo no corresponde al cuestionario especificado.');

            return $this->redirectToRoute($this->actual->getRuta());
        }

        $id = $grupo->getId();
        if (count($grupo->getPreguntas()) > 0) {
            $this->generator->logAndFlash('error', 'No se puede eliminar un grupo con preguntas', [
                'id' => $id,
                'cuestionario' => $cuestionario->getCodigo(),
                'titulo' => $grupo->getTitulo(),
            ]);
        } elseif ($this->isCsrfTokenValid('delete' . (int) $id, (string) $request->request->get('_token'))) {
            $this->grupoRepository->remove($grupo, true);
            $this->generator->logAndFlash('info', 'Grupo de preguntas eliminado.', [
                'id' => $id,
                'cuestionario' => $cuestionario->getCodigo(),
            ]);
        }

        return $this->redirectToRoute('desempenyo_admin_grupo_index',
            ['id' => $cuestionario->getId()],
            Response::HTTP_SEE_OTHER
        );
    }
}

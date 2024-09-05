<?php

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Sistema\Usuario;
use App\Form\Cuestiona\CuestionarioType;
use App\Form\Cuestiona\PeriodoValidezType;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Sistema\EstadoRepository;
use App\Service\MessageGenerator;
use App\Service\RutaActual;
use App\Service\Slug;
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
        private readonly MessageGenerator $generator,
        private readonly RutaActual $actual,
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

            return $this->redirectToRoute($this->actual->getAplicacion()?->getRuta() ?? 'intranet_inicio');
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

            return $this->redirectToRoute($this->actual->getAplicacion()?->getRuta() ?? 'intranet_inicio');
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

    /** Activar formulario (estado publicado). */
    #[Route(
        path: '/{id}/activar',
        name: 'activar',
        defaults: ['titulo' => 'Activar Cuestionario'],
        methods: ['GET', 'POST']
    )]
    public function activar(Request $request, EstadoRepository $estadoRepository, Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted('admin');
        if ($cuestionario->getAplicacion() !== $this->actual->getAplicacion()) {
            $this->addFlash('warning', 'Sin acceso al cuestionario.');

            return $this->redirectToRoute($this->actual->getAplicacion()?->getRuta() ?? 'intranet_inicio');
        } elseif (0 === count($cuestionario->getGrupos())) {
            $this->addFlash('warning', 'El cuestionario no tiene preguntas definidas.');

            return $this->redirectToRoute('intranet_desempenyo_admin_cuestionario_show', ['id' => $cuestionario->getId()]);
        } elseif ($cuestionario->getGrupos()->filter(static fn ($grupo) => count($grupo->getPreguntas()) === 0)->count() > 0) {
            $this->addFlash('warning', 'El cuestionario tiene algún grupo de preguntas vacío.');

            return $this->redirectToRoute('intranet_desempenyo_admin_cuestionario_show', ['id' => $cuestionario->getId()]);
        }

        $form = $this->createForm(PeriodoValidezType::class, $cuestionario, [
            'con_fechas' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $cuestionario->getFechaAlta() || null === $cuestionario->getFechaBaja()) {
                $this->addFlash('warning', 'Las fechas inicial y final son obligatorias.');

                return $this->redirectToRoute((string) $request->attributes->get('_route'), [
                    'id' => $cuestionario->getId(),
                ]);
            } elseif ($cuestionario->getFechaAlta() > $cuestionario->getFechaBaja()) {
                $this->addFlash('warning', 'La fecha final debe ser posterior o igual a la fecha inicial.');

                return $this->redirectToRoute((string) $request->attributes->get('_route'), [
                    'id' => $cuestionario->getId(),
                ]);
            }

            $publicado = $estadoRepository->findOneBy(['nombre' => 'Publicado']);
            $cuestionario
                ->setEstado($publicado)
                ->setUrl(
                    sprintf(
                        '/%s/formulario/%s-%s',
                        $this->actual->getAplicacion()?->rutaToTemplateDir() ?? '',
                        (new Slug())((string) $cuestionario->getCodigo()),
                        uniqid()
                    )
                )
            ;
            $this->cuestionarioRepository->save($cuestionario, true);
            $this->generator->logAndFlash('info', 'Cuestionario activado', [
                'id' => $cuestionario->getId(),
                'codigo' => $cuestionario->getCodigo(),
            ]);

            return $this->render('intranet/desempenyo/admin/cuestionario/activo.html.twig', [
                'cuestionario' => $cuestionario,
            ]);
        }

        return $this->render('intranet/desempenyo/admin/cuestionario/edit.html.twig', [
            'cuestionario' => $cuestionario,
            'form' => $form->createView(),
        ]);
    }
}

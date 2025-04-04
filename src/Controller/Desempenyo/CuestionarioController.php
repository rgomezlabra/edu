<?php

namespace App\Controller\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Estado;
use App\Entity\Usuario;
use App\Form\Cuestiona\CuestionarioType;
use App\Form\Cuestiona\PeriodoValidezType;
use App\Form\Desempenyo\CuestionarioFechasType;
use App\Form\Desempenyo\CuestionarioPesosType;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\EstadoRepository;
use App\Service\MessageGenerator;
use App\Service\Slug;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador para gestionar cuestionarios para evaluación de desempeño.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[Route(path: '/desempenyo/admin/cuestionario', name: 'desempenyo_admin_cuestionario_')]
class CuestionarioController extends AbstractController
{
    public function __construct(
        private readonly MessageGenerator       $generator,
        private readonly CuestionarioRepository $cuestionarioRepository,
    ) {
    }

    #[Route(
        path: '/',
        name: 'index',
        defaults: ['titulo' => 'Cuestionarios de Evaluación de Desempeño'],
        methods: ['GET']
    )]
    public function index(EstadoRepository $estadoRepository): Response
    {
        $this->denyAccessUnlessGranted('admin');

        return $this->render('desempenyo/admin/cuestionario/index.html.twig', [
            'cuestionarios' => $this->cuestionarioRepository->findAll(),
            'estados' => $estadoRepository->findBy(['tipo' => Estado::SISTEMA]),
        ]);
    }

    #[Route(
        path: '/new',
        name: 'new',
        defaults: ['titulo' => 'Nuevo Cuestionario de Evaluación de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function new(Request $request, EstadoRepository $estadoRepository): Response
    {
        $this->denyAccessUnlessGranted('admin');
        /** @var Usuario $autor */
        $autor = $this->getUser();
        $cuestionario = new Cuestionario();
        $cuestionario
            ->setEstado($estadoRepository->findOneBy(['nombre' => Estado::BORRADOR]))
            ->setAutor($autor)
        ;
        $form = $this->createForm(CuestionarioType::class, $cuestionario, [
            'de_aplicacion' => true,
            'con_fechas' => true,
            'form_configuracion' => CuestionarioPesosType::class,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->cuestionarioRepository->save($cuestionario, true);
            $this->generator->logAndFlash('info', 'Nuevo cuestionario de desempeño', [
                'id' => $cuestionario->getId(),
                'codigo' => $cuestionario->getCodigo(),
            ]);

            return $this->redirectToRoute('desempenyo_admin_cuestionario_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('desempenyo/admin/cuestionario/new.html.twig', [
            'cuestionario' => $cuestionario,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        path: '/{id}',
        name: 'show',
        defaults: ['titulo' => 'Cuestionario de Evaluación de Desempeño'],
        methods: ['GET']
    )]
    public function show(Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted('admin');

        return $this->render('desempenyo/admin/cuestionario/show.html.twig', [
            'cuestionario' => $cuestionario,
        ]);
    }

    #[Route(
        path: '/{id}/edit',
        name: 'edit',
        defaults: ['titulo' => 'Editar Cuestionario de Evaluación de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function edit(Request $request, Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted('admin');
        if (Estado::BORRADOR !== $cuestionario->getEstado()?->getNombre()) {
            $this->addFlash('warning', 'El cuestionario no puede ser editado porque no es un borrador.');

            return $this->redirectToRoute('desempenyo');
        }

        $form = $this->createForm(CuestionarioType::class, $cuestionario, [
            'de_aplicacion' => true,
            'con_fechas' => true,
            'form_configuracion' => CuestionarioPesosType::class,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Usuario $autor */
            $autor = $this->getUser();
            $cuestionario->setAutor($autor);
            $this->cuestionarioRepository->save($cuestionario, true);
            $this->generator->logAndFlash('info', 'Cuestionario de desempeño modificado', [
                'id' => $cuestionario->getId(),
                'codigo' => $cuestionario->getCodigo(),
            ]);

            return $this->redirectToRoute('desempenyo_admin_cuestionario_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('desempenyo/admin/cuestionario/edit.html.twig', [
            'cuestionario' => $cuestionario,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        path: '/{id}/delete',
        name: 'delete',
        defaults: ['titulo' => 'Eliminar Cuestionario de Evaluación de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function delete(Request $request, Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $id = $cuestionario->getId();
        if (count($cuestionario->getGrupos()) > 0) {
            $this->generator->logAndFlash('error', 'No se puede eliminar un cuestionario con grupos definidos.', [
                'id' => $id,
                'codigo' => $cuestionario->getCodigo(),
            ]);
        } elseif ($this->isCsrfTokenValid('delete' . (int) $id, $request->request->getString('_token'))) {
            $this->cuestionarioRepository->remove($cuestionario, true);
            $this->generator->logAndFlash('info', 'Cuestionario eliminado', [
                'id' => $id,
                'codigo' => $cuestionario->getCodigo(),
            ]);
        }

        return $this->redirectToRoute('desempenyo_admin_cuestionario_index', [], Response::HTTP_SEE_OTHER);
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
        if (0 === count($cuestionario->getGrupos())) {
            $this->addFlash('warning', 'El cuestionario no tiene preguntas definidas.');

            return $this->redirectToRoute('desempenyo_admin_cuestionario_show', ['id' => $cuestionario->getId()]);
        } elseif ($cuestionario->getGrupos()->filter(static fn ($grupo) => count($grupo->getPreguntas()) === 0)->count() > 0) {
            $this->addFlash('warning', 'El cuestionario tiene algún grupo de preguntas vacío.');

            return $this->redirectToRoute('desempenyo_admin_cuestionario_show', ['id' => $cuestionario->getId()]);
        }

        $form = $this->createForm(PeriodoValidezType::class, $cuestionario, [
            'con_fechas' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$cuestionario->getFechaAlta() instanceof DateTimeImmutable
                || !$cuestionario->getFechaBaja() instanceof DateTimeImmutable) {
                $this->addFlash('warning', 'Las fechas inicial y final son obligatorias.');

                return $this->redirectToRoute($request->attributes->getString('_route'), [
                    'id' => $cuestionario->getId(),
                ]);
            } elseif ($cuestionario->getFechaAlta() > $cuestionario->getFechaBaja()) {
                $this->addFlash('warning', 'La fecha final debe ser posterior o igual a la fecha inicial.');

                return $this->redirectToRoute($request->attributes->getString('_route'), [
                    'id' => $cuestionario->getId(),
                ]);
            }

            $publicado = $estadoRepository->findOneBy(['nombre' => Estado::PUBLICADO]);
            $url = $cuestionario->getUrl() ?? sprintf(
                '/desempenyo/formulario/%s-%s',
                (new Slug())((string) $cuestionario->getCodigo()),
                uniqid()
            );
            $cuestionario
                ->setEstado($publicado)
                ->setUrl($url)
            ;
            $this->cuestionarioRepository->save($cuestionario, true);
            $this->generator->logAndFlash('info', 'Cuestionario activado', [
                'id' => $cuestionario->getId(),
                'codigo' => $cuestionario->getCodigo(),
            ]);

            return $this->render('desempenyo/admin/cuestionario/activo.html.twig', [
                'cuestionario' => $cuestionario,
            ]);
        }

        return $this->render('desempenyo/admin/cuestionario/edit.html.twig', [
            'cuestionario' => $cuestionario,
            'activar' => $cuestionario->getEstado()?->getNombre() === Estado::BORRADOR,
            'form' => $form->createView(),
        ]);
    }

    /** Desactivar formulario (estado borrador). */
    #[Route(
        path: '/{id}/desactivar',
        name: 'desactivar',
        defaults: ['titulo' => 'Desactivar Cuestionario'],
        methods: ['GET']
    )]
    public function desactivar(EstadoRepository $estadoRepository, Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $cuestionario->setEstado($estadoRepository->findOneBy(['nombre' => Estado::BORRADOR]));
        $this->cuestionarioRepository->save($cuestionario, true);

        return $this->render('desempenyo/admin/cuestionario/show.html.twig', [
            'cuestionario' => $cuestionario,
        ]);
    }


    /** Configurar fechas para mostrar resultados del cuestionario. */
    #[Route(
        path: '/{id}/fechas',
        name: 'fechas',
        defaults: ['titulo' => 'Fechas para Mostrar Resultados del Cuestionario'],
        methods: ['GET', 'POST']
    )]
    public function fechasResultados(Request $request, Cuestionario $cuestionario): Response
    {
        $this->denyAccessUnlessGranted('admin');
        if (Estado::PUBLICADO !== $cuestionario->getEstado()?->getNombre()) {
            $this->addFlash('warning', 'El cuestionario no está activo.');

            return $this->redirectToRoute('desempenyo');
        }

        $form = $this->createForm(CuestionarioType::class, $cuestionario, [
            'form_configuracion' => CuestionarioFechasType::class,
            'solo_configuracion' => true,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $error = '';
            /** @var array{provisional: DateTimeImmutable|null, definitiva: DateTimeImmutable|null} $config */
            $config = $cuestionario->getConfiguracion();
            $finPeriodo = $cuestionario->getFechaBaja();
            if (!$finPeriodo instanceof DateTimeImmutable) {
                $error = 'Periodo de validez del cuestionario no establecido.';
            } elseif (is_a($config['provisional'], 'DateTimeInterface')
                && $finPeriodo->format('Ymd') >= $config['provisional']->format('Ymd')
            ) {
                $error = sprintf(
                    'La fecha provisional debe ser posterior a %s.',
                    $finPeriodo->format('d/m/Y')
                );
            } elseif (is_a($config['provisional'], 'DateTimeInterface')
                && is_a($config['definitiva'], 'DateTimeInterface')
                && $config['provisional']->format('Ymd') > $config['definitiva']->format('Ymd')
            ) {
                $error = sprintf(
                    'La fecha definitiva debe ser igual o posterior a %s.',
                    $config['provisional']->format('d/m/Y')
                );
            }
            if ('' !== $error) {
                $this->addFlash('warning', $error);
                return $this->redirectToRoute($request->attributes->getString('_route'), [
                    'id' => $cuestionario->getId(),
                ]);
            }

            $this->cuestionarioRepository->save($cuestionario, true);
            $this->generator->logAndFlash('info', 'Fechas guardadas correctamente', [
                'id' => $cuestionario->getId(),
                'codigo' => $cuestionario->getCodigo(),
            ]);

            return $this->redirectToRoute('desempenyo_admin_cuestionario_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('desempenyo/admin/cuestionario/edit.html.twig', [
            'cuestionario' => $cuestionario,
            'form' => $form->createView(),
        ]);
    }
}

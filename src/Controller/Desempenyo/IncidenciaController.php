<?php

namespace App\Controller\Desempenyo;

use App\Entity\Cirhus\Incidencia as CirhusIncidencia;
use App\Entity\Cirhus\IncidenciaApunte;
use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Desempenyo\Incidencia;
use App\Entity\Sistema\Estado;
use App\Entity\Sistema\Usuario;
use App\Form\Cirhus\IncidenciaApunteType;
use App\Form\Desempenyo\IncidenciaType;
use App\Repository\Cirhus\IncidenciaApunteRepository;
use App\Repository\Cirhus\IncidenciaRepository as CirhusIncidenciaRepository;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Desempenyo\IncidenciaRepository;
use App\Repository\Sistema\EstadoRepository;
use App\Service\MessageGenerator;
use App\Service\RutaActual;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function Symfony\Component\String\u;

/**
 * Controlador para gestionar incidencias de evaluación de desempeño.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[Route(path: '/intranet/desempenyo', name: 'intranet_desempenyo_')]
class IncidenciaController extends AbstractController
{
    private string $rutaBase;

    public function __construct(
        private readonly MessageGenerator       $generator,
        private readonly CuestionarioRepository $cuestionarioRepository,
        private readonly IncidenciaRepository   $incidenciaRepository,
        private readonly RutaActual             $actual,
    ) {
        $this->rutaBase = $this->actual->getAplicacion()?->getRuta() ?? 'intranet';
    }

    #[Route(
        path: '/admin/incidencia/',
        name: 'admin_incidencia_index',
        defaults: ['titulo' => 'Incidencias para Evaluación de Desempeño'],
        methods: ['GET']
    )]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('admin');

        return $this->render('intranet/desempenyo/admin/incidencia/index.html.twig', [
            'incidencias' => $this->incidenciaRepository->findAll(),
        ]);
    }

    #[Route(
        path: '/formulario/{codigo}/incidencia',
        name: 'formulario_incidencia_index',
        requirements: ['codigo' => '[a-z0-9-]+'],
        defaults: ['titulo' => 'Mis Incidencias de Evaluación de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function indexUsuario(?string $codigo): Response
    {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        $cuestionario = $this->cuestionarioRepository->findOneBy(['codigo' => u($codigo)->beforeLast('-')]);
        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute($this->rutaBase);
        }

        return $this->render('intranet/desempenyo/incidencia_index.html.twig', [
            'codigo' => $codigo,
            'incidencias' => $this->incidenciaRepository->findByConectado($cuestionario),
        ]);
    }

    #[Route(
        path: '/formulario/{codigo}/incidencia/new',
        name: 'formulario_incidencia_new',
        requirements: ['codigo' => '[a-z0-9-]+'],
        defaults: ['titulo' => 'Nueva Incidencia de Evaluación de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function newUsuario(
        Request                    $request,
        RutaActual                 $actual,
        CirhusIncidenciaRepository $cirhusRepository,
        EstadoRepository           $estadoRepository,
        string                     $codigo,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        $cuestionario = $this->cuestionarioRepository->findOneBy(['codigo' => u($codigo)->beforeLast('-')]);
        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute($this->rutaBase);
        }

        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $cirhus = new CirhusIncidencia();
        $cirhus
            ->setAplicacion($actual->getAplicacion())
            ->setSolicitante($usuario)
        ;
        $incidencia = new Incidencia();
        $incidencia
            ->setCuestionario($cuestionario)
            ->setIncidencia($cirhus)
        ;
        $form = $this->createForm(IncidenciaType::class, $incidencia);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $iniciado = $estadoRepository->findOneBy(['nombre' => Estado::INICIADO]);
            $apunte = new IncidenciaApunte();
            $apunte
                ->setIncidencia($cirhus)
                ->setEstado($iniciado)
                ->setComentario('Solicitud')
                ->setAutor($usuario)
                ->setFechaInicio(new DateTimeImmutable('now'))
            ;
            $cirhus->addApunte($apunte);
            $cirhusRepository->save($cirhus);
            $this->incidenciaRepository->save($incidencia, true);
            $this->generator->logAndFlash('info', 'Nueva incidencia de evaluación de desempeño', [
                'id' => $incidencia->getId(),
                'cuestionario' => $codigo,
            ]);

            return $this->redirectToRoute(
                $this->rutaBase . '_formulario_incidencia_index',
                ['codigo' => $codigo],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('intranet/desempenyo/incidencia_edit.html.twig', [
            'incidencia' => $incidencia,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        path: '/formulario/{codigo}/incidencia/{id}/edit',
        name: 'formulario_incidencia_edit',
        requirements: ['codigo' => '[a-z0-9-]+'],
        defaults: ['titulo' => 'Editar Incidencia de Evaluación de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function editUsuario(
        Request          $request,
        EstadoRepository $estadoRepository,
        string           $codigo,
        Incidencia       $incidencia
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        $cuestionario = $this->cuestionarioRepository->findOneBy(['codigo' => u($codigo)->beforeLast('-')]);
        $iniciado = $estadoRepository->findOneBy(['nombre' => Estado::INICIADO]);
        $ultimo = $incidencia->getIncidencia()?->getApuntes()->last();
        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif ($incidencia->getCuestionario() !== $cuestionario) {
            $this->addFlash('warning', 'La incidencia no corresponde al cuestionario.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (!$ultimo instanceof IncidenciaApunte || $ultimo->getEstado() !== $iniciado) {
            $this->addFlash('warning', 'La incidencia está siendo tratada y no puede ser editada.');

            return $this->redirectToRoute($this->rutaBase);
        }

        $form = $this->createForm(IncidenciaType::class, $incidencia);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->incidenciaRepository->save($incidencia, true);
            $this->generator->logAndFlash('info', 'Incidencia de evaluación de desempeño editada', [
                'id' => $incidencia->getId(),
                'cuestionario' => $codigo,
            ]);

            return $this->redirectToRoute(
                $this->rutaBase . '_formulario_incidencia_index',
                ['codigo' => $codigo],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('intranet/desempenyo/incidencia_edit.html.twig', [
            'incidencia' => $incidencia,
            'button_label' => 'Actualizar',
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        path: '/admin/incidencia/{id}',
        name: 'admin_incidencia_show',
        defaults: ['titulo' => 'Incidencia para Evaluación de Desempeño'],
        methods: ['GET']
    )]
    public function show(Incidencia $incidencia): Response
    {
        $this->denyAccessUnlessGranted('admin');

        return $this->render('intranet/desempenyo/admin/incidencia/show.html.twig', [
            'incidencia' => $incidencia,
        ]);
    }

    #[Route(
        path: '/formulario/{codigo}/incidencia/{id}',
        name: 'formulario_incidencia_show',
        requirements: ['codigo' => '[a-z0-9-]+'],
        defaults: ['titulo' => 'Incidencia de Evaluación de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function showUsuario(string $codigo, Incidencia $incidencia): Response
    {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        $cuestionario = $this->cuestionarioRepository->findOneBy(['codigo' => u($codigo)->beforeLast('-')]);
        if ($incidencia->getCuestionario() !== $cuestionario) {
            $this->addFlash('warning', 'La incidencia no corresponde al cuestionario.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif ($incidencia->getIncidencia()?->getSolicitante() !== $this->getUser()) {
            $this->addFlash('warning', 'La incidencia no ha sido solicitada por el usuario.');

            return $this->redirectToRoute($this->rutaBase);
        }

        return $this->render('intranet/desempenyo/incidencia_show.html.twig', [
            'incidencia' => $incidencia,
        ]);
    }

    #[Route(
        path: '/formulario/{codigo}/incidencia/{id}/delete',
        name: 'formulario_incidencia_delete',
        defaults: ['titulo' => 'Eliminar Incidencia para Evaluación de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function deleteUsuario(
        Request                    $request,
        CirhusIncidenciaRepository $cirhusRepository,
        EstadoRepository           $estadoRepository,
        IncidenciaApunteRepository $apunteRepository,
        string                     $codigo,
        Incidencia                 $incidencia,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        $cuestionario = $this->cuestionarioRepository->findOneBy(['codigo' => u($codigo)->beforeLast('-')]);
        $iniciado = $estadoRepository->findOneBy(['nombre' => Estado::INICIADO]);
        $cirhus = $incidencia->getIncidencia();
        $ultimo = $cirhus?->getApuntes()->last();
        if ($incidencia->getCuestionario() !== $cuestionario) {
            $this->addFlash('warning', 'La incidencia no corresponde al cuestionario.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (!$cirhus instanceof CirhusIncidencia || $cirhus->getSolicitante() !== $this->getUser()) {
            $this->addFlash('warning', 'La incidencia no ha sido solicitada por el usuario.');

            return $this->redirectToRoute($this->rutaBase);
        } elseif (!$ultimo instanceof IncidenciaApunte || $ultimo->getEstado() !== $iniciado) {
            $this->addFlash('warning', 'La incidencia está siendo tratada y no puede ser eliminada.');

            return $this->redirectToRoute($this->rutaBase);
        }

        $id = $incidencia->getId();
        if ($this->isCsrfTokenValid('delete' . (int) $id, $request->request->getString('_token'))) {
            foreach ($apunteRepository->findBy(['incidencia' => $cirhus]) as $apunte) {
                $apunteRepository->remove($apunte);
            }

            $cirhusRepository->remove($cirhus);
            $this->incidenciaRepository->remove($incidencia, true);
            $this->generator->logAndFlash('info', 'Incidencia de desempeño eliminada', [
                'id' => $id,
                'cuestionario' => $codigo,
                'solicitante' => $cirhus->getSolicitante()?->getUvus(),
            ]);
        }

        return $this->redirectToRoute(
            $this->rutaBase . '_formulario_incidencia_index',
            ['codigo' => $codigo],
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route(
        path: '/admin/incidencia/{incidencia}/apunte/{apunte?}',
        name: 'admin_incidencia_apunte',
        methods: ['GET', 'POST']
    )]
    public function apunte(
        Request                    $request,
        CirhusIncidenciaRepository $cirhusRepository,
        EstadoRepository           $estadoRepository,
        IncidenciaApunteRepository $apunteRepository,
        IncidenciaRepository       $incidenciaRepository,
        Incidencia                 $incidencia,
        IncidenciaApunte           $apunte = null,
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        $nuevo = false;
        /** @var Usuario $autor */
        $autor = $this->getUser();
        $iniciado = $estadoRepository->findOneBy(['nombre' => Estado::INICIADO]);
        $finalizado = $estadoRepository->findOneBy(['nombre' => Estado::FINALIZADO]);
        $cirhus = $incidencia->getIncidencia();
        $ultimo = $cirhus?->getApuntes()->last();

        if (!$cirhus instanceof CirhusIncidencia) {
            $incidenciaRepository->remove($incidencia, true);
            $this->addFlash('danger', 'La incidencia se elimina por error en inconsistencia de datos.');

            return $this->redirectToRoute($this->rutaBase . '_admin_incidencia_index', [], Response::HTTP_SEE_OTHER);
        } elseif ($apunte instanceof IncidenciaApunte && !$cirhus->getApuntes()->contains($apunte)) {
            $this->addFlash('danger', 'El apunte no pertenece a esta incidencia.');

            return $this->redirectToRoute($this->rutaBase . '_admin_incidencia_show', ['id' => $incidencia->getId()], Response::HTTP_SEE_OTHER);
        } elseif ($ultimo instanceof IncidenciaApunte && $ultimo->getEstado() === $finalizado) {
            $this->addFlash('danger', 'La incidencia ya está finalizada.');

            return $this->redirectToRoute($this->rutaBase . '_admin_incidencia_show', ['id' => $incidencia->getId()], Response::HTTP_SEE_OTHER);
        }

        if (!$apunte instanceof IncidenciaApunte) {
            $apunte = new IncidenciaApunte();
            $nuevo = true;
        }

        $form = $this->createForm(IncidenciaApunteType::class, $apunte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($apunte->getEstado() === $iniciado) {
                $this->addFlash('warning', 'No se puede cambiar a estado iniciado.');

                return $this->redirectToRoute($request->attributes->getString('_route'));
            }

            $fecha = new DateTimeImmutable();
            $apunte
                ->setIncidencia($cirhus)
                ->setAutor($autor)
                ->setFechaInicio($fecha)
            ;
            $cirhus->addApunte($apunte);
            if ($nuevo) {
                if ($ultimo instanceof IncidenciaApunte) {
                    $ultimo->setFechaFin($fecha);
                    $apunteRepository->save($ultimo);
                }
                $apunteRepository->save($apunte, true);
                $mensaje = 'Nuevo apunte de incidencia de evaluación de desempeño';
            } else {
                $apunteRepository->save($apunte);
                $mensaje = 'Apunte de incidencia de evaluación de desempeño editado';
            }
            $cirhus->addApunte($apunte);
            $cirhusRepository->save($cirhus, true);

            $this->generator->logAndFlash('info', $mensaje, [
                'id' => $apunte->getId(),
                'cuestionario' => $incidencia->getCuestionario(),
                'incidencia_id' => $incidencia->getId(),
                'autor' => $apunte->getAutor(),
            ]);

            return $this->redirectToRoute(
                $this->rutaBase . '_admin_incidencia_show',
                ['id' => $incidencia->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('intranet/desempenyo/admin/incidencia/apunte.html.twig', [
            'incidencia' => $incidencia,
            'titulo' => sprintf('%s Apunte de Incidencia para Evaluación de Desempeño', $nuevo ? 'Nuevo' : 'Editar'),
            'button_label' => $nuevo ? '' : 'Actualizar',
            'form' => $form->createView(),
        ]);
    }
}

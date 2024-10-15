<?php

namespace App\Controller\Desempenyo;

use App\Entity\Cirhus\Incidencia as CirhusIncidencia;
use App\Entity\Cirhus\IncidenciaApunte;
use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Desempenyo\Incidencia;
use App\Entity\Sistema\Estado;
use App\Entity\Sistema\Usuario;
use App\Form\Desempenyo\IncidenciaType;
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
    public function __construct(
        private readonly MessageGenerator     $generator,
        private readonly IncidenciaRepository $incidenciaRepository,
    ) {
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
        path: '/formulario/{codigo}/incidencia/new',
        name: 'formulario_incidencia_new',
        requirements: ['codigo' => '[a-z0-9-]+'],
        defaults: ['titulo' => 'Nueva Incidencia de Evaluación de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function new(
        Request                    $request,
        RutaActual                 $actual,
        CirhusIncidenciaRepository $cirhusRepository,
        CuestionarioRepository     $cuestionarioRepository,
        EstadoRepository           $estadoRepository,
        string                     $codigo,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        $cuestionario = $cuestionarioRepository->findOneBy(['codigo' => u($codigo)->beforeLast('-')]);
        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute('intranet_desempenyo');
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
                ->setAutor($usuario)
                ->setEstado($iniciado)
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
                'intranet_desempenyo_admin_tipo_incidencia_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('intranet/desempenyo/incidencia_edit.html.twig', [
            'incidencia' => $incidencia,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        path: '/formulario/{codigo}/incidencia/{id}',
        name: 'intranet_desempenyo_formulario_incidencia_edit',
        requirements: ['codigo' => '[a-z0-9-]+'],
        defaults: ['titulo' => 'Editar Incidencia de Evaluación de Desempeño'],
        methods: ['GET', 'POST']
    )]
    public function edit(
        Request                $request,
        CuestionarioRepository $cuestionarioRepository,
        EstadoRepository       $estadoRepository,
        string                 $codigo,
        Incidencia             $incidencia
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        $cuestionario = $cuestionarioRepository->findOneBy(['codigo' => u($codigo)->beforeLast('-')]);
        $iniciado = $estadoRepository->findOneBy(['nombre' => Estado::INICIADO]);
        if (!$cuestionario instanceof Cuestionario) {
            $this->addFlash('warning', 'El cuestionario solicitado no existe o no está disponible.');

            return $this->redirectToRoute('intranet_desempenyo');
        } elseif ($incidencia->getCuestionario() !== $cuestionario) {
            $this->addFlash('warning', 'La incidencia no corresponde al cuestionario.');

            return $this->redirectToRoute('intranet_desempenyo');
        } elseif ($incidencia->getIncidencia()?->getApuntes()->last()->getEstado() !== $iniciado) {
            $this->addFlash('warning', 'La incidencia está siendo tratada y no puede ser editada.');

            return $this->redirectToRoute('intranet_desempenyo');
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
                'intranet_desempenyo_admin_tipo_incidencia_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('intranet/desempenyo/incidencia_edit.html.twig', [
            'incidencia' => $incidencia,
            'button_label' => 'Actualizar',
            'form' => $form->createView(),
        ]);
    }
}

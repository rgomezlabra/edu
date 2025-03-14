<?php

namespace App\Controller;

use App\Entity\Cuestiona\Formulario;
use App\Entity\Cuestiona\Pregunta;
use App\Entity\Estado;
use App\Entity\Usuario;
use App\Repository\Cuestiona\CuestionarioRepository;
use App\Repository\Desempenyo\EvaluaRepository;
use App\Repository\Desempenyo\TipoIncidenciaRepository;
use App\Repository\EstadoRepository;
use App\Repository\Plantilla\EmpleadoRepository;
use App\Service\RutaActual;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/', name: 'desempenyo')]
class DesempenyoController extends AbstractController
{
    public function __construct(
        protected readonly RutaActual $actual,
    ) {
    }

    #[Route(
        path: '/',
        name: '',
        defaults: ['titulo' => 'Evaluación del Desempeño Universitario'],
)]
    public function inicio(
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
        EstadoRepository       $estadoRepository,
        EvaluaRepository       $evaluaRepository,
    ): Response {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        if (!$usuario instanceof Usuario) {
            return $this->redirectToRoute('app_login');
        }
        $empleado = $empleadoRepository->findOneByUsuario($usuario);
        $publicado = $estadoRepository->findOneBy(['nombre' => Estado::PUBLICADO]);
        $cuestionarios = $cuestionarioRepository->findBy(['estado' => $publicado]);
        $evaluados = $evaluaRepository->findBy([
            'cuestionario' => $cuestionarios,
            'empleado' => $empleado,
        ]);
        // Calcular las medias de los formularios enviados para mostrar resultados finales del usuario
        /** @var float[] $medias */
        $medias = [];
        foreach ($evaluados as $evaluado) {
            $formulario = $evaluado->getFormulario();
            if ($formulario instanceof Formulario && null !== $formulario->getFechaEnvio()) {
                $total = 0;
                $n = 0;
                foreach ($formulario->getRespuestas() as $respuesta) {
                    $pregunta = $respuesta->getPregunta();
                    if ($pregunta instanceof Pregunta) {
                        $total += (float) $respuesta->getValor()['valor'];
                        $n++;
                    }
                }
                $medias[$evaluado->getTipoEvaluador()] = $total / $n;
            }
        }

        return $this->render('desempenyo/index.html.twig', [
            'cuestionarios' => $cuestionarios,
            'evaluados' => $evaluados,
            'evaluaciones' => $evaluaRepository->findBy(['evaluador' => $empleado]),
            'medias' => $medias,
        ]);
    }

    #[Route(
        path: '/admin',
        name: '_admin',
        defaults: ['titulo' => 'Administración de Evaluación de Desempeño'],
        methods: ['GET']
    )]
    public function admin(
        CuestionarioRepository   $cuestionarioRepository,
        EstadoRepository         $estadoRepository,
        TipoIncidenciaRepository $tipoIncidenciaRepository
    ): Response {
        $this->denyAccessUnlessGranted('admin');
        if (0 === $tipoIncidenciaRepository->count([])) {
            $this->addFlash('warning', 'No hay definido ningún tipo de incidencias.');
        } elseif (0 === $cuestionarioRepository->count([
                'estado' => $estadoRepository->findOneBy(['nombre' => Estado::PUBLICADO]),
            ])) {
            $this->addFlash('warning', 'No hay cuestionarios de evaluación activos.');
        }

        return $this->render('desempenyo/admin/index.html.twig');
    }
}

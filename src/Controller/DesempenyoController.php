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

#[Route(path: 'desempenyo', name: 'desempenyo')]
class DesempenyoController extends AbstractController
{
    public function __construct(
        protected readonly RutaActual $actual,
    ) {
    }

    #[Route(
        path: '/',
        name: ''
    )]
    public function inicio(
        CuestionarioRepository $cuestionarioRepository,
        EmpleadoRepository     $empleadoRepository,
        EvaluaRepository       $evaluaRepository,
    ): Response {
        $this->denyAccessUnlessGranted(null, ['relacion' => null]);
        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $empleado = $empleadoRepository->findOneByUsuario($usuario);
        $cuestionarios = $cuestionarioRepository->findBy(['aplicacion' => $this->actual->getAplicacion()]);
        $evaluados = $evaluaRepository->findBy([
            'cuestionario' => array_filter(
                $cuestionarios,
                static fn ($cuestionario) => Estado::PUBLICADO === $cuestionario->getEstado()?->getNombre()
            ),
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
                'aplicacion' => $this->actual->getAplicacion(),
                'estado' => $estadoRepository->findOneBy(['nombre' => Estado::PUBLICADO]),
            ])) {
            $this->addFlash('warning', 'No hay cuestionarios de evaluación activos.');
        }

        return $this->render('desempenyo/admin/index.html.twig');
    }
}

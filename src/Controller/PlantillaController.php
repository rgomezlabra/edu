<?php

namespace App\Controller;

use App\Entity\Plantilla\Ausencia;
use App\Entity\Plantilla\Empleado;
use App\Entity\Plantilla\Situacion;
use App\Entity\Plantilla\Grupo;
use App\Entity\Plantilla\Unidad;
use App\Entity\Usuario;
use App\Form\VolcadoType;
use App\Repository\Plantilla\AusenciaRepository;
use App\Repository\Plantilla\EmpleadoRepository;
use App\Repository\Plantilla\SituacionRepository;
use App\Repository\Plantilla\GrupoRepository;
use App\Repository\UsuarioRepository;
use App\Repository\Plantilla\UnidadRepository;
use App\Service\Csv;
use App\Service\MessageGenerator;
use App\Service\SirhusLock;
use DateMalformedStringException;
use Exception;
use Predis\ClientInterface;
use Redis;
use RedisArray;
use RedisCluster;
use RedisException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function Symfony\Component\String\u;

/**
 * Controlador para volcar datas a la aplicación plantilla actual.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[Route(path: '/desempenyo/admin/plantilla', name: 'desempenyo_admin_plantilla_')]
class PlantillaController extends AbstractController
{
    /** @var ClientInterface|Redis|RedisArray|RedisCluster $redis */
    private readonly object $redis;

    public function __construct(
        private readonly MessageGenerator    $generator,
        private readonly AusenciaRepository  $ausenciaRepository,
        private readonly EmpleadoRepository  $empleadoRepository,
        private readonly GrupoRepository     $grupoRepository,
        private readonly UsuarioRepository   $usuarioRepository,
        private readonly SituacionRepository $situacionRepository,
        private readonly UnidadRepository    $unidadRepository,
        #[Autowire('%app.redis_url%')]
        private readonly string              $redisUrl,
    ) {
        $this->redis = RedisAdapter::createConnection($this->redisUrl);
    }

    /** Volcado de datos de la plantilla actual. */
    #[Route(
        path: '/volcado',
        name: 'volcado',
        defaults: ['titulo' => 'Volcado de Datos de la Plantilla Actual'],
        methods: ['GET', 'POST']
    )]
    public function volcado(Request $request, SirhusLock $lock): Response
    {
        $this->denyAccessUnlessGranted('admin');
        $mensajeLog = 'Volcado de empleados';
        $campos = [
            'NOMBRE',
            'APELLIDOS',
            'NIF',
            'CORREO',
            'PAS_SN',
            'NRP',
            'ID_GRUPO',
            'ID_NIVEL',
            'ID_UNIDAD',
            'DES_UNIDAD',
            'ID_SITAD',
            'DES_SITAD',
            'AUS_SN',
            'ID_AUSENC',
            'DES_AUSENC',
            'CES_SN',
            'F_CES',
            'F_INI_VIG',
        ];
        $ttl = 300;
        if (null === $lock->acquire($ttl)) {
            $this->addFlash('warning', 'Recurso bloqueado por otra operación de carga.');

            return $this->redirectToRoute('desempenyo_admin', [], Response::HTTP_SEE_OTHER);
        }

        $lineas = [];
        $form = $this->createForm(VolcadoType::class, ['maxSize' => '4096k']);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Cargar fichero CSV
            /** @var UploadedFile $fichero */
            $fichero = $form->get('fichero_csv')->getData();
            $csv = new Csv();
            $csv->abrir($fichero);
            if (!$csv->comprobarCabeceras($campos)) {
                $this->generator->logAndFlash('error', 'No se puede abrir el fichero de datos o no es correcto', [
                    'fichero' => $fichero->getClientOriginalName(),
                ]);

                return $this->redirectToRoute((string) $request->attributes->get('_route'));
            }

            while (($datos = $csv->leer($campos)) !== null) {
                $lineas[] = $datos;
            }

            $csv->cerrar();

            // Grabar datos
            $resultado = [
                'inicio' => new DatePoint(),
                'total' => count($lineas),
                'linea' => 0,
                'nuevos' => [
                    'empleados' => 0,
                    'unidades' => 0,
                    'situaciones' => 0,
                    'ausencias' => 0,
                ],
                'actualizados' => 0,
                'descartados' => 0,
                'cesados' => 0,
                'duracion' => 0,
                'finalizado' => false,
            ];
            set_time_limit($ttl);  // La carga completa puede tardar más de los 30 s. por defecto.
            $inicio = microtime(true);
            // Obtener todas datos auxiliares para reducir consultas
            $grupos = $this->grupoRepository->findAll();
            $unidades = $this->unidadRepository->findAll();
            $situaciones = $this->situacionRepository->findAll();
            $ausencias = $this->ausenciaRepository->findAll();

            foreach ($lineas as $linea) {
                ++$resultado['linea'];
                $idGrupo = is_numeric($linea['ID_GRUPO']) ? sprintf('L%s', $linea['ID_GRUPO']) : $linea['ID_GRUPO'];
                $empleado = $this->empleadoRepository->findOneBy(['doc_identidad' => $linea['NIF']]);
                if ('S' === $linea['CES_SN']) {
                    if ($empleado instanceof Empleado) {
                        // Marcar como cesado
                        try {
                            $cese = DatePoint::createFromFormat('d/m/Y', $linea['F_CES']);
                        } catch (DateMalformedStringException) {
                            $cese = new DatePoint();
                        }
                        $empleado->setCesado($cese);
                        $this->empleadoRepository->save($empleado, true);
                        ++$resultado['cesados'];
                    }
                    continue;
                }

                if (!$empleado instanceof Empleado) {
                    $empleado = new Empleado();
                    $usuario = new Usuario();
                    ++$resultado['nuevos']['empleados'];
                } else {
                    $usuario = $empleado->getUsuario();
                    ++$resultado['actualizados'];
                }

                $unidad = array_slice(array_filter($unidades, static fn (Unidad $u) => $u->getCodigo() === $linea['ID_UNIDAD']), 0, 1)[0] ?? null;
                if (!$unidad instanceof Unidad) {
                    // Nueva unidad
                    $unidad = new Unidad();
                    $unidad->setCodigo($linea['ID_UNIDAD'])
                        ->setNombre($linea['DES_UNIDAD'])
                    ;
                    $this->unidadRepository->save($unidad, true);
                    ++$resultado['nuevos']['unidades'];
                }

                $situacion = array_slice(array_filter($situaciones, static fn (Situacion $s) => $s->getCodigo() === $linea['ID_SITAD']), 0, 1)[0] ?? null;
                if (!$situacion instanceof Situacion) {
                    // Nueva situación administrativa
                    $situacion = new Situacion();
                    $situacion->setCodigo($linea['ID_SITAD'])
                        ->setNombre($linea['DES_SITAD'])
                    ;
                    $this->situacionRepository->save($situacion, true);
                    ++$resultado['nuevos']['situaciones'];
                }

                if ('S' === $linea['AUS_SN']) {
                    $ausencia = array_slice(array_filter($situaciones, static fn (Ausencia $a) => $a->getCodigo() === $linea['ID_AUSENC']), 0, 1)[0] ?? null;
                    if (!$ausencia instanceof Ausencia) {
                        // Nueva ausencia
                        $ausencia = new Ausencia();
                        $ausencia->setCodigo($linea['ID_AUSENC'])
                            ->setNombre($linea['DES_AUSENC'])
                        ;
                        $this->ausenciaRepository->save($ausencia, true);
                        ++$resultado['nuevos']['ausencias'];
                    }
                } else {
                    $ausencia = null;
                }

                // Actualizar datos del empleado
                try {
                    $vigencia = '' !== $linea['F_INI_VIG'] ? DatePoint::createFromFormat('d/m/Y', $linea['F_INI_VIG']) : null;
                } catch (DateMalformedStringException) {
                    $vigencia = null;
                }

                $empleado->setNombre($linea['NOMBRE'])
                    ->setApellidos($linea['APELLIDOS'])
                    ->setDocIdentidad($linea['NIF'])
                    ->setNrp($linea['NRP'])
                    ->setGrupo(array_slice(array_filter($grupos, static fn (Grupo $g) => $g->getNombre() === $idGrupo), 0, 1)[0])
                    ->setNivel((int) $linea['ID_NIVEL'])
                    ->setUnidad($unidad)
                    ->setSituacion($situacion)
                    ->setAusencia($ausencia)
                    ->setVigente($vigencia)
                    ->setCesado(null)
                ;
                $this->empleadoRepository->save($empleado, true);
                $usuario->setLogin(u($linea['NIF'])->lower())
                    ->setPassword($empleado->getNrp())  // TODO definir clave por defecto
                    ->setCorreo(u($linea['CORREO'])->lower())
                    ->setEmpleado($empleado)
                ;
                $this->usuarioRepository->save($usuario, true);
                ++$resultado['actualizados'];
                $resultado['duracion'] = microtime(true) - $inicio;
                try {
                    $this->redis->set('empleados', json_encode($resultado));
                } catch (RedisException) {
                }
            }

            $resultado['finalizado'] = true;
            try {
                $this->redis->set('empleados', json_encode($resultado));
            } catch (RedisException) {
            }

            $this->generator->logAndFlash('info', $mensajeLog, $resultado);
            $lock->release();

            return $this->render('desempenyo/admin/plantilla/resultado_volcado.html.twig', $resultado);
        }

        try {
            $ultimo = json_decode($this->redis->get('empleados'));
        } catch (Exception) {
            $ultimo = null;
        }

        $lock->release();

        return $this->render('desempenyo/admin/plantilla/volcado.html.twig', [
            'form' => $form->createView(),
            'campos' => $campos,
            'ultimo' => $ultimo,
        ]);
    }

    /** Obtener información sobre el progreso de carga de datos. */
    #[Route(
        path: '/progreso',
        name: 'progreso',
        methods: ['POST']
    )]
    public function progreso(Request $request, string $tipo): ?JsonResponse
    {
        $this->denyAccessUnlessGranted('admin');
        if ($request->isXmlHttpRequest()) {
            try {
                /** @var array<array-key, mixed> $datos */
                $datos = json_decode((string) $this->redis->get($tipo));

                return new JsonResponse($datos);
            } catch (RedisException) {
                return null;
            }
        }

        return null;
    }
}

<?php

namespace App\Repository\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Desempenyo\Evalua;
use App\Entity\Plantilla\Empleado;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends ServiceEntityRepository<Evalua>
 */
class EvaluaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evalua::class);
    }

    public function save(Evalua $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Evalua $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * Buscar datos de evaluación para los criterios de búsqueda indicados (cuestionario, empleado, evaluador y tipo de
     * evaluación).
     * @param  int[]|bool[]|Cuestionario[]|Empleado[] $criterios
     * @return Evalua[]
     */
    public function findByEvaluacion(array $criterios): array
    {
        $qb = $this->createQueryBuilder('evalua')
            ->addSelect('origen')
            ->join('evalua.origen', 'origen')
        ;
        $empleados = false;
        $evaluaciones = false;
        foreach ($criterios as $criterio => $valor) {
            switch ($criterio) {
                case 'cuestionario':
                    if ($valor instanceof Cuestionario) {
                        $qb->join('evalua.cuestionario', 'cuestionario')
                            ->andWhere('cuestionario.id = :cuestionario')
                            ->setParameter('cuestionario', $valor->getId())
                        ;
                    }
                    break;
                case 'empleado':
                    if ($valor instanceof Empleado) {
                        $empleados = true;
                        $qb->andWhere('empleado.id = :empleado')
                            ->setParameter('empleado', $valor->getId())
                        ;
                    }
                    break;
                case 'evaluador':
                    if ($valor instanceof Empleado) {
                        $evaluaciones = true;
                        $qb->andWhere('evaluador.id = :evaluador')
                            ->setParameter('evaluador', $valor->getId())
                        ;
                    }
                    break;
                case 'tipo':
                    /** @var int[] $valor */
                    if (!is_array($valor)) {
                        $valor = [$valor];
                    }

                    if (in_array(Evalua::AUTOEVALUACION, $valor) || in_array(Evalua::NO_EVALUACION, $valor)) {
                        $empleados = true;
                    } elseif (in_array(Evalua::EVALUA_RESPONSABLE, $valor) || in_array(Evalua::EVALUA_OTRO, $valor)) {
                        $empleados = true;
                        $evaluaciones = true;
                    }

                    $qb->andWhere('evalua.tipo_evaluador IN (:tipo)')->setParameter('tipo', $valor);
                    break;
                case 'entregados':
                    $qb->join('evalua.formulario', 'formulario')
                        ->andWhere('formulario.fecha_envio ' . ($valor ? 'IS NOT NULL' : 'IS NULL'))
                    ;
            }
        }

        if ($empleados) {
            $qb = $this->addEmpleados($qb);
        }
        if ($evaluaciones) {
            $qb = $this->addEvaluadores($qb);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Devuelve las evaluaciones con formularios según los criterios de búsqueda (cuestionario, empleado, evaluador y
     * si han sido entregados.
     * @param bool[]|Cuestionario[]|Empleado[] $criterios
     * @return Evalua[]
     */
    public function findByFormularios(array $criterios): array
    {
        $qb = $this->createQueryBuilder('evalua')
            ->addSelect('cuestionario')
            ->join('evalua.cuestionario', 'cuestionario')
        ;
        foreach ($criterios as $criterio => $valor) {
            switch ($criterio) {
                case 'cuestionario':
                    if ($valor instanceof Cuestionario) {
                        $qb->andWhere('cuestionario.id = :cuestionario')
                            ->setParameter('cuestionario', $valor->getId())
                        ;
                    }
                    break;
                case 'empleado':
                    if ($valor instanceof Empleado) {
                        $qb = $this->addEmpleados($qb)
                            ->andWhere('empleado.id = :empleado')
                            ->setParameter('empleado', $valor->getId())
                        ;
                    }
                    break;
                case 'evaluador':
                    if ($valor instanceof Empleado) {
                        $qb = $this->addEvaluadores($qb)
                            ->andWhere('evaluador.id = :evaluador')
                            ->setParameter('evaluador', $valor->getId())
                        ;
                    }
                    break;
                case 'entregados':
                    $qb->join('evalua.formulario', 'formulario')
                        ->andWhere('formulario.fecha_envio ' . ($valor ? 'IS NOT NULL' : 'IS NULL'));
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Devuelve las evaluaciones con formularios entregados según los criterios de búsqueda (cuestionario, empleado y
     * evaluador).
     * @param int[]|Cuestionario[]|Empleado[] $criterios
     * @return Evalua[]
     */
    public function findByEntregados(array $criterios): array
    {
        return $this->findByEvaluacion([...$criterios, 'entregados' => true]);
    }

    /** Mejorar consulta para obtener datos de empleado evaluado. */
    private function addEmpleados(QueryBuilder $qb): QueryBuilder
    {
        return $qb->addSelect('empleado')
            ->addSelect('empleado_persona')
            ->addSelect('empleado_grupo')
            ->addSelect('empleado_plaza_titular')
            ->addSelect('empleado_unidad_titular')
            ->addSelect('empleado_plaza_ocupada')
            ->addSelect('empleado_unidad_ocupada')
            ->join('evalua.empleado', 'empleado')
            ->join('empleado.persona', 'empleado_persona')
            ->join('empleado.grupo', 'empleado_grupo')
            ->leftJoin('empleado.plaza_titular', 'empleado_plaza_titular')
            ->leftJoin('empleado_plaza_titular.unidad', 'empleado_unidad_titular')
            ->leftJoin('empleado.plaza_ocupada', 'empleado_plaza_ocupada')
            ->leftJoin('empleado_plaza_ocupada.unidad', 'empleado_unidad_ocupada')
        ;
    }

    /** Mejorar consulta para obtener datos de evaluador. */
    private function addEvaluadores(QueryBuilder $qb): QueryBuilder
    {
        return $qb->addSelect('evaluador')
            ->addSelect('evaluador_persona')
            ->addSelect('evaluador_grupo')
            ->addSelect('evaluador_plaza_titular')
            ->addSelect('evaluador_unidad_titular')
            ->addSelect('evaluador_plaza_ocupada')
            ->addSelect('evaluador_unidad_ocupada')
            ->join('evalua.evaluador', 'evaluador')
            ->join('evaluador.persona', 'evaluador_persona')
            ->join('evaluador.grupo', 'evaluador_grupo')
            ->leftJoin('evaluador.plaza_titular', 'evaluador_plaza_titular')
            ->leftJoin('evaluador_plaza_titular.unidad', 'evaluador_unidad_titular')
            ->leftJoin('evaluador.plaza_ocupada', 'evaluador_plaza_ocupada')
            ->leftJoin('evaluador_plaza_ocupada.unidad', 'evaluador_unidad_ocupada')
        ;
    }
}

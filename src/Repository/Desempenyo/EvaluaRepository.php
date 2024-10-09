<?php

namespace App\Repository\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Desempenyo\Evalua;
use App\Entity\Plantilla\Empleado;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    /** Borrar datos de autoevaluación. */
    public function deleteAutoevaluacion(Cuestionario $cuestionario): void
    {
        $this->createQueryBuilder('evalua')
            ->delete()
            ->andWhere('evalua.cuestionario = :cuestionario')
            ->andWhere('evalua.empleado = evalua.evaluador')
            ->setParameter('cuestionario', $cuestionario)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Buscar datos de evaluación para los criterios de búsqueda indicados (cuestionario, empleado, evaluador y tipo de
     * evaluación).
     * @param  int[]|Cuestionario[]|Empleado[] $criterios
     * @return Evalua[]
     */
    public function findByEvaluacion(array $criterios): array
    {
        $qb = $this->createQueryBuilder('evalua');
        foreach ($criterios as $criterio => $valor) {
            switch ($criterio) {
                case 'cuestionario':
                    $qb->join('evalua.cuestionario', 'cuestionario')
                        ->andWhere('cuestionario.id = :cuestionario')
                        ->setParameter('cuestionario', $valor->getId())
                    ;
                    break;
                case 'empleado':
                    $qb->join('evalua.empleado', 'empleado')
                        ->andWhere('empleado.id = :empleado')
                        ->setParameter('empleado', $valor->getId())
                    ;
                    break;
                case 'evaluador':
                    $qb->join('evalua.evaluador', 'evaluador')
                        ->andWhere('evaluador.id = :evaluador')
                        ->setParameter('evaluador', $valor->getId())
                    ;
                    break;
                case 'tipo':
                    $qb->andWhere('evalua.tipo_evaluador IN (:tipo)')->setParameter('tipo', $valor);
            }
        }

        if (!isset($criterios['tipo'])) {
            $qb->andWhere('evalua.tipo_evaluador = :tipo')->setParameter('tipo', Evalua::AUTOEVALUACION);
        }

        return $qb->getQuery()->getResult();
    }
}

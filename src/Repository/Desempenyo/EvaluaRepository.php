<?php

namespace App\Repository\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Desempenyo\Evalua;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends ServiceEntityRepository<Evalua>
 */
class EvaluaRepository extends ServiceEntityRepository
{
    // Tipos de evaluaciones
    public const int AUTOEVALUACION = 1;    // Autoevaluación (empleado = evaluador)

    public const int EVALUACION = 2;    // Evaluación de otro empleado

    public const int NO_EVALUACION = 3; // Solicitud de no evaluación (evaluador nulo)

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
     * Buscar todos los datos de evaluación para un cuestionario según el tipo solicitado (autoevaluación, evaluación
     * de otro empleado o solicitud de no evaluación).
     * @return Evalua[]
     */
    public function findByEvaluacion(Cuestionario $cuestionario, int $tipo = self::AUTOEVALUACION): array
    {
        $condicion = match ($tipo) {
            self::EVALUACION => 'evalua.empleado != evalua.evaluador AND evalua.evaluador IS NOT NULL',
            self::NO_EVALUACION => 'evalua.evaluador IS NULL',
            default => 'evalua.empleado = evalua.evaluador'
        };

        return $this->createQueryBuilder('evalua')
            ->andWhere('evalua.cuestionario = :cuestionario')
            ->andWhere($condicion)
            ->setParameter('cuestionario', $cuestionario)
            ->getQuery()
            ->getResult()
        ;
    }
}

<?php

namespace App\Repository\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Desempenyo\Formulario;
use App\Entity\Plantilla\Empleado;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends ServiceEntityRepository<Formulario>
 */
class FormularioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formulario::class);
    }

    public function save(Formulario $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Formulario $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Devuelve los formularios para un cuestionario dado.
     * @return Formulario[]
     */
    public function findByCuestionario(Cuestionario $cuestionario, ?Empleado $empleado = null, ?Empleado $evaluador = null): array
    {
        $qb = $this->createQueryBuilder('formulario')
            ->join('formulario.formulario', 'cues_formulario')
            ->join('cues_formulario.cuestionario', 'cuestionario')
            ->andWhere('cuestionario.id = :cuestionarioId')
            ->setParameter('cuestionarioId', $cuestionario->getId())
        ;
        if ($empleado instanceof Empleado) {
            $qb
                ->join('formulario.empleado', 'empleado')
                ->andWhere('empleado.id = :empleadoId')
                ->setParameter('empleadoId', $empleado->getId())
            ;
        }

        if ($evaluador instanceof Empleado) {
            $qb
                ->join('formulario.evaluador', 'evaluador')
                ->andWhere('evaluador.id = :evaluadorId')
                ->setParameter('evaluadorId', $evaluador->getId())
            ;
        }

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }
}

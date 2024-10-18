<?php

namespace App\Repository\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Desempenyo\Incidencia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends ServiceEntityRepository<Incidencia>
 */
class IncidenciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly Security $security)
    {
        parent::__construct($registry, Incidencia::class);
    }

    public function save(Incidencia $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Incidencia $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Devuelve todas las incidencias del usuario conectado o solo las que haya solicitado en un cuestionario dado.
     * @return Incidencia[]
     */
    public function findByConectado(?Cuestionario $cuestionario = null): array
    {
        $qb = $this->createQueryBuilder('incidencia')
            ->join('incidencia.incidencia', 'cirhus')
            ->andWhere('cirhus.solicitante = :solicitante')
            ->setParameter('solicitante', $this->security->getUser())
        ;
        if ($cuestionario instanceof Cuestionario) {
            $qb->andWhere('incidencia.cuestionario = :cuestionario')
                ->setParameter('cuestionario', $cuestionario)
            ;
        }

        return $qb->getQuery()->getResult();
    }
}

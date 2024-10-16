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
     * @return Incidencia[]
     */
    public function findByConectado(Cuestionario $cuestionario): array
    {
        return $this->createQueryBuilder('incidencia')
            ->join('incidencia.incidencia', 'cirhus')
            ->andWhere('cirhus.solicitante = :solicitante')
            ->andWhere('incidencia.cuestionario = :cuestionario')
            ->setParameter('solicitante', $this->security->getUser())
            ->setParameter('cuestionario', $cuestionario)
            ->getQuery()
            ->getResult()
        ;
    }
}

<?php

namespace App\Repository\Cirhus;

use App\Entity\Cirhus\Incidencia;
use App\Entity\Cirhus\Servicio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Incidencia>
 *
 * @method Incidencia|null find($id, $lockMode = null, $lockVersion = null)
 * @method Incidencia|null findOneBy(array $criteria, array $orderBy = null)
 * @method Incidencia[]    findAll()
 * @method Incidencia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IncidenciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
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
     * Incidencias asociadas a un servicio.
     * @return Incidencia[]
     */
    public function findByIncidencia(?Servicio $servicio): array
    {
        return $this->createQueryBuilder('incidencia')
            ->join('incidencia.servicio', 'servicio')
            ->andWhere('incidencia.servicio = :servicio')
            ->setParameter('servicio', $servicio)
            ->getQuery()
            ->getResult();
    }
}

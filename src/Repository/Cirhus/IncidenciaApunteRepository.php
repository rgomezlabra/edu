<?php

namespace App\Repository\Cirhus;

use App\Entity\Cirhus\IncidenciaApunte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IncidenciaApunte>
 *
 * @method IncidenciaApunte|null find($id, $lockMode = null, $lockVersion = null)
 * @method IncidenciaApunte|null findOneBy(array $criteria, array $orderBy = null)
 * @method IncidenciaApunte[]    findAll()
 * @method IncidenciaApunte[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IncidenciaApunteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IncidenciaApunte::class);
    }

    public function save(IncidenciaApunte $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(IncidenciaApunte $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

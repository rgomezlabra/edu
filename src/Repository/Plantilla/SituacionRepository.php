<?php

namespace App\Repository\Plantilla;

use App\Entity\Plantilla\Situacion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends ServiceEntityRepository<Situacion>
 * @method Situacion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Situacion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Situacion[]    findAll()
 * @method Situacion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SituacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Situacion::class);
    }

    public function save(Situacion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Situacion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

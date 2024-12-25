<?php

namespace App\Repository\Plantilla;

use App\Entity\Plantilla\Ausencia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends ServiceEntityRepository<Ausencia>
 * @method Ausencia|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ausencia|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ausencia[]    findAll()
 * @method Ausencia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AusenciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ausencia::class);
    }

    public function save(Ausencia $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ausencia $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

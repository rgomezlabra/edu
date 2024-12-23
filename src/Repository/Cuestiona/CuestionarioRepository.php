<?php

namespace App\Repository\Cuestiona;

use App\Entity\Cuestiona\Cuestionario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends ServiceEntityRepository<Cuestionario>
 * @method Cuestionario|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cuestionario|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cuestionario[]    findAll()
 * @method Cuestionario[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CuestionarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cuestionario::class);
    }

    public function save(Cuestionario $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Cuestionario $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

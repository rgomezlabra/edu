<?php

namespace App\Repository\Cuestiona;

use App\Entity\Cuestiona\Respuesta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends ServiceEntityRepository<Respuesta>
 * @method Respuesta|null find($id, $lockMode = null, $lockVersion = null)
 * @method Respuesta|null findOneBy(array $criteria, array $orderBy = null)
 * @method Respuesta[]    findAll()
 * @method Respuesta[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RespuestaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Respuesta::class);
    }

    public function save(Respuesta $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Respuesta $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

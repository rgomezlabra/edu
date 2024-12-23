<?php

namespace App\Repository\Cuestiona;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Pregunta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends ServiceEntityRepository<Pregunta>
 * @method Pregunta|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pregunta|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pregunta[]    findAll()
 * @method Pregunta[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PreguntaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pregunta::class);
    }

    public function save(Pregunta $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Pregunta $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Obtener todas las preguntas de un cuestionario.
     * @return Pregunta[]
     */
    public function findByCuestionario(Cuestionario $cuestionario): array
    {
        return $this->createQueryBuilder('pregunta')
            ->join('pregunta.grupo', 'grupo')
            ->join('grupo.cuestionario', 'cuestionario')
            ->where('cuestionario.id = :cuestionario')
            ->setParameter('cuestionario', $cuestionario->getId())
            ->addOrderBy('grupo.orden', 'ASC')
            ->addOrderBy('pregunta.orden', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}

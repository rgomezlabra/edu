<?php

namespace App\Repository\Cuestiona;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Formulario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends ServiceEntityRepository<Formulario>
 * @method Formulario|null find($id, $lockMode = null, $lockVersion = null)
 * @method Formulario|null findOneBy(array $criteria, array $orderBy = null)
 * @method Formulario[]    findAll()
 * @method Formulario[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
     * Obtener los formularios enviados para un cuestionario dado.
     * @return Formulario[]
     */
    public function findByEntregados(Cuestionario $cuestionario): array
    {
        return $this->createQueryBuilder('formulario')
            ->join('formulario.cuestionario', 'cuestionario')
            ->andWhere('formulario.enviado IS NOT NULL')
            ->andWhere('cuestionario.id = :cuestionario')
            ->setParameter('cuestionario', $cuestionario->getId())
            ->getQuery()
            ->getResult()
        ;
    }
}

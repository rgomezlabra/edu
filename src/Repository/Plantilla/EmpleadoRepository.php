<?php

namespace App\Repository\Plantilla;

use App\Entity\Plantilla\Empleado;
use App\Entity\Plantilla\Unidad;
use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends ServiceEntityRepository<Empleado>
 */
class EmpleadoRepository extends ServiceEntityRepository
{
    /** @var string[] */
    private const array REGIMENES_FIJOS = ['FC', 'LF'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Empleado::class);
    }

    public function save(Empleado $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->flush();
        }
    }

    public function remove(Empleado $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * Devuelve el empleado que tiene un documento de identidad determinado.
     * @param string $documento Documento de identidad a buscar
     * @param bool $soloActivo Buscar solo empleado activo (por defecto) o buscar también entre empleados cesados.
     */
    public function findOneByDocumento(string $documento, bool $soloActivo = true): ?Empleado
    {
        try {
            $qb = $this->createQueryBuilder('empleado')
                ->andWhere('empleado.doc_identidad = :doc')
                ->setParameter('doc', $documento)
            ;
            if ($soloActivo) {
                $qb->andWhere('empleado.cesado IS NULL');
            }

            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    /**
     * Devuelve los datos de empleado de un usuario determinado.
     * @param Usuario $usuario Usuario a buscar
     * @param bool $soloActivo Buscar solo empleado activo (por defecto) o buscar también entre empleados cesados.
     */
    public function findOneByUsuario(UserInterface $usuario, bool $soloActivo = true): ?Empleado
    {
        try {
            $qb = $this->createQueryBuilder('empleado')
                ->andWhere('empleado.usuario = :usuario')
                ->andWhere('empleado.cesado IS NULL')
                ->setParameter('usuario', $usuario)
            ;
            if ($soloActivo) {
                $qb->andWhere('empleado.cesado IS NULL');
            }

            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    /**
     * Buscar empleados cesados o activos.
     * @param bool $cese Indicar si se buscan empleados cesados (true) o activos (false).
     * @return Empleado[]
     */
    public function findCesados(bool $cese = true): array
    {
        return $this->createQueryBuilder('empleado')
            ->addSelect('unidad')
            ->leftJoin('empleado.unidad', 'unidad')
            ->andWhere($cese ? 'empleado.cesado IS NOT NULL' : 'empleado.cesado IS NULL')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Buscar los empleados que actualmente trabajan en una unidad.
     * @return Unidad[]
     */
    public function findByUnidad(Unidad $unidad): array
    {
        return $this->createQueryBuilder('empleado')
            ->distinct()
            ->leftJoin('empleado.unidad', 'unidad')
            ->andWhere('empleado.cesado IS NULL')
            ->andWhere('unidad.id = :id')
            ->setParameter('id', $unidad->getId())
            ->getQuery()
            ->getResult()
        ;
    }
}

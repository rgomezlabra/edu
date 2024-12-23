<?php

namespace App\Repository;

use App\Entity\Edificio;
use App\Entity\Empleado;
use App\Entity\Subunidad;
use App\Entity\Usuario;
use App\Entity\Unidades\Unidad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends ServiceEntityRepository<Empleado>
 * @method Empleado|null find($id, $lockMode = null, $lockVersion = null)
 * @method Empleado|null findOneBy(array $criteria, array $orderBy = null)
 * @method Empleado[]    findAll()
 * @method Empleado[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
     * Devuelve todos los empleados que tienen plaza.
     * @return Empleado[]
     */
    public function findWithPlaza(): array
    {
        return $this->createQueryBuilder('empleado')
            ->andWhere('empleado.plaza_ocupada IS NOT NULL OR empleado.plaza_titular IS NOT NULL')
            ->andWhere('empleado.cesado IS NULL')
            ->getQuery()
            ->getResult()
        ;
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
                ->join('empleado.persona', 'persona')
                ->andWhere('persona.doc_identidad = :doc')
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
     */
    public function findOneByUsuario(UserInterface $usuario): ?Empleado
    {
        try {
            return $this->createQueryBuilder('empleado')
                ->join('empleado.persona', 'persona')
                ->andWhere('persona.usuario = :usuario')
                ->andWhere('empleado.cesado IS NULL')
                ->setParameter('usuario', $usuario)
                ->getQuery()
                ->getOneOrNullResult()
            ;
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    /**
     * Devuelve la lista de validadores de empleados.
     * @return Empleado[]
     */
    public function findValidadores(): array
    {
        return $this->createQueryBuilder('empleado')
            ->distinct()
            ->join('empleado.validador', 'validador')
            ->andWhere('validador.cesado IS NULL')
            ->getQuery()
            ->getResult()
        ;
    }

    /** Quita los validadores de todos los empleados. */
    public function cleanValidadores(): void
    {
        $this->createQueryBuilder('empleado')
            ->update()
            ->set('empleado.validador', 'NULL')
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * Buscar empleados cesados o activos.
     * @param bool $cese  Indicar si se buscan empleados cesados (true) o activos (false).
     * @return Empleado[]
     */
    public function findCesados(bool $cese = true): array
    {
        return $this->createQueryBuilder('empleado')
            ->addSelect('persona', 'plaza_titular', 'plaza_ocupada', 'unidad_titular', 'unidad_ocupada')
            ->leftJoin('empleado.persona', 'persona')
            ->leftJoin('empleado.plaza_titular', 'plaza_titular')
            ->leftJoin('empleado.plaza_ocupada', 'plaza_ocupada')
            ->leftJoin('plaza_titular.unidad', 'unidad_titular')
            ->leftJoin('plaza_ocupada.unidad', 'unidad_ocupada')
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
            ->leftJoin('empleado.plaza_titular', 'plaza_titular')
            ->leftJoin('plaza_titular.unidad', 'unidad_titular')
            ->leftJoin('empleado.plaza_ocupada', 'plaza_ocupada')
            ->leftJoin('plaza_ocupada.unidad', 'unidad_ocupada')
            ->andWhere('empleado.cesado IS NULL')
            ->andWhere('unidad_ocupada.id = :id OR (unidad_titular.id = :id AND unidad_ocupada IS NULL)')
            ->setParameter('id', $unidad->getId())
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Buscar los empleados que actualmente trabajan en una subunidad.
     * @return Empleado[]
     */
    public function findBySubunidad(Subunidad $subunidad): array
    {
        return $this->createQueryBuilder('empleado')
            ->distinct()
            ->leftJoin('empleado.plaza_titular', 'plaza_titular')
            ->leftJoin('plaza_titular.subunidad', 'subunidad_titular')
            ->leftJoin('empleado.plaza_ocupada', 'plaza_ocupada')
            ->leftJoin('plaza_ocupada.subunidad', 'subunidad_ocupada')
            ->andWhere('empleado.cesado IS NULL')
            ->andWhere('subunidad_ocupada.id = :id OR (subunidad_titular.id = :id AND subunidad_ocupada IS NULL)')
            ->setParameter('id', $subunidad->getId())
            ->getQuery()
            ->getResult()
        ;
    }

    public function countBySubunidad(Subunidad $subunidad): int
    {
        try {
            return $this->createQueryBuilder('empleado')
                ->select('COUNT(empleado.id)')
                ->leftJoin('empleado.plaza_titular', 'plaza_titular')
                ->leftJoin('plaza_titular.subunidad', 'subunidad_titular')
                ->leftJoin('empleado.plaza_ocupada', 'plaza_ocupada')
                ->leftJoin('plaza_ocupada.subunidad', 'subunidad_ocupada')
                ->andWhere('empleado.cesado IS NULL')
                ->andWhere('subunidad_ocupada.id = :id OR (subunidad_titular.id = :id AND subunidad_ocupada IS NULL)')
                ->setParameter('id', $subunidad->getId())
                ->getQuery()
                ->getSingleScalarResult()
            ;
        } catch (NoResultException|NonUniqueResultException) {
            return 0;
        }
    }

    /**
     * Buscar los empleados que actualmente trabajan en un edificio.
     * @return Empleado[]
     */
    public function findByEdificio(Edificio $edificio): array
    {
        return $this->createQueryBuilder('empleado')
            ->distinct()
            ->leftJoin('empleado.plaza_titular', 'plaza_titular')
            ->leftJoin('plaza_titular.subunidad', 'subunidad_titular')
            ->leftJoin('subunidad_titular.edificio', 'edificio_titular')
            ->leftJoin('empleado.plaza_ocupada', 'plaza_ocupada')
            ->leftJoin('plaza_ocupada.subunidad', 'subunidad_ocupada')
            ->leftJoin('subunidad_ocupada.edificio', 'edificio_ocupada')
            ->andWhere('empleado.cesado IS NULL')
            ->andWhere('edificio_ocupada.id = :id OR (edificio_titular.id = :id AND edificio_ocupada IS NULL)')
            ->setParameter('id', $edificio->getId())
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Buscar empleados fijos sin plaza titular asignada.
     * @return Empleado[]
     */
    public function findFijosSinTitular(): array
    {
        return $this->createQueryBuilder('empleado')
            ->join('empleado.regimen', 'regimen')
            ->andWhere('regimen.codigo IN (:regimenes)')
            ->andWhere('empleado.plaza_titular IS NULL')
            ->andWhere('empleado.cesado IS NULL')
            ->setParameter('regimenes', $this::REGIMENES_FIJOS)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Buscar empleados temporales (no fijos) con plaza titular asignada.
     * @return Empleado[]
     */
    public function findTemporalesConTitular(): array
    {
        return $this->createQueryBuilder('empleado')
            ->join('empleado.regimen', 'regimen')
            ->andWhere('regimen.codigo NOT IN (:regimenes)')
            ->andWhere('empleado.plaza_titular IS NOT NULL')
            ->andWhere('empleado.cesado IS NULL')
            ->setParameter('regimenes', $this::REGIMENES_FIJOS)
            ->getQuery()
            ->getResult()
        ;
    }
}

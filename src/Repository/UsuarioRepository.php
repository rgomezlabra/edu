<?php

namespace App\Repository;

use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Usuario>
 *
 * @method Usuario|null find($id, $lockMode = null, $lockVersion = null)
 * @method Usuario|null findOneBy(array $criteria, array $orderBy = null)
 * @method Usuario[]    findAll()
 * @method Usuario[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsuarioRepository extends ServiceEntityRepository implements UserLoaderInterface, PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Usuario::class);
    }

    public function save(Usuario $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->flush();
        }
    }

    public function remove(Usuario $entity, bool $flush = false): void
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

    function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $user->setPassword($newHashedPassword);

        $this->flush();
    }

    /** Realiza búsquedas para paginar la salida de empleados. */
    public function findQueryBuilder(string $buscar = '', ?string $orden = null, string $dir = 'ASC'): QueryBuilder
    {
        $qb = $this->createQueryBuilder('usuario')
            ->addSelect('empleado')
            ->join('usuario.empleado', 'empleado')
        ;
        if ('' !== $buscar) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'usuario.login LIKE :buscar',
                    $qb->expr()->orX(
                        'usuario.correo1 LIKE :buscar',
                        $qb->expr()->orX(
                            'empleado.doc_identidad LIKE :buscar',
                            $qb->expr()->like(
                                $qb->expr()->concat(
                                    'empleado.nombre',
                                    $qb->expr()->concat($qb->expr()->literal(' '), 'empleado.apellidos')
                                ),
                                ':buscar'
                            )
                        )
                    )
                )
            )->setParameter(
                'buscar',
                '%' . $buscar . '%'
            );
        }
        match ($orden) {
            'id', 'login', 'correo', 'creado', 'modificado' => $qb->orderBy('usuario.' . $orden, $dir),
            'nif' => $qb->orderBy('empleado.doc_identidad', $dir),
            'nombre' => $qb->orderBy($qb->expr()->concat('empleado.nombre','empleado.apellidos'), $dir),
            'apellidos' => $qb->orderBy('empleado.apellidos', $dir),
            default => $qb,
        };

        return $qb;
    }

    /**
     * Cargar usuario por UVUS o por documento de identidad de la empleado asociada.
     * @param string $identifier UVUS o documento de identidad
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $identifier): ?Usuario
    {
        return $this->createQueryBuilder('usuario')
            ->join('usuario.empleado', 'empleado')
            ->andWhere('usuario.login = :val OR empleado.doc_identidad = :val')
            ->setParameter('val', $identifier)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function loadUserByUsername(string $username): ?Usuario
    {
        return $this->loadUserByIdentifier($username);
    }
}

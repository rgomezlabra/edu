<?php

namespace App\Repository;

use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @extends ServiceEntityRepository<Usuario>
 *
 * @method Usuario|null find($id, $lockMode = null, $lockVersion = null)
 * @method Usuario|null findOneBy(array $criteria, array $orderBy = null)
 * @method Usuario[]    findAll()
 * @method Usuario[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsuarioRepository extends ServiceEntityRepository implements UserLoaderInterface
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

    /** Realiza búsquedas para paginar la salida de personas. */
    public function findQueryBuilder(string $buscar = '', ?string $orden = null, string $dir = 'ASC'): QueryBuilder
    {
        $qb = $this->createQueryBuilder('usuario')
            ->addSelect('persona')
            ->join('usuario.persona', 'persona')
        ;
        if ('' !== $buscar) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'usuario.uvus LIKE :buscar',
                    $qb->expr()->orX(
                        'usuario.correo1 LIKE :buscar',
                        $qb->expr()->orX(
                            'persona.doc_identidad LIKE :buscar',
                            $qb->expr()->like(
                                $qb->expr()->concat(
                                    'persona.nombre',
                                    $qb->expr()->concat(
                                        $qb->expr()->literal(' '),
                                        $qb->expr()->concat(
                                            'persona.apellido1',
                                            $qb->expr()->concat($qb->expr()->literal(' '), 'persona.apellido2')
                                        )
                                    )
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
            'id', 'uvus', 'creado', 'modificado' => $qb->orderBy('usuario.' . $orden, $dir),
            'correo' => $qb->orderBy('usuario.correo1', $dir),
            'nif' => $qb->orderBy('persona.doc_identidad', $dir),
            'nombre' => $qb->orderBy(
                $qb->expr()->concat(
                    'persona.nombre',
                    $qb->expr()->concat(
                        'persona.apellido1',
                        'persona.apellido2'
                    )
                ),
                $dir
            ),
            'apellidos' => $qb->orderBy($qb->expr()->concat('persona.apellido1', 'persona.apellido2'), $dir),
            'origen' => $qb->leftJoin('usuario.origen', 'origen')
                ->orderBy('origen.nombre', $dir),
            default => $qb,
        };

        return $qb;
    }

    /**
     * Cargar usuario por UVUS o por documento de identidad de la persona asociada.
     * @param string $identifier UVUS o documento de identidad
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $identifier): ?Usuario
    {
        return $this->createQueryBuilder('usuario')
            ->join('usuario.persona', 'persona')
            ->andWhere('usuario.uvus = :val OR persona.doc_identidad = :val')
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

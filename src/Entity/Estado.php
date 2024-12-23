<?php

namespace App\Entity;

use App\Repository\EstadoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Entidad para gestionar estados de recursos.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: EstadoRepository::class)]
#[UniqueEntity('nombre')]
#[ORM\Index(columns: ['nombre'], name: 'idx_nombre')]
class Estado implements Stringable
{
    // Nombres de estados que no deben ser modificados en la BD porque se usan en el código.
    final public const string PUBLICADO = 'Publicado';

    final public const string BORRADOR = 'Borrador';

    final public const string ARCHIVADO = 'Archivado';

    final public const string ELIMINADO = 'Eliminado';

    final public const string SOLICITADO = 'Solicitado';

    final public const string APROBADO = 'Aprobado';

    final public const string RECHAZADO = 'Rechazado';

    final public const string NUEVO = 'Nuevo';

    final public const string CLONADO = 'Clonado';

    final public const string MODIFICADO = 'Modificado';

    final public const string TRANSFORMADO = 'Transformado';

    final public const string EXTINGUIR = 'Extinguir';

    final public const string INICIADO = 'Iniciado';

    final public const string FINALIZADO = 'Finalizado';

    final public const string REABIERTO = 'Reabierto';

    /** @var string[] */
    final public const array ESTADOS_FIJOS = [
        self::PUBLICADO,
        self::BORRADOR,
        self::ARCHIVADO,
        self::ELIMINADO,
        self::SOLICITADO,
        self::RECHAZADO,
        self::APROBADO,
        self::NUEVO,
        self::CLONADO,
        self::MODIFICADO,
        self::TRANSFORMADO,
        self::EXTINGUIR,
        self::INICIADO,
        self::FINALIZADO,
        self::REABIERTO,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $nombre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $descripcion = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $icono = null;

    #[ORM\Column(type: Types::STRING, length: 22, nullable: true)]
    private ?string $color = null;

    #[ORM\ManyToOne(targetEntity: EstadoTipo::class, inversedBy: 'estados')]
    private ?EstadoTipo $tipo = null;

    public function __toString(): string
    {
        return (string) $this->nombre;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getIcono(): ?string
    {
        return $this->icono;
    }

    public function setIcono(?string $icono): static
    {
        $this->icono = $icono;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getTipo(): ?EstadoTipo
    {
        return $this->tipo;
    }

    public function setTipo(?EstadoTipo $tipo): static
    {
        $this->tipo = $tipo;

        return $this;
    }
}

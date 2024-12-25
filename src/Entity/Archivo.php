<?php

namespace App\Entity;

use App\Repository\ArchivoRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Stringable;

#[ORM\Entity(repositoryClass: ArchivoRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['ruta', 'nombre'], name: 'idx_ruta_nombre')]
#[UniqueEntity(fields: ['ruta', 'nombre'], message: 'La imagen ya existe.', errorPath: 'nombre')]
class Archivo extends AbstractController implements Stringable
{
    public const int VER_PRIVADO = 0;
    public const int VER_INTRANET = 1;
    public const int VER_PORTAL = 2;

    public const array ACCESOS_VER = [
        self::VER_PRIVADO,
        self::VER_INTRANET,
        self::VER_PORTAL,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Estado::class)]
    public ?Estado $estado = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank()]
    private ?string $nombre = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $ruta = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    public ?string $tipo = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Usuario $autor = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $creado = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 0, max: 2)]
    public int $acceso_ver = 0;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\GreaterThanOrEqual(0)]
    private int $accesos = 0;

    public function __toString(): string
    {
        return (string) $this->getNombre();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEstado(): ?Estado
    {
        return $this->estado;
    }

    public function setEstado(?Estado $estado): static
    {
        $this->estado = $estado;

        return $this;
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

    public function getRuta(): ?string
    {
        return $this->ruta;
    }

    public function setRuta(string $ruta): static
    {
        $this->ruta = $ruta;

        return $this;
    }


    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function setTipo(?string $tipo): static
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function getAutor(): ?UserInterface
    {
        return $this->autor;
    }

    // TODO ver si se puede hacer automáticamente con un evento PrePersist
    public function setAutor(?Usuario $usuario): static
    {
        $this->autor = $usuario;

        return $this;
    }

    public function getCreado(): ?DateTimeImmutable
    {
        return $this->creado;
    }

    /** Guarda automáticamente la fecha de creación. */
    #[ORM\PrePersist]
    public function setCreado(): static
    {
        $this->creado = new DateTimeImmutable();

        return $this;
    }

    /** Muestra si la operación de ver se gestiona por los accesos, es válida en toda la intranet o en todo el portal. */
    public function getAccesoVer(): int
    {
        return $this->acceso_ver;
    }

    public function setAccesoVer(int $accesoVer = 0): static
    {
        $this->acceso_ver = $accesoVer;

        return $this;
    }

    /** Muestra los accesos permitidos para las operaciones de ver, editar y borrar. */
    public function getAccesos(): int
    {
        return $this->accesos;
    }

    public function setAccesos(int $accesos = 0): static
    {
        $this->accesos = $accesos;

        return $this;
    }

    /** Pone accesos por operación (ver, editar y borrar) */
    public function setPermisos(int $ver = 0, int $editar = 0, int $borrar = 0): static
    {
        $this->setAccesos(100 * $ver + 10 * $editar + $borrar);

        return $this;
    }
}

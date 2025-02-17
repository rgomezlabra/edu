<?php

namespace App\Entity\Cuestiona;

use App\Entity\Estado;
use App\Entity\Usuario;
use App\Repository\Cuestiona\CuestionarioRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entidad para gestionar cuestionarios de preguntas.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: CuestionarioRepository::class)]
#[ORM\Table(name: 'cuestiona_cuestionario')]
#[ORM\Index(fields: ['codigo'], name: 'index_cuestionario_codigo')]
#[UniqueEntity('codigo')]
class Cuestionario implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Estado::class)]
    private ?Estado $estado = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    private ?string $codigo = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private ?string $titulo = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $descripcion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bienvenida = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $despedida = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    private ?Usuario $autor = null;

    /** @var Collection<int, Grupo> */
    #[ORM\OneToMany(mappedBy: 'cuestionario', targetEntity: Grupo::class, cascade: ['persist', 'remove'])]
    private Collection $grupos;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $editable = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $privado = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $fecha_alta = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $fecha_baja = null;

    /** @var array<array-key, mixed> */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $configuracion = null;

    public function __construct()
    {
        $this->grupos = new ArrayCollection();
    }

    #[Override]
    public function __toString(): string
    {
        return (string) $this->codigo;
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

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(?string $codigo): static
    {
        $this->codigo = $codigo;

        return $this;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(?string $titulo): static
    {
        $this->titulo = $titulo;

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

    public function getBienvenida(): ?string
    {
        return $this->bienvenida;
    }

    public function setBienvenida(?string $bienvenida): static
    {
        $this->bienvenida = $bienvenida;

        return $this;
    }

    public function getDespedida(): ?string
    {
        return $this->despedida;
    }

    public function setDespedida(?string $despedida): static
    {
        $this->despedida = $despedida;

        return $this;
    }

    public function getAutor(): ?Usuario
    {
        return $this->autor;
    }

    public function setAutor(?Usuario $usuario): static
    {
        $this->autor = $usuario;

        return $this;
    }

    /** @return Collection<int, Grupo> */
    public function getGrupos(): Collection
    {
        return $this->grupos;
    }

    public function addGrupo(Grupo $grupo): static
    {
        if (!$this->grupos->contains($grupo)) {
            $this->grupos[] = $grupo;
        }

        return $this;
    }

    public function removeGrupo(Grupo $grupo): static
    {
        $this->grupos->removeElement($grupo);

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function isEditable(): bool
    {
        return $this->editable;
    }

    public function setEditable(bool $editable = true): static
    {
        $this->editable = $editable;

        return $this;
    }

    public function isPrivado(): bool
    {
        return $this->privado;
    }

    public function setPrivado(bool $privado = true): static
    {
        $this->privado = $privado;

        return $this;
    }

    public function getFechaAlta(): ?DateTimeImmutable
    {
        return $this->fecha_alta;
    }

    public function setFechaAlta(?DateTimeImmutable $fecha): static
    {
        $this->fecha_alta = $fecha;

        return $this;
    }

    public function getFechaBaja(): ?DateTimeImmutable
    {
        return $this->fecha_baja;
    }

    public function setFechaBaja(?DateTimeImmutable $fecha): static
    {
        $this->fecha_baja = $fecha;

        return $this;
    }

    /** @return array<array-key, mixed> */
    public function getConfiguracion(): ?array
    {
        return $this->configuracion;
    }

    /** @param array<array-key, mixed> $configuracion */
    public function setConfiguracion(?array $configuracion): static
    {
        $this->configuracion = $configuracion;

        return $this;
    }
}

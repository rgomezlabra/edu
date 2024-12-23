<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entidad para gestionar los usuarios del sistema.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[ORM\Index(columns: ['uvus'], name: 'idx_uvus')]
class Usuario implements UserInterface, Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    private ?string $uvus = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\ManyToOne(targetEntity: Origen::class)]
    private ?Origen $origen = null;

    /** @var Collection<int, Relacion> */
    #[ORM\ManyToMany(targetEntity: Relacion::class, cascade: ['persist', 'remove'])]
    private Collection $relaciones;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $uvus_estado = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Email]
    private ?string $correo1 = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Email]
    private ?string $correo2 = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $creado = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $modificado = null;

    #[ORM\OneToOne(mappedBy: 'usuario', targetEntity: Persona::class, cascade: ['persist', 'remove'])]
    private ?Persona $persona = null;

    /** @var Collection<int, Permiso> */
    #[ORM\OneToMany(mappedBy: 'usuario', targetEntity: Permiso::class, cascade: ['persist', 'remove'])]
    private Collection $permisos;

    /** @var Collection<int, Notificacion> */
    #[ORM\OneToMany(mappedBy: 'receptor', targetEntity: Notificacion::class, cascade: ['persist', 'remove'])]
    private Collection $notas;

    public function __construct()
    {
        $this->notas = new ArrayCollection();
        $this->permisos = new ArrayCollection();
        $this->relaciones = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->uvus;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUvus(): ?string
    {
        return $this->uvus;
    }

    public function setUvus(string $uvus): static
    {
        $this->uvus = $uvus;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->uvus;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->uvus;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getOrigen(): ?Origen
    {
        return $this->origen;
    }

    public function setOrigen(?Origen $origen): static
    {
        $this->origen = $origen;

        return $this;
    }

    /**
     * @return Collection<int, Relacion>
     */
    public function getRelaciones(): Collection
    {
        return $this->relaciones;
    }

    public function addRelacion(Relacion $relacion): static
    {
        if (!$this->relaciones->contains($relacion)) {
            $this->relaciones[] = $relacion;
        }

        return $this;
    }

    public function removeRelacion(Relacion $relacion): static
    {
        $this->relaciones->removeElement($relacion);

        return $this;
    }

    public function clearRelaciones(): static
    {
        $this->relaciones->clear();

        return $this;
    }

    public function getUvusEstado(): ?string
    {
        return $this->uvus_estado;
    }

    public function setUvusEstado(?string $uvus_estado): static
    {
        $this->uvus_estado = $uvus_estado;

        return $this;
    }

    public function getCorreo1(): ?string
    {
        return $this->correo1;
    }

    public function setCorreo1(?string $correo1): static
    {
        $this->correo1 = $correo1;

        return $this;
    }

    public function getCorreo2(): ?string
    {
        return $this->correo2;
    }

    public function setCorreo2(?string $correo2): static
    {
        $this->correo2 = $correo2;

        return $this;
    }

    public function getCreado(): ?DateTimeInterface
    {
        return $this->creado;
    }

    public function setCreado(DateTimeInterface $creado): static
    {
        $this->creado = $creado;

        return $this;
    }

    public function getModificado(): ?DateTimeInterface
    {
        return $this->modificado;
    }

    public function setModificado(DateTimeInterface $modificado): static
    {
        $this->modificado = $modificado;

        return $this;
    }

    public function getPersona(): ?Persona
    {
        return $this->persona;
    }

    public function setPersona(Persona $persona): static
    {
        if ($persona->getUsuario() !== $this) {
            $persona->setUsuario($this);
        }

        $this->persona = $persona;

        return $this;
    }

    /**
     * @return Collection<int, Permiso>
     */
    public function getPermisos(): Collection
    {
        return $this->permisos;
    }

    public function addPermiso(Permiso $permiso): static
    {
        if (!$this->permisos->contains($permiso)) {
            $this->permisos[] = $permiso;
            $permiso->setUsuario($this);
        }

        return $this;
    }

    public function removePermiso(Permiso $permiso): static
    {
        if ($this->permisos->removeElement($permiso) && $permiso->getUsuario() === $this) {
            $permiso->setUsuario(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Notificacion>
     */
    public function getNotas(): Collection
    {
        return $this->notas;
    }

    public function addNota(Notificacion $nota): static
    {
        if (!$this->notas->contains($nota)) {
            $this->notas[] = $nota;
            $nota->setReceptor($this);
        }

        return $this;
    }

    public function removeNota(Notificacion $nota): static
    {
        if ($this->notas->removeElement($nota) && $nota->getReceptor() === $this) {
            $nota->setReceptor(null);
        }

        return $this;
    }
}

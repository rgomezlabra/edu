<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use DateTimeInterface;
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
#[ORM\Index(columns: ['login'], name: 'idx_login')]
class Usuario implements UserInterface, Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    private ?string $login = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];



    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $login_estado = null;

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

    #[ORM\OneToOne(mappedBy: 'usuario', targetEntity: Empleado::class, cascade: ['persist', 'remove'])]
    private ?Empleado $empleado = null;




    public function __toString(): string
    {
        return (string) $this->login;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): static
    {
        $this->login = $login;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->login;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->login;
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



    /**
     */






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

    public function getEmpleado(): ?Empleado
    {
        return $this->empleado;
    }

    public function setEmpleado(Empleado $empleado): static
    {
        if ($empleado->getUsuario() !== $this) {
            $empleado->setUsuario($this);
        }

        $this->empleado = $empleado;

        return $this;
    }

    /**
     */



    /**
     */


}

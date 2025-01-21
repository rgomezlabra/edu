<?php

namespace App\Entity\Desempenyo;

use App\Repository\Desempenyo\ServicioRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ServicioRepository::class)]
#[ORM\Table(name: "desempenyo_servicio")]
#[ORM\Index(columns: ['codigo'], name: 'idx_codigo')]
#[UniqueEntity(fields: 'codigo', message: 'Este código ya existe')]
class Servicio implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private ?string $codigo = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private ?string $nombre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $correo = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private ?string $telefono = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private ?string $responsable = null;

    public function __toString(): string
    {
        return (string)$this->codigo;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): static
    {
        $this->codigo = $codigo;

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

    public function getCorreo(): ?string
    {
        return $this->correo;
    }

    public function setCorreo(?string $correo): static
    {
        $this->correo = $correo;

        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): static
    {
        $this->telefono = $telefono;

        return $this;
    }

    public function getResponsable(): ?string
    {
        return $this->responsable;
    }

    public function setResponsable(?string $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }
}

<?php

namespace App\Entity\Plantilla;

use App\Repository\Plantilla\SituacionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Entidad que define las situacions administrativas que pueden tener un empleado.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: SituacionRepository::class)]
#[ORM\Table(name: 'plantilla_situacion')]
#[UniqueEntity('codigo')]
class Situacion implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: false)]
    private ?string $codigo = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $nombre = null;

    public function __toString(): string
    {
        return (string) $this->getCodigo();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): string
    {
        $this->nombre = $nombre;

        return $this;
    }
}

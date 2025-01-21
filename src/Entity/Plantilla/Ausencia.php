<?php

namespace App\Entity\Plantilla;

use App\Repository\Plantilla\AusenciaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Entidad que define las ausencias que pueden tener los trabajadores de la plantilla de la US.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: AusenciaRepository::class)]
#[ORM\Table(name: 'plantilla_ausencia')]
#[UniqueEntity('codigo')]
#[UniqueEntity('nombre')]
class Ausencia implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
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

    public function setNombre(?string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }
}

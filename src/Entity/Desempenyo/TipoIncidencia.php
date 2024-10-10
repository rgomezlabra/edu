<?php

namespace App\Entity\Desempenyo;

use App\Repository\Desempenyo\TipoIncidenciaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Entidad que define tipos de incidencias para evaluación del desempeño.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: TipoIncidenciaRepository::class)]
#[ORM\Table(name: 'desempenyo_tipo_incidencia')]
#[UniqueEntity('nombre')]
class TipoIncidencia implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $nombre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $descripcion = null;

    #[Override]
    public function __toString(): string
    {
        return $this->nombre ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }
}

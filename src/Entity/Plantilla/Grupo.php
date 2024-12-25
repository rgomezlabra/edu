<?php

namespace App\Entity\Plantilla;

use App\Repository\Plantilla\GrupoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Entidad que define grupos profesionales.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: GrupoRepository::class)]
#[ORM\Table(name: 'plantilla_grupo')]
#[UniqueEntity('nombre')]
class Grupo implements Stringable
{
    /** @var string[] */
    final public const array ADSCRIPCIONES = [
        'F' => 'Funcionario',
        'L' => 'Laboral',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $nombre = null;

    #[ORM\Column(type: Types::STRING, length: 1)]
    private ?string $adscripcion = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $nivel_minimo = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $nivel_maximo = null;

    #[Override]
    public function __toString(): string
    {
        return sprintf('%s:%s', $this->adscripcion ?? '', $this->nombre ?? '');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): self
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getAdscripcion(): ?string
    {
        return $this->adscripcion;
    }

    public function setAdscripcion(?string $adscripcion): self
    {
        $this->adscripcion = $adscripcion;

        return $this;
    }

    public function getNivelMinimo(): ?int
    {
        return $this->nivel_minimo;
    }

    public function setNivelMinimo(?int $nivel_minimo): self
    {
        $this->nivel_minimo = $nivel_minimo;

        return $this;
    }

    public function getNivelMaximo(): ?int
    {
        return $this->nivel_maximo;
    }

    public function setNivelMaximo(?int $nivel_maximo): self
    {
        $this->nivel_maximo = $nivel_maximo;

        return $this;
    }

    /** Devolver adscripción y nombre del grupo profesional. */
    public function getAdscripcionNombre(): string
    {
        return sprintf(
            '%s %s',
            self::ADSCRIPCIONES[(string) $this->adscripcion],
            $this->nombre ?? ''
        );
    }
}

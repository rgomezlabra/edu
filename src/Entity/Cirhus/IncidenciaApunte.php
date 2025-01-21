<?php

namespace App\Entity\Cirhus;

use App\Entity\Desempenyo\Servicio;
use App\Entity\Estado;
use App\Entity\Usuario;
use App\Repository\Cirhus\IncidenciaApunteRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncidenciaApunteRepository::class)]
#[ORM\Table(name: "cirhus_incidencia_apunte")]
class IncidenciaApunte
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Incidencia::class, inversedBy: 'apuntes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Incidencia $incidencia = null;

    #[ORM\ManyToOne(targetEntity: Estado::class)]
    private ?Estado $estado = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comentario = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $fecha_inicio = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $fecha_fin = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    private ?Usuario $autor = null;

    #[ORM\ManyToOne(targetEntity: Servicio::class, inversedBy: 'incidencias')]
    private ?Servicio $servicio = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observaciones = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIncidencia(): ?Incidencia
    {
        return $this->incidencia;
    }

    public function setIncidencia(?Incidencia $incidencia): static
    {
        $this->incidencia = $incidencia;

        return $this;
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

    public function getComentario(): ?string
    {
        return $this->comentario;
    }

    public function setComentario(?string $comentario): static
    {
        $this->comentario = $comentario;

        return $this;
    }

    public function getFechaInicio(): ?DateTimeImmutable
    {
        return $this->fecha_inicio;
    }

    public function setFechaInicio(?DateTimeImmutable $fecha): static
    {
        $this->fecha_inicio = $fecha;

        return $this;
    }

    public function getFechaFin(): ?DateTimeImmutable
    {
        return $this->fecha_fin;
    }

    public function setFechaFin(?DateTimeImmutable $fecha): static
    {
        $this->fecha_fin = $fecha;

        return $this;
    }

    public function getAutor(): ?Usuario
    {
        return $this->autor;
    }

    public function setAutor(?Usuario $autor): static
    {
        $this->autor = $autor;

        return $this;
    }

    public function getServicio(): ?Servicio
    {
        return $this->servicio;
    }

    public function setServicio(?Servicio $servicio): static
    {
        $this->servicio = $servicio;

        return $this;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): static
    {
        $this->observaciones = $observaciones;

        return $this;
    }
}
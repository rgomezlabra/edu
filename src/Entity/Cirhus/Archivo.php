<?php

namespace App\Entity\Cirhus;

use App\Repository\Cirhus\ArchivoRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArchivoRepository::class)]
#[ORM\Table(name: "cirhus_archivo")]
class Archivo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Incidencia::class, inversedBy: 'archivos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Incidencia $incidencia = null;

    #[ORM\OneToOne(targetEntity: \App\Entity\Archivo::class)]
    private ?\App\Entity\Archivo $archivo = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $fecha = null;

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

    public function getArchivo(): ?\App\Entity\Archivo
    {
        return $this->archivo;
    }

    public function setArchivo(?\App\Entity\Archivo $archivo): static
    {
        $this->archivo = $archivo;

        return $this;
    }

    public function getFecha(): ?DateTimeImmutable
    {
        return $this->fecha;
    }

    public function setFecha(?DateTimeImmutable $fecha): static
    {
        $this->fecha = $fecha;

        return $this;
    }

}

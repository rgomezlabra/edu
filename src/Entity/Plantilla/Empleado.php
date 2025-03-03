<?php

namespace App\Entity\Plantilla;

use App\Entity\Usuario;
use App\Repository\Plantilla\EmpleadoRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad que define los datos de los empleados de la plantilla actual de la Universidad.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: EmpleadoRepository::class)]
#[ORM\Table(name: 'plantilla_empleado')]
class Empleado
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'empleado', targetEntity: Usuario::class)]
    private ?Usuario $usuario = null;

    #[ORM\ManyToOne(targetEntity: Situacion::class)]
    private ?Situacion $situacion = null;

    #[ORM\ManyToOne(targetEntity: Grupo::class)]
    private ?Grupo $grupo = null;

    #[ORM\ManyToOne(targetEntity: Unidad::class)]
    private ?Unidad $unidad = null;

    #[ORM\ManyToOne(targetEntity: Ausencia::class)]
    private ?Ausencia $ausencia = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $nrp = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $nivel = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeInterface $vigente = null;

    #[ORM\ManyToOne(targetEntity: Empleado::class)]
    public ?Empleado $validador = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    public ?DateTimeInterface $cesado = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $consolidado = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeInterface $consolidacion = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $antiguedad = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $en_titular = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $en_ocupada = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private ?string $apellidos = null;

    #[ORM\Column(type: Types::STRING, length: 11, nullable: true)]
    private ?string $doc_identidad = null;

    public function __toString(): string
    {
        return trim(sprintf('%s %s', $this->nombre ?? '', $this->getApellidos() ?? ''));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getSituacion(): ?Situacion
    {
        return $this->situacion;
    }

    public function setSituacion(?Situacion $situacion): static
    {
        $this->situacion = $situacion;

        return $this;
    }

    public function getGrupo(): ?Grupo
    {
        return $this->grupo;
    }

    public function setGrupo(?Grupo $grupo): static
    {
        $this->grupo = $grupo;

        return $this;
    }

    public function getUnidad(): ?Unidad
    {
        return $this->unidad;
    }

    public function setUnidad(?Unidad $unidad): static
    {
        $this->unidad = $unidad;

        return $this;
    }

    public function getAusencia(): ?Ausencia
    {
        return $this->ausencia;
    }

    public function setAusencia(?Ausencia $ausencia): static
    {
        $this->ausencia = $ausencia;

        return $this;
    }

    public function getNrp(): ?string
    {
        return $this->nrp;
    }

    public function setNrp(?string $nrp): static
    {
        $this->nrp = $nrp;

        return $this;
    }

    public function getNivel(): ?int
    {
        return $this->nivel;
    }

    public function setNivel(?int $nivel): static
    {
        $this->nivel = $nivel;

        return $this;
    }

    public function getVigente(): ?DateTimeInterface
    {
        return $this->vigente;
    }

    public function setVigente(?DateTimeInterface $vigente): static
    {
        $this->vigente = $vigente;

        return $this;
    }

    public function getValidador(): ?Empleado
    {
        return $this->validador;
    }

    public function setValidador(?Empleado $empleado): static
    {
        $this->validador = $empleado;

        return $this;
    }

    public function getCesado(): ?DateTimeInterface
    {
        return $this->cesado;
    }

    public function setCesado(?DateTimeInterface $cesado): static
    {
        $this->cesado = $cesado;

        return $this;
    }

    public function getConsolidado(): ?int
    {
        return $this->consolidado;
    }

    public function setConsolidado(?int $consolidado): static
    {
        $this->consolidado = $consolidado;

        return $this;
    }

    public function getConsolidacion(): ?DateTimeInterface
    {
        return $this->consolidacion;
    }

    public function setConsolidacion(?DateTimeInterface $consolidacion): static
    {
        $this->consolidacion = $consolidacion;

        return $this;
    }

    public function getAntiguedad(): ?int
    {
        return $this->antiguedad;
    }

    public function setAntiguedad(?int $periodo): static
    {
        $this->antiguedad = $periodo;

        return $this;
    }

    public function getEnTitular(): ?int
    {
        return $this->en_titular;
    }

    public function setEnTitular(?int $periodo): static
    {
        $this->en_titular = $periodo;

        return $this;
    }

    public function getEnOcupada(): ?int
    {
        return $this->en_ocupada;
    }

    public function setEnOcupada(?int $periodo): static
    {
        $this->en_ocupada = $periodo;

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

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    public function setApellidos(string $apellidos): static
    {
        $this->apellidos = $apellidos;

        return $this;
    }

    public function getDocIdentidad(): ?string
    {
        return $this->doc_identidad;
    }

    public function setDocIdentidad(?string $doc_identidad): static
    {
        $this->doc_identidad = $doc_identidad;

        return $this;
    }
}

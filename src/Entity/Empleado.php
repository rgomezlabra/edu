<?php

namespace App\Entity;

use App\Entity\Categoria;
use App\Entity\Grupo;
use App\Entity\Persona;
use App\Repository\EmpleadoRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad que define los datos de los empleados de la plantilla actual de la US.
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

    #[ORM\OneToOne(targetEntity: Persona::class)]
    private ?Persona $persona = null;

    #[ORM\OneToOne(inversedBy: 'titular', targetEntity: Plaza::class, cascade: ['persist'])]
    private ?Plaza $plaza_titular = null;

    #[ORM\ManyToOne(targetEntity: Plaza::class, cascade: ['persist'], inversedBy: 'ocupantes')]
    private ?Plaza $plaza_ocupada = null;

    #[ORM\ManyToOne(targetEntity: Categoria::class)]
    private ?Categoria $categoria = null;

    #[ORM\ManyToOne(targetEntity: Regimen::class)]
    private ?Regimen $regimen = null;

    #[ORM\ManyToOne(targetEntity: Situacion::class)]
    private ?Situacion $situacion = null;

    #[ORM\ManyToOne(targetEntity: Jornada::class)]
    private ?Jornada $jornada = null;

    #[ORM\ManyToOne(targetEntity: Grupo::class)]
    private ?Grupo $grupo = null;

    #[ORM\ManyToOne(targetEntity: Ausencia::class)]
    private ?Ausencia $ausencia = null;

    #[ORM\ManyToOne(targetEntity: Especialidad::class)]
    private ?Especialidad $especialidad = null;

    #[ORM\ManyToOne(targetEntity: Academico::class)]
    private ?Academico $academico = null;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPersona(): ?Persona
    {
        return $this->persona;
    }

    public function setPersona(?Persona $persona): self
    {
        $this->persona = $persona;

        return $this;
    }

    public function getPlazaTitular(): ?Plaza
    {
        return $this->plaza_titular;
    }

    public function setPlazaTitular(?Plaza $plaza): self
    {
        $this->plaza_titular = $plaza;

        return $this;
    }

    public function getPlazaOcupada(): ?Plaza
    {
        return $this->plaza_ocupada;
    }

    public function setPlazaOcupada(?Plaza $plaza): self
    {
        $this->plaza_ocupada = $plaza;

        return $this;
    }

    public function getCategoria(): ?Categoria
    {
        return $this->categoria;
    }

    public function setCategoria(?Categoria $categoria): self
    {
        $this->categoria = $categoria;

        return $this;
    }

    public function getRegimen(): ?Regimen
    {
        return $this->regimen;
    }

    public function setRegimen(?Regimen $regimen): self
    {
        $this->regimen = $regimen;

        return $this;
    }

    public function getSituacion(): ?Situacion
    {
        return $this->situacion;
    }

    public function setSituacion(?Situacion $situacion): self
    {
        $this->situacion = $situacion;

        return $this;
    }

    public function getJornada(): ?Jornada
    {
        return $this->jornada;
    }

    public function setJornada(?Jornada $jornada): self
    {
        $this->jornada = $jornada;

        return $this;
    }

    public function getGrupo(): ?Grupo
    {
        return $this->grupo;
    }

    public function setGrupo(?Grupo $grupo): self
    {
        $this->grupo = $grupo;

        return $this;
    }

    public function getAusencia(): ?Ausencia
    {
        return $this->ausencia;
    }

    public function setAusencia(?Ausencia $ausencia): self
    {
        $this->ausencia = $ausencia;

        return $this;
    }

    public function getEspecialidad(): ?Especialidad
    {
        return $this->especialidad;
    }

    public function setEspecialidad(?Especialidad $especialidad): self
    {
        $this->especialidad = $especialidad;

        return $this;
    }

    public function getAcademico(): ?Academico
    {
        return $this->academico;
    }

    public function setAcademico(?Academico $academico): self
    {
        $this->academico = $academico;

        return $this;
    }

    public function getNrp(): ?string
    {
        return $this->nrp;
    }

    public function setNrp(?string $nrp): self
    {
        $this->nrp = $nrp;

        return $this;
    }

    public function getNivel(): ?int
    {
        return $this->nivel;
    }

    public function setNivel(?int $nivel): self
    {
        $this->nivel = $nivel;

        return $this;
    }

    public function getVigente(): ?DateTimeInterface
    {
        return $this->vigente;
    }

    public function setVigente(?DateTimeInterface $vigente): self
    {
        $this->vigente = $vigente;

        return $this;
    }

    public function getValidador(): ?Empleado
    {
        return $this->validador;
    }

    public function setValidador(?Empleado $empleado): self
    {
        $this->validador = $empleado;

        return $this;
    }

    public function getCesado(): ?DateTimeInterface
    {
        return $this->cesado;
    }

    public function setCesado(?DateTimeInterface $cesado): self
    {
        $this->cesado = $cesado;

        return $this;
    }

    public function getConsolidado(): ?int
    {
        return $this->consolidado;
    }

    public function setConsolidado(?int $consolidado): self
    {
        $this->consolidado = $consolidado;

        return $this;
    }

    public function getConsolidacion(): ?DateTimeInterface
    {
        return $this->consolidacion;
    }

    public function setConsolidacion(?DateTimeInterface $consolidacion): self
    {
        $this->consolidacion = $consolidacion;

        return $this;
    }

    public function getAntiguedad(): ?int
    {
        return $this->antiguedad;
    }

    public function setAntiguedad(?int $periodo): self
    {
        $this->antiguedad = $periodo;

        return $this;
    }

    public function getEnTitular(): ?int
    {
        return $this->en_titular;
    }

    public function setEnTitular(?int $periodo): self
    {
        $this->en_titular = $periodo;

        return $this;
    }

    public function getEnOcupada(): ?int
    {
        return $this->en_ocupada;
    }

    public function setEnOcupada(?int $periodo): self
    {
        $this->en_ocupada = $periodo;

        return $this;
    }
}

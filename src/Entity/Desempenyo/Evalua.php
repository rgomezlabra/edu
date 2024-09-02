<?php

namespace App\Entity\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Plantilla\Empleado;
use App\Repository\Desempenyo\EvaluaRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad para gestionar permisos de valuación de desempeño (relación evaluador y empleado evaluado).
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: EvaluaRepository::class)]
#[ORM\Table(name: 'desempenyo_evalua')]
#[ORM\Index(
    columns: ['cuestionario_id', 'empleado_id', 'evaluador_id'],
    name: 'desempenyo_cuestionario_empleado_evaluador_idx'
)]
class Evalua
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Empleado::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Empleado $empleado = null;

    #[ORM\ManyToOne(targetEntity: Empleado::class)]
    private ?Empleado $evaluador = null;

    #[ORM\ManyToOne(targetEntity: Cuestionario::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cuestionario $cuestionario = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $fecha_rechazo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmpleado(): ?Empleado
    {
        return $this->empleado;
    }

    public function setEmpleado(?Empleado $empleado): static
    {
        $this->empleado = $empleado;

        return $this;
    }

    public function getEvaluador(): ?Empleado
    {
        return $this->evaluador;
    }

    public function setEvaluador(?Empleado $evaluador): static
    {
        $this->evaluador = $evaluador;

        return $this;
    }

    public function getCuestionario(): ?Cuestionario
    {
        return $this->cuestionario;
    }

    public function setCuestionario(?Cuestionario $cuestionario): static
    {
        $this->cuestionario = $cuestionario;

        return $this;
    }

    public function getFechaRechazo(): ?DateTimeImmutable
    {
        return $this->fecha_rechazo;
    }

    public function setFechaRechazo(?DateTimeImmutable $fecha_rechazo): void
    {
        $this->fecha_rechazo = $fecha_rechazo;
    }
}

<?php

namespace App\Entity\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Plantilla\Empleado;
use App\Repository\Desempenyo\EvaluaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad para gestionar permisos de valuación de desempeño (relación evaluador y empleado evaluado).
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: EvaluaRepository::class)]
#[ORM\Table(name: 'desempemnyo_evalua')]
class Evalua
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Empleado::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Empleado $evaluando = null;

    #[ORM\ManyToOne(targetEntity: Empleado::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Empleado $evaluador = null;

    #[ORM\ManyToOne(targetEntity: Cuestionario::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cuestionario $cuestionario = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvaluando(): ?Empleado
    {
        return $this->evaluando;
    }

    public function setEvaluando(?Empleado $evaluando): static
    {
        $this->evaluando = $evaluando;

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
}

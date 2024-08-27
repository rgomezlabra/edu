<?php

namespace App\Entity\Desempenyo;

use App\Entity\Plantilla\Empleado;
use App\Repository\Desempenyo\FormularioRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad para gestionar formularios de valuación.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: FormularioRepository::class)]
#[ORM\Table(name: 'desempemnyo_formulario')]
class Formulario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Empleado::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Empleado $empleado = null;

    #[ORM\ManyToOne(targetEntity: Empleado::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Empleado $evaluador = null;

    #[ORM\ManyToOne(targetEntity: Formulario::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Formulario $formulario = null;

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

    public function getFormulario(): ?Formulario
    {
        return $this->formulario;
    }

    public function setFormulario(?Formulario $formulario): static
    {
        $this->formulario = $formulario;

        return $this;
    }
}

<?php

namespace App\Entity\Desempenyo;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Formulario;
use App\Entity\Plantilla\Empleado;
use App\Entity\Sistema\Origen;
use App\Entity\Sistema\Usuario;
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
    // Tipos de evaluaciones
    public const int NO_EVALUACION = 0; // Solicitud de no evaluación

    public const int AUTOEVALUACION = 1;    // Autoevaluación

    public const int EVALUA_RESPONSABLE = 2;    // Evaluación por el responsable

    public const int EVALUA_OTRO = 3;   // Evaluación por otro agente

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Empleado::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Empleado $empleado = null;

    #[ORM\ManyToOne(targetEntity: Empleado::class)]
    private ?Empleado $evaluador = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: false, options: ['default' => 1])]
    private int $tipo_evaluador = self::AUTOEVALUACION;

    #[ORM\ManyToOne(targetEntity: Cuestionario::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cuestionario $cuestionario = null;

    #[ORM\ManyToOne(targetEntity: Formulario::class)]
    private ?Formulario $formulario = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $rechazado = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $correccion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comentario = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    private ?Usuario $corrector = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $corregido = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => 0])]
    private bool $habilita = false;

    #[ORM\ManyToOne(targetEntity: Origen::class)]
    private ?Origen $origen = null;

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

    public function getTipoEvaluador(): int
    {
        return $this->tipo_evaluador;
    }

    public function setTipoEvaluador(int $tipo = self::AUTOEVALUACION): static
    {
        $this->tipo_evaluador = $tipo;

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

    public function getFormulario(): ?Formulario
    {
        return $this->formulario;
    }

    public function setFormulario(?Formulario $formulario): static
    {
        $this->formulario = $formulario;

        return $this;
    }

    public function getRechazado(): ?DateTimeImmutable
    {
        return $this->rechazado;
    }

    public function setRechazado(?DateTimeImmutable $fecha): static
    {
        $this->rechazado = $fecha;

        return $this;
    }

    public function getCorreccion(): ?float
    {
        return $this->correccion;
    }

    public function setCorreccion(?float $puntuacion): static
    {
        $this->correccion = $puntuacion;

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

    public function getCorrector(): ?Usuario
    {
        return $this->corrector;
    }

    public function setCorrector(?Usuario $corrector): static
    {
        $this->corrector = $corrector;

        return $this;
    }

    public function getCorregido(): ?DateTimeImmutable
    {
        return $this->corregido;
    }

    public function setCorregido(?DateTimeImmutable $fecha): static
    {
        $this->corregido = $fecha;

        return $this;
    }

    public function isHabilita(): bool
    {
        return $this->habilita;
    }

    public function setHabilita(bool $habilita = true): static
    {
        $this->habilita = $habilita;

        return $this;
    }

    public function getOrigen(): ?Origen
    {
        return $this->origen;
    }

    public function setOrigen(?Origen $origen): static
    {
        $this->origen = $origen;

        return $this;
    }
}

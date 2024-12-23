<?php

namespace App\Entity\Cuestiona;

use App\Entity\Usuario;
use App\Repository\Cuestiona\FormularioRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad para gestionar formularios de preguntas rellenas por usuarios.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: FormularioRepository::class)]
#[ORM\Table(name: 'cuestiona_formulario')]
class Formulario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cuestionario::class)]
    private ?Cuestionario $cuestionario = null;
    
    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    private ?Usuario $usuario = null;

    /** @var Collection<int, Respuesta> */
    #[ORM\OneToMany(mappedBy: 'formulario', targetEntity: Respuesta::class)]
    private Collection $respuestas;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $fecha_grabacion = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $fecha_envio = null;

    public function __construct()
    {
        $this->respuestas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $Usuario): static
    {
        $this->usuario = $Usuario;

        return $this;
    }

    /** @return Collection<int, Respuesta> */
    public function getRespuestas(): Collection
    {
        return $this->respuestas;
    }

    public function addRespuesta(Respuesta $respuesta): static
    {
        if (!$this->respuestas->contains($respuesta)) {
            $this->respuestas[] = $respuesta;
            $respuesta->setFormulario($this);
        }

        return $this;
    }

    public function removeRespuesta(Respuesta $respuesta): static
    {
        if ($this->respuestas->removeElement($respuesta) && $respuesta->getFormulario() === $this) {
            $respuesta->setFormulario(null);
        }

        return $this;
    }

    public function getFechaGrabacion(): ?DateTimeImmutable
    {
        return $this->fecha_grabacion;
    }

    public function setFechaGrabacion(?DateTimeImmutable $fecha): static
    {
        $this->fecha_grabacion = $fecha;

        return $this;
    }

    public function getFechaEnvio(): ?DateTimeImmutable
    {
        return $this->fecha_envio;
    }

    public function setFechaEnvio(?DateTimeImmutable $fecha): static
    {
        $this->fecha_envio = $fecha;

        return $this;
    }
}

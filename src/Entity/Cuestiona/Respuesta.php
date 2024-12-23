<?php

namespace App\Entity\Cuestiona;

use App\Repository\Cuestiona\RespuestaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad para gestionar las respuestas de los usuarios.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: RespuestaRepository::class)]
#[ORM\Table(name: 'cuestiona_respuesta')]
class Respuesta
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Formulario::class, cascade: ['persist'], inversedBy: 'respuestas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Formulario $formulario = null;

    #[Orm\ManyToOne(targetEntity: Pregunta::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pregunta $pregunta = null;

    /** @var array<array-key, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $valor = [];

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPregunta(): ?Pregunta
    {
        return $this->pregunta;
    }

    public function setPregunta(?Pregunta $pregunta): static
    {
        $this->pregunta = $pregunta;

        return $this;
    }

    /** @return array<array-key, mixed> */
    public function getValor(): array
    {
        return $this->valor;
    }

    /** @param array<array-key, mixed> $valor */
    public function setValor(array $valor = []): static
    {
        $this->valor = $valor;

        return $this;
    }
}

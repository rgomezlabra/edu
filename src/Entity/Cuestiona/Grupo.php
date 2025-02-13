<?php

namespace App\Entity\Cuestiona;

use App\Repository\Cuestiona\GrupoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Override;
use Stringable;

/**
 * Entidad para gestionar grupos de preguntas.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: GrupoRepository::class)]
#[ORM\Table(name: 'cuestiona_grupo')]
#[ORM\Index(fields: ['cuestionario', 'orden'], name: 'idx_grupo_cuestionario_orden')]
class Grupo implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cuestionario::class, inversedBy: 'grupos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cuestionario $cuestionario = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private ?bool $activa = true;

    #[Gedmo\SortablePosition]
    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $orden = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private ?string $codigo = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $titulo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descripcion = null;

    /** @var Collection<int, Pregunta> */
    #[ORM\OneToMany(mappedBy: 'grupo', targetEntity: Pregunta::class, cascade: ['persist', 'remove'])]
    private Collection $preguntas;

    public function __construct()
    {
        $this->preguntas = new ArrayCollection();
    }

    #[Override]
    public function __toString(): string
    {
        return sprintf('%s. %s', $this->codigo ?? '', $this->titulo ?? '');
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

    public function getActiva(): ?bool
    {
        return $this->activa;
    }

    public function setActiva(?bool $activa): static
    {
        $this->activa = $activa;

        return $this;
    }

    public function getOrden(): ?int
    {
        return $this->orden;
    }

    public function setOrden(?int $orden): static
    {
        $this->orden = $orden;

        return $this;
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(?string $codigo): static
    {
        $this->codigo = $codigo;

        return $this;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(?string $titulo): static
    {
        $this->titulo = $titulo;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    /** @return Collection<int, Pregunta> */
    public function getPreguntas(): Collection
    {
        return $this->preguntas;
    }

    public function addPregunta(Pregunta $pregunta): static
    {
        if (!$this->preguntas->contains($pregunta)) {
            $this->preguntas[] = $pregunta;
        }

        return $this;
    }

    public function removePregunta(Pregunta $pregunta): static
    {
        $this->preguntas->removeElement($pregunta);

        return $this;
    }
}

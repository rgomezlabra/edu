<?php

namespace App\Entity\Cuestiona;

use App\Repository\Cuestiona\PreguntaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Override;
use Stringable;

/**
 * Entidad para gestionar las preguntas de un cuestionario.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: PreguntaRepository::class)]
#[ORM\Table(name: 'cuestiona_pregunta')]
#[ORM\Index(fields: ['grupo', 'orden'], name: 'idx_pregunta_grupo_orden')]
class Pregunta implements Stringable
{
    // Preguntas abiertas
    final public const int TEXTO = 11;

    final public const int AREA = 12;

    final public const int EMAIL = 13;

    final public const int URL = 14;

    final public const int TEL = 15;

    final public const int CLAVE = 16;

    final public const int NUMERO = 17;

    // Preguntas de selección única
    final public const int CASILLA = 21;

    final public const int RADIO = 22;

    final public const int DICOTOMICA = 23;

    final public const int MENU = 24;

    final public const int LISTA = 25;

    final public const int RANGO = 26;

    final public const int FECHA = 27;

    final public const int FECHAHORA = 28;

    // Preguntas con selección múltiple
    final public const int CASILLA_MULTIPLE = 31;

    final public const int MENU_MULTIPLE = 32;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Grupo::class, cascade: ['persist'], inversedBy: 'preguntas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Grupo $grupo = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool $activa = true;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool $opcional = false;

    #[Gedmo\SortablePosition]
    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $orden = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private ?string $codigo = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $titulo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ayuda = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: false)]
    private int $tipo = 0;

    /** @var array<array-key, mixed> */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $opciones = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $reducida = false;

    #[Override]
    public function __toString(): string
    {
        return sprintf('%s. %s', $this->codigo ?? '', $this->titulo ?? '');
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isActiva(): bool
    {
        return $this->activa;
    }

    public function setActiva(bool $activa = true): static
    {
        $this->activa = $activa;

        return $this;
    }

    public function isOpcional(): bool
    {
        return $this->opcional;
    }

    public function setOpcional(bool $opcional = false): static
    {
        $this->opcional = $opcional;

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

    public function getAyuda(): ?string
    {
        return $this->ayuda;
    }

    public function setAyuda(?string $ayuda): static
    {
        $this->ayuda = $ayuda;

        return $this;
    }

    public function getTipo(): int
    {
        return $this->tipo;
    }

    public function setTipo(int $tipo): static
    {
        $this->tipo = $tipo;

        return $this;
    }

    /** @return array<array-key, mixed>|null */
    public function getOpciones(): ?array
    {
        return $this->opciones;
    }

    /** @param array<array-key, mixed>|null $opciones */
    public function setOpciones(?array $opciones): static
    {
        $this->opciones = $opciones;

        return $this;
    }

    public function isReducida(): bool
    {
        return $this->reducida;
    }

    public function setReducida(bool $reducida = false): static
    {
        $this->reducida = $reducida;

        return $this;
    }
}

<?php

namespace App\Entity\Cirhus;

use App\Entity\Aplicacion;
use App\Entity\Archivo;
use App\Entity\Usuario;
use App\Repository\Cirhus\IncidenciaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncidenciaRepository::class)]
#[ORM\Table(name: "cirhus_incidencia")]
class Incidencia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Aplicacion::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Aplicacion $aplicacion = null;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private ?string $descripcion = null;

    /** @var Collection<int, Archivo> */
    #[ORM\ManyToMany(targetEntity: Archivo::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'cirhus_incidencia_archivo')]
    private Collection $archivos;

    /** @var Collection<int, IncidenciaApunte> */
    #[ORM\OneToMany(mappedBy: 'incidencia', targetEntity: IncidenciaApunte::class, cascade: ['persist'])]
    public Collection $apuntes;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Usuario $solicitante = null;

    public function __construct()
    {
        $this->archivos = new ArrayCollection();
        $this->apuntes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string)$this->descripcion;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAplicacion(): ?Aplicacion
    {
        return $this->aplicacion;
    }

    public function setAplicacion(?Aplicacion $aplicacion): static
    {
        $this->aplicacion = $aplicacion;

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

    /**
     * @return Collection<int, Archivo>
     */
    public function getArchivos(): Collection
    {
        return $this->archivos;
    }

    public function addArchivo(Archivo $archivo): static
    {
        if (!$this->archivos->contains($archivo)) {
            $this->archivos[] = $archivo;
        }

        return $this;
    }

    public function removeArchivo(Archivo $archivo): static
    {
        $this->archivos->removeElement($archivo);

        return $this;
    }

    /**
     * @return Collection<int, IncidenciaApunte>
     */
    public function getApuntes(): Collection
    {
        return $this->apuntes;
    }

    public function addApunte(IncidenciaApunte $apunte): static
    {
        if (!$this->apuntes->contains($apunte)) {
            $this->apuntes[] = $apunte;
        }

        return $this;
    }

    public function removeApunte(IncidenciaApunte $apunte): static
    {
        $this->apuntes->removeElement($apunte);

        return $this;
    }

    public function getSolicitante(): ?Usuario
    {
        return $this->solicitante;
    }

    public function setSolicitante(?Usuario $solicitante): static
    {
        $this->solicitante = $solicitante;

        return $this;
    }
}

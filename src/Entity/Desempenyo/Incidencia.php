<?php

namespace App\Entity\Desempenyo;

use App\Entity\Cirhus\Incidencia as IncidenciaCirhus;
use App\Repository\Desempenyo\IncidenciaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad que define incidencias para evaluación del desempeño.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
#[ORM\Entity(repositoryClass: IncidenciaRepository::class)]
#[ORM\Table(name: 'desempenyo_incidencia')]
class Incidencia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: IncidenciaCirhus::class)]
    private ?IncidenciaCirhus $incidencia = null;

    #[ORM\ManyToOne(targetEntity: TipoIncidencia::class)]
    private ?TipoIncidencia $tipo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIncidencia(): ?IncidenciaCirhus
    {
        return $this->incidencia;
    }

    public function setIncidencia(?IncidenciaCirhus $incidencia): static
    {
        $this->incidencia = $incidencia;

        return $this;
    }

    public function getTipo(): ?TipoIncidencia
    {
        return $this->tipo;
    }

    public function setTipo(?TipoIncidencia $tipo): static
    {
        $this->tipo = $tipo;

        return $this;
    }
}

<?php

namespace App\Form\DataTransformer;

use App\Entity\Plantilla\Empleado;
use App\Repository\Plantilla\EmpleadoRepository;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforma entre objeto de empleado y cadena con su documento de identidad.
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @implements DataTransformerInterface<Empleado, string>
 */
class EmpleadoDniTransformer implements DataTransformerInterface
{
    public function __construct(protected EmpleadoRepository $empleadoRepository)
    {
    }

    /**
     * Transforma objeto Empleado en cadena con el documento de identidad.
     * @param Empleado|null $value
     */
    public function transform($value): string
    {
        return $value instanceof Empleado ? (string) $value->getDocIdentidad() : '';
    }

    /**
     * Transforma cadena con documento de identidad en objeto Empleado.
     * @param string $value
     */
    public function reverseTransform($value): ?Empleado
    {
        return ('' === $value || null === $value) ? null : $this->empleadoRepository->findOneByDocumento($value);
    }
}

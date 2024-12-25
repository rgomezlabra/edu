<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transformar JSON en array y viceversa.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class JsonTransformer implements DataTransformerInterface
{
    /** @inheritDoc */
    public function transform(mixed $value): mixed {
        return empty($value) ? json_encode([]) : json_encode($value);

    }

    /** @inheritDoc */
    public function reverseTransform(mixed $value): mixed {
        return empty($value) ? [] : json_decode($value, true);

    }
}
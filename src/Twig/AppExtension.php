<?php

namespace App\Twig;

use App\Service\Slug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

/**
 * Servicio para añadir variables de sesión y filtros a las plantillas Twig.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    /** Añadir variables globales. */
    public function getGlobals(): array
    {
        $titulo = null;
        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            // Obtener título de la página definido en el controlador
            $titulo = (string) $request->attributes->get('titulo');
        }

        return [
            'titulo' => $titulo,
        ];
    }

    /** Añadir filtros específicos. */
    public function getFilters(): array
    {
        return [
            // Sustituir expresiones regulares usando la función PHP "preg_replace"
            new TwigFilter(
                'regex_replace',
                static fn (array|string $origen, array|string $patron, array|string $reemplazo, int $limite = -1): array|string|null => preg_replace($patron, $reemplazo, $origen, $limite)
            ),
            // Obtener una ruta a partir de una cadena
            new TwigFilter(
                'slug',
                static fn (string $cadena, string $separador = '-'): string => (new Slug())($cadena, $separador)
            ),
            // Convertir objeto JSON en array
            new TwigFilter(
                'json_decode',
                static fn (string $cadena): mixed => json_decode($cadena, true, 512, JSON_OBJECT_AS_ARRAY)
            ),
            // Sumar los valores de un array
            new TwigFilter(
                'sum',
                static fn (array $datos): float|int => array_sum($datos),
            )
        ];
    }
}

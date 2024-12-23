<?php

namespace App\Service;

use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Clase para convertir cadenas en rutas y URL.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class Slug
{
    /**
     * Quita los acentos y caracteres especiales de una cadena para usarla en una URL.
     * @param string $cadena Cadena a convertir en URL.
     * @param string $separador Carácter separador de palabras, '-' por defecto.
     */
    public function __invoke(string $cadena, string $separador = '-'): string
    {
        $slug = new AsciiSlugger();

        return $slug->slug($cadena, $separador)->lower()->toString();
    }
}

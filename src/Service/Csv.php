<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Process\Process;

/**
 * Clase para tratar ficheros CSV subidos al servidor en un formulario.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class Csv
{
    /** @var resource|null */
    private mixed $recurso = null;

    /** @var string[] Cabeceras del fichero CSV */
    private array $cabeceras = [];

    /** @var string Fichero convertido desde Excel. */
    private string $convertido = '';

    public function __construct(private string $separador = ',', private int $numCampos = 0)
    {
    }

    public function __destruct()
    {
        $this->cerrar();
    }

    /**
     * Abre un fichero CSV subido al servidor si tiene el número adecuado de campos y devuelve los datos de la cabecera.
     * @return string[]|null
     */
    public function abrir(UploadedFile $fichero, string $separador = ',', int $numCampos = 0): ?array
    {
        $cabeceras = [];
        $this->recurso = null;
        // Convertir Excel a CSV, si es necesario
        $fich = 'application/vnd.ms-excel' === $fichero->getClientMimeType() ? $this->convertirDesdeExcel($fichero) : $fichero->getClientOriginalName();
        if (0 !== preg_match('#.csv$#i', $fich)) {
            $fich = '' === $this->convertido ? $fichero->getRealPath() : $fich;
            if (false !== ($this->recurso = fopen($fich, 'r'))) {
                $cabeceras = fgetcsv($this->recurso, 0, $separador);
                $n = false !== $cabeceras ? count($cabeceras) : 0;
                if (in_array($numCampos, [0, $n])) {
                    // Ignorar BOM
                    if (str_starts_with((string) $cabeceras[0], "\xEF\xBB\xBF")) {
                        $cabeceras[0] = substr((string) $cabeceras[0], 3);
                    }

                    $this->separador = $separador;
                    $this->numCampos = $n;
                    $this->cabeceras = false !== $cabeceras ? $cabeceras : [];
                } else {
                    $this->recurso = null;
                }
            } else {
                $this->recurso = null;
            }
        }

        return (null !== $this->recurso && false !== $cabeceras) ? $cabeceras : null;
    }

    /**
     * Devuelve un array con los campos leídos en una línea del fichero CSV, pueden especificarse cabeceras específicas.
     * @param string[] $campos
     * @return string[]|null
     */
    public function leer(array $campos = []): ?array
    {
        if (null === $this->recurso) {
            return null;
        }

        $linea = fgetcsv($this->recurso, 0, $this->separador);
        if (false === $linea || count($linea) !== $this->numCampos) {
            return null;
        }

        if ([] === $campos) {
            return $linea;
        }

        /** @var int[] $cabeceras */
        $cabeceras = array_flip($this->cabeceras);
        $datos = [];
        foreach ($campos as $campo) {
            $datos[$campo] = $linea[$cabeceras[$campo]];
        }

        return $datos;
    }

    /** Cerrar un fichero abierto. */
    public function cerrar(): void
    {
        if (is_resource($this->recurso)) {
            fclose($this->recurso);
            // Eliminar fichero convertido desde Excel tras cerrarlo, si es necesario
            if ('' !== $this->convertido && file_exists($this->convertido)) {
                unlink($this->convertido);
            }
        }

        $this->recurso = null;
    }

    /**
     * Obtiene las cabeceras del fichero CSV.
     * @return string[]
     */
    public function getCabeceras(): array
    {
        return $this->cabeceras;
    }

    /**
     * Comprueba si las cabeceras del fichero de volcado CSV son correctas (están en la lista de campos).
     * @param string[] $campos
     */
    public function comprobarCabeceras(array $campos): bool
    {
        return array_values(array_intersect($this->getCabeceras(), $campos)) === array_values($campos);
    }

    /** Convierte un fichero Excel en CSV para que pueda ser procesado. */
    private function convertirDesdeExcel(UploadedFile $fichero): string
    {
        $process = new Process(['ssconvert', $fichero->getRealPath(), $fichero->getRealPath() . '.csv']);
        $process->run();

        $this->convertido = $process->isSuccessful() ? $fichero->getRealPath() . '.csv' : '';

        return $this->convertido;
    }
}

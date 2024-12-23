<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

readonly class MessageGenerator
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * Crear incidencia y mostrar mensaje flash.
     * @param string $level   Nivel de log
     * @param string $message Mensaje
     * @param array  $params  Parámetros adicionales
     */
    public function logAndFlash(string $level, string $message, array $params = []): void
    {
        if (!in_array($level, ['alert', 'critical', 'debug', 'emergency', 'error', 'info', 'notice', 'warning'])) {
            $level = 'notice';
        }

        $session = $this->requestStack->getSession();

        if (isset($params['id'])) {
            $message .= ': '.$params['id'];
        }

        $session->getFlashBag()->add('error' === $level ? 'danger' : $level, $message);
    }
}

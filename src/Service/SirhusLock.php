<?php

namespace App\Service;

use Predis\ClientInterface;
use Redis;
use RedisArray;
use RedisCluster;
use RedisException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio para gestionar bloqueos de recursos.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class SirhusLock
{
    private readonly ClientInterface|Redis|RedisArray|RedisCluster $redis;

    private bool $dirty = false;

    public function __construct(
        protected readonly RequestStack $stack,
        #[Autowire('%app.redis_url%')]
        private readonly ?string        $redisUrl,
    ) {
        $this->redis = $this->redisConnection();
    }

    /**
     * Crear un nuevo bloqueo sobre el recurso de la ruta actual con un periodo de validez (300 s. por defecto).
     * @return int[]|string[]|null
     */
    public function acquire(int $ttl = 300): ?array
    {
        $key = $this->getRouteWithParams();
        $lock = [
            'session' => $this->stack->getSession()->getId(),
            'timestamp' => time(),
            'ttl' => $ttl,
        ];
        try {
            if (!$this->isAcquired($key) || $this->isExpired($key)) {
                // Crear nueva clave con datos del bloqueo, si está libre o caducado
                $this->redis->set($key, serialize($lock));
                $this->dirty = true;

                return $lock;
            } else {
                $readLock = $this->getLockValue();
                if ($lock['session'] === ($readLock['session'] ?? '')) {
                    // Bloqueo propio
                    return $readLock;
                } else {
                    // Bloqueado por otra sesión
                    return null;
                }
            }
        } catch (RedisException) {
            return null;
        }
    }

    /**
     * Obtiene los datos del bloqueo si es de la sesión actual (sin datos si está libre o nulo si bloqueado por otra
     * sesión o error).
     * @return int[]|string[]|null
     */
    public function getLockValue(): ?array
    {
        $key = $this->getRouteWithParams();
        try {
            if (!$this->isAcquired($key)) {
                return [];
            }

            /** @var string $readValue */
            $readValue = $this->redis->get($key);
            /** @var array<array-key, string|int> $value */
            $value = unserialize($readValue);

            return $value['session'] === $this->stack->getSession()->getId() ? $value : null;
        } catch (RedisException) {
            return null;
        }
    }

    /** Devuelve el tiempo restante que queda para considerar el recurso como liberado. */
    public function getRemainingLifetime(): int
    {
        $value = $this->getLockValue() ?? [];

        return isset($value['timestamp']) && isset($value['ttl']) ?
            max((int) $value['timestamp'] + (int) $value['ttl'] - time(), 0) : 0;
    }

    /**
     * Devuelve si el recurso está bloqueado (por defecto, el recurso basado en la ruta actual).
     * @throws RedisException
     */
    public function isAcquired(?string $key = null): bool
    {
        $key ??= $this->getRouteWithParams();
        $this->dirty = 1 === $this->redis->exists($key);

        return $this->dirty;
    }

    /**
     * Indica si el bloqueo del recurso ha caducado o no.
     * @throws RedisException
     */
    public function isExpired(?string $key = null): bool
    {
        $key ??= $this->getRouteWithParams();
        if ($this->isAcquired()) {
            /** @var string $readValue */
            $readValue = $this->redis->get($key);
            /** @var array<array-key, string|int> $value */
            $value = unserialize($readValue);
            $this->dirty = time() > ((int) $value['timestamp'] + (int) $value['ttl']);
        }

        return $this->dirty;
    }

    /** Libera un recurso bloqueado. */
    public function release(?string $key = null): void
    {
        $key ??= $this->getRouteWithParams();
        try {
            $this->redis->del($key);
        } catch (RedisException) {
        }

        $this->dirty = false;
    }

    /** Conexión al servidor Redis. */
    private function redisConnection(): ClientInterface|Redis|RedisArray|RedisCluster
    {
        return RedisAdapter::createConnection((string) $this->redisUrl);
    }

    /** Compone una cadena con la ruta y sus parámetros. */
    private function getRouteWithParams(): string
    {
        /** @var string $value */
        $value = $this->stack->getCurrentRequest()?->attributes->get('_route', '');
        /** @var string[] $routeParams */
        $routeParams = $this->stack->getCurrentRequest()?->attributes->get('_route_params', []);
        $params = implode(':', array_filter($routeParams, static fn($k) => $k !== 'titulo', ARRAY_FILTER_USE_KEY));
        if ('' !== $params) {
            $value .= ':' . $params;
        }

        return $value;
    }
}

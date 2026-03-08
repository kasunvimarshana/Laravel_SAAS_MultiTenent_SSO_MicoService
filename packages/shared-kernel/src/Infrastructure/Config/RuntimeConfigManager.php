<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Config;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Psr\Log\LoggerInterface;
use Saas\SharedKernel\Infrastructure\Tenant\TenantContext;

/**
 * Runtime configuration manager.
 *
 * Allows changing Laravel config values at runtime WITHOUT application restart.
 * Per-tenant configurations override global defaults.
 *
 * Supported dynamic configs:
 *  - database connections
 *  - cache drivers
 *  - mail settings
 *  - queue connections
 *  - message broker settings
 *
 * Changes are cached in Redis and applied on each request via middleware.
 */
final class RuntimeConfigManager
{
    private const CACHE_TTL_DEFAULT = 300; // 5 minutes
    private const CACHE_PREFIX = 'runtime_config';

    public function __construct(
        private readonly Cache          $cache,
        private readonly Config         $config,
        private readonly TenantContext  $tenantContext,
        private readonly LoggerInterface $logger,
        private readonly int            $cacheTtl = self::CACHE_TTL_DEFAULT
    ) {}

    /**
     * Apply all runtime configurations for the current tenant.
     * Called by middleware on every request.
     */
    public function applyTenantConfig(): void
    {
        $tenantId = $this->tenantContext->getTenantId();

        if (!$tenantId) {
            return;
        }

        $configs = $this->getTenantConfigs($tenantId);

        foreach ($configs as $key => $value) {
            $this->config->set($key, $value);
            $this->applyToRuntime($key, $value);
        }

        if (!empty($configs)) {
            $this->logger->debug('[RuntimeConfig] Applied tenant configs', [
                'tenant_id' => $tenantId,
                'keys'      => array_keys($configs),
            ]);
        }
    }

    /**
     * Set a runtime config value for a tenant (persisted to cache).
     *
     * @param  mixed $value
     */
    public function setForTenant(string $tenantId, string $key, mixed $value): void
    {
        $configs       = $this->getTenantConfigs($tenantId);
        $configs[$key] = $value;

        $this->cache->put(
            $this->cacheKey($tenantId),
            $configs,
            $this->cacheTtl
        );

        $this->logger->info('[RuntimeConfig] Config updated for tenant', [
            'tenant_id' => $tenantId,
            'key'       => $key,
        ]);
    }

    /**
     * Get all runtime configs for a tenant.
     *
     * @return array<string, mixed>
     */
    public function getTenantConfigs(string $tenantId): array
    {
        return $this->cache->get($this->cacheKey($tenantId), []);
    }

    /**
     * Clear all cached configs for a tenant (e.g. after config update).
     */
    public function clearForTenant(string $tenantId): void
    {
        $this->cache->forget($this->cacheKey($tenantId));
    }

    /**
     * Apply config to underlying Laravel framework components.
     *
     * @param  mixed $value
     */
    private function applyToRuntime(string $key, mixed $value): void
    {
        // Apply database connection overrides
        if (str_starts_with($key, 'database.connections.')) {
            // Laravel resolves DB connections lazily, config update is sufficient
            return;
        }

        // Apply cache driver override
        if ($key === 'cache.default') {
            // Cache manager will pick up the new driver on next resolution
            return;
        }

        // Apply mail settings
        if (str_starts_with($key, 'mail.')) {
            // Mail is re-instantiated per request so config update is sufficient
            return;
        }
    }

    private function cacheKey(string $tenantId): string
    {
        return self::CACHE_PREFIX . ':' . $tenantId;
    }
}

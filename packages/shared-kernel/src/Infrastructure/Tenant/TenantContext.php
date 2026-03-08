<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Tenant;

/**
 * Holds the currently resolved tenant for the duration of the HTTP request
 * or queue job. Acts as a lightweight request-scoped service.
 */
final class TenantContext
{
    private ?string $tenantId    = null;
    private ?string $tenantSlug  = null;
    private array   $config      = [];

    // ──────────── Tenant Resolution ────────────

    public function setTenant(string $tenantId, string $tenantSlug = '', array $config = []): void
    {
        $this->tenantId   = $tenantId;
        $this->tenantSlug = $tenantSlug;
        $this->config     = $config;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function getTenantSlug(): ?string
    {
        return $this->tenantSlug;
    }

    public function isResolved(): bool
    {
        return $this->tenantId !== null;
    }

    public function clear(): void
    {
        $this->tenantId   = null;
        $this->tenantSlug = null;
        $this->config     = [];
    }

    // ──────────── Runtime Config ────────────

    /**
     * Retrieve a tenant-specific runtime config value.
     *
     * @param  mixed $default
     * @return mixed
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Set a runtime config value (e.g. database, cache, mail driver).
     */
    public function setConfig(string $key, mixed $value): void
    {
        data_set($this->config, $key, $value);
    }

    /** @return array<string, mixed> */
    public function getAllConfig(): array
    {
        return $this->config;
    }
}

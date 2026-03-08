<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Saas\SharedKernel\Application\DTOs\PaginationDto;
use Saas\SharedKernel\Domain\Exceptions\ValidationException;
use Saas\SharedKernel\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;

/**
 * Tenant application service.
 */
final class TenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly MessageBrokerInterface    $messageBroker
    ) {}

    public function list(PaginationDto $pagination, array $filters = []): Collection|LengthAwarePaginator
    {
        return $this->tenantRepository->findAll($pagination->mergeToCriteria(['filters' => $filters]));
    }

    public function get(string $id): Tenant
    {
        return $this->tenantRepository->findOrFail($id);
    }

    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            if ($this->tenantRepository->findBySlug($data['slug'])) {
                throw new ValidationException(['slug' => ['This slug is already taken.']]);
            }

            $tenant = $this->tenantRepository->create([
                'name'    => $data['name'],
                'slug'    => $data['slug'],
                'plan'    => $data['plan']   ?? 'free',
                'status'  => $data['status'] ?? 'trial',
                'config'  => $data['config'] ?? [],
                'features'=> $data['features'] ?? [],
                'domain'  => $data['domain']   ?? null,
                'trial_ends_at' => now()->addDays(14),
            ]);

            $this->messageBroker->publish('tenant.created', [
                'tenant_id' => $tenant->id,
                'slug'      => $tenant->slug,
                'plan'      => $tenant->plan,
            ]);

            return $tenant;
        });
    }

    public function update(string $id, array $data): Tenant
    {
        return $this->tenantRepository->update($id, array_filter($data, fn($v) => $v !== null));
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->tenantRepository->delete($id);
            $this->messageBroker->publish('tenant.deleted', ['tenant_id' => $id]);
        });
    }

    /**
     * Update tenant runtime configuration without restarting the application.
     */
    public function updateConfig(string $id, array $config): Tenant
    {
        $tenant = $this->tenantRepository->findOrFail($id);
        $merged = array_merge($tenant->config ?? [], $config);
        return $this->tenantRepository->update($id, ['config' => $merged]);
    }
}

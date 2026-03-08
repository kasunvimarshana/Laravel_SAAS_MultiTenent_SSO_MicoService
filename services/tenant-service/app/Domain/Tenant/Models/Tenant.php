<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tenant aggregate root representing an organization/account.
 *
 * @property string      $id
 * @property string      $name
 * @property string      $slug       URL-safe identifier
 * @property string      $plan       free|starter|professional|enterprise
 * @property string      $status     active|suspended|trial|cancelled
 * @property array       $config     Runtime-configurable settings per tenant
 * @property array       $features   Feature flags
 * @property string|null $domain     Custom domain
 * @property \Carbon\Carbon|null $trial_ends_at
 */
class Tenant extends Model
{
    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'status',
        'config',
        'features',
        'domain',
        'trial_ends_at',
    ];

    protected $casts = [
        'config'       => 'array',
        'features'     => 'array',
        'trial_ends_at'=> 'datetime',
    ];

    // ──────────── Business logic ────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? [], true);
    }

    /**
     * Get a runtime config value (e.g. database, cache, broker settings).
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    public function members(): HasMany
    {
        return $this->hasMany(TenantMember::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(TenantWebhook::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Membership record linking a user to a tenant with a role.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $user_id
 * @property string $role       owner|admin|member|viewer
 * @property bool   $is_active
 */
class TenantMember extends Model
{
    use HasUuids;

    protected $table = 'tenant_members';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'role',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

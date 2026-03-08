<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-device authentication token for multi-device SSO support.
 *
 * @property string $id
 * @property string $user_id
 * @property string $tenant_id
 * @property string $device_fingerprint
 * @property string $device_name
 * @property string $platform
 * @property string $token_hash
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon|null $expires_at
 * @property bool   $is_revoked
 */
class DeviceToken extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'device_fingerprint',
        'device_name',
        'platform',
        'token_hash',
        'last_used_at',
        'expires_at',
        'is_revoked',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
        'is_revoked'   => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}

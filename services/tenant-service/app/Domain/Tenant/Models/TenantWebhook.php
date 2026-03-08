<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Webhook configuration per tenant.
 *
 * @property string   $id
 * @property string   $tenant_id
 * @property string   $url
 * @property string[] $events     Events to subscribe to
 * @property string   $secret     HMAC signing secret
 * @property bool     $is_active
 */
class TenantWebhook extends Model
{
    use HasUuids;

    protected $table = 'tenant_webhooks';

    protected $fillable = [
        'tenant_id',
        'url',
        'events',
        'secret',
        'is_active',
    ];

    protected $casts = [
        'events'    => 'array',
        'is_active' => 'boolean',
    ];

    /** @var string[] */
    protected $hidden = ['secret'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

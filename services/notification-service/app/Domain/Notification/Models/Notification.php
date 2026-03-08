<?php

declare(strict_types=1);

namespace App\Domain\Notification\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Persistent notification record.
 *
 * @property string      $id
 * @property string      $tenant_id
 * @property string|null $user_id
 * @property string      $channel     email|sms|push|webhook|in_app
 * @property string      $type        e.g. "order.created", "low_stock"
 * @property string      $status      pending|sent|failed|cancelled
 * @property array       $payload     Template variables
 * @property array       $recipient   To address/device token/etc.
 * @property string|null $error
 * @property \Carbon\Carbon|null $sent_at
 */
class Notification extends Model
{
    use HasUuids;

    protected $table = 'notifications';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'channel',
        'type',
        'status',
        'payload',
        'recipient',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'payload'   => 'array',
        'recipient' => 'array',
        'sent_at'   => 'datetime',
    ];

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }
}

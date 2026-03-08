<?php

declare(strict_types=1);

namespace App\Domain\Saga\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Persistent saga transaction record for audit and replay.
 *
 * @property string      $id
 * @property string      $tenant_id
 * @property string      $saga_type      e.g. "CreateOrder"
 * @property string      $status         running|completed|compensating|compensated|failed
 * @property array       $context        Shared saga context
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $compensated_at
 */
class SagaTransaction extends Model
{
    use HasUuids;

    protected $table = 'saga_transactions';

    protected $fillable = [
        'tenant_id',
        'saga_type',
        'status',
        'context',
        'error_message',
        'completed_at',
        'compensated_at',
    ];

    protected $casts = [
        'context'        => 'array',
        'completed_at'   => 'datetime',
        'compensated_at' => 'datetime',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(SagaStep::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}

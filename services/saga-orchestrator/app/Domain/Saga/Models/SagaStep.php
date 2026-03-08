<?php

declare(strict_types=1);

namespace App\Domain\Saga\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Individual step record within a saga transaction.
 *
 * @property string      $id
 * @property string      $saga_transaction_id
 * @property string      $name
 * @property string      $status   pending|executing|completed|compensating|compensated|failed
 * @property int         $order
 * @property array|null  $input
 * @property array|null  $output
 * @property string|null $error
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 */
class SagaStep extends Model
{
    use HasUuids;

    protected $table = 'saga_steps';

    protected $fillable = [
        'saga_transaction_id',
        'name',
        'status',
        'order',
        'input',
        'output',
        'error',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'input'        => 'array',
        'output'       => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SagaTransaction::class, 'saga_transaction_id');
    }
}

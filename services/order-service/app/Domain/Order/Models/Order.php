<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Order aggregate root.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $customer_id
 * @property string $order_number
 * @property string $status   pending|confirmed|processing|shipped|delivered|cancelled|refunded
 * @property int    $subtotal        cents
 * @property int    $tax             cents
 * @property int    $shipping        cents
 * @property int    $total           cents
 * @property string $currency
 * @property array  $shipping_address
 * @property string $saga_id         Tracks the distributed transaction
 * @property array  $metadata
 */
class Order extends Model
{
    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'order_number',
        'status',
        'subtotal',
        'tax',
        'shipping',
        'total',
        'currency',
        'shipping_address',
        'saga_id',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'subtotal'         => 'integer',
        'tax'              => 'integer',
        'shipping'         => 'integer',
        'total'            => 'integer',
        'shipping_address' => 'array',
        'metadata'         => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'processing'], true);
    }

    public function getTotalInFloat(): float
    {
        return $this->total / 100;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit record for every stock change (in/out/transfer/adjustment).
 *
 * @property string      $id
 * @property string      $tenant_id
 * @property string      $product_id
 * @property string      $warehouse_id
 * @property string      $type         in|out|transfer|adjustment|reservation|release
 * @property int         $quantity
 * @property int         $quantity_before
 * @property int         $quantity_after
 * @property string|null $reference_type  Order, PurchaseOrder, etc.
 * @property string|null $reference_id
 * @property string|null $notes
 * @property string      $performed_by  User UUID
 */
class StockMovement extends Model
{
    use HasUuids;
    use HasFactory;

    protected $table = 'stock_movements';

    // Stock movements are immutable – no updates allowed
    public $timestamps = true;
    protected const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'quantity'        => 'integer',
        'quantity_before' => 'integer',
        'quantity_after'  => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}

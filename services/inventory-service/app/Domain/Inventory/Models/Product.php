<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Saas\SharedKernel\Domain\AggregateRoot;

/**
 * Product aggregate root.
 *
 * @property string      $id
 * @property string      $tenant_id
 * @property string      $category_id
 * @property string      $sku
 * @property string      $name
 * @property string|null $description
 * @property int         $price       stored in cents
 * @property string      $currency
 * @property int         $stock_quantity
 * @property int         $reorder_level
 * @property string      $status      active|inactive|discontinued
 * @property array|null  $attributes  JSON meta
 * @property string|null $image_url
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Product extends Model
{
    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'sku',
        'name',
        'description',
        'price',
        'currency',
        'stock_quantity',
        'reorder_level',
        'status',
        'attributes',
        'image_url',
    ];

    protected $casts = [
        'price'          => 'integer',
        'stock_quantity' => 'integer',
        'reorder_level'  => 'integer',
        'attributes'     => 'array',
    ];

    // ──────────── Relationships ────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    // ──────────── Business Logic ────────────

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function isBelowReorderLevel(): bool
    {
        return $this->stock_quantity <= $this->reorder_level;
    }

    public function getPriceInFloat(): float
    {
        return $this->price / 100;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

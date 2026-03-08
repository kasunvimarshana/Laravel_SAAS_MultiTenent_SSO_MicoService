<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms Product model into a consistent API response shape.
 */
final class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'sku'            => $this->sku,
            'name'           => $this->name,
            'description'    => $this->description,
            'price'          => $this->getPriceInFloat(),
            'price_cents'    => $this->price,
            'currency'       => $this->currency,
            'stock_quantity' => $this->stock_quantity,
            'reorder_level'  => $this->reorder_level,
            'is_in_stock'    => $this->isInStock(),
            'is_low_stock'   => $this->isBelowReorderLevel(),
            'status'         => $this->status,
            'attributes'     => $this->attributes ?? [],
            'image_url'      => $this->image_url,
            'category'       => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            'tenant_id'      => $this->tenant_id,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}

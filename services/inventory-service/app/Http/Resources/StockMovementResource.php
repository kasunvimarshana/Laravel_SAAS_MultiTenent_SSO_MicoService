<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StockMovementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'product_id'     => $this->product_id,
            'warehouse_id'   => $this->warehouse_id,
            'type'           => $this->type,
            'quantity'       => $this->quantity,
            'quantity_before'=> $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'notes'          => $this->notes,
            'performed_by'   => $this->performed_by,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}

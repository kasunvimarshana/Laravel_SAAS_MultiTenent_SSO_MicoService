<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'order_number'     => $this->order_number,
            'customer_id'      => $this->customer_id,
            'status'           => $this->status,
            'subtotal'         => $this->subtotal / 100,
            'tax'              => $this->tax / 100,
            'shipping'         => $this->shipping / 100,
            'total'            => $this->getTotalInFloat(),
            'currency'         => $this->currency,
            'shipping_address' => $this->shipping_address,
            'items'            => OrderItemResource::collection($this->whenLoaded('items')),
            'saga_id'          => $this->saga_id,
            'tenant_id'        => $this->tenant_id,
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}

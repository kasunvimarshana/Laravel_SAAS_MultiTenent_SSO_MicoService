<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SagaTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'tenant_id'      => $this->tenant_id,
            'saga_type'      => $this->saga_type,
            'status'         => $this->status,
            'context'        => $this->context,
            'error_message'  => $this->error_message,
            'completed_at'   => $this->completed_at?->toIso8601String(),
            'compensated_at' => $this->compensated_at?->toIso8601String(),
            'steps'          => SagaStepResource::collection($this->whenLoaded('steps')),
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}

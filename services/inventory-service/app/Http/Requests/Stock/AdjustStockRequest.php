<?php

declare(strict_types=1);

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

final class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'delta'          => ['required', 'integer', 'not_in:0'],
            'type'           => ['required', 'string', 'in:in,out,transfer,adjustment'],
            'warehouse_id'   => ['required', 'uuid'],
            'reference_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'reference_id'   => ['sometimes', 'nullable', 'uuid'],
            'notes'          => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}

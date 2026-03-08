<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates incoming product creation payloads.
 * Controller stays thin – validation is entirely here.
 */
final class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware / policies
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'sku'           => ['required', 'string', 'max:100'],
            'name'          => ['required', 'string', 'max:255'],
            'category_id'   => ['required', 'uuid'],
            'warehouse_id'  => ['required', 'uuid'],
            'price'         => ['required', 'numeric', 'min:0'],
            'currency'      => ['sometimes', 'string', 'size:3'],
            'initial_stock' => ['sometimes', 'integer', 'min:0'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
            'description'   => ['sometimes', 'nullable', 'string', 'max:5000'],
            'attributes'    => ['sometimes', 'array'],
        ];
    }
}

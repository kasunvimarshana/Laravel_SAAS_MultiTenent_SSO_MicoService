<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'category_id'   => ['sometimes', 'uuid'],
            'price'         => ['sometimes', 'numeric', 'min:0'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
            'description'   => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status'        => ['sometimes', 'string', 'in:active,inactive,discontinued'],
            'attributes'    => ['sometimes', 'array'],
        ];
    }
}

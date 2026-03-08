<?php

declare(strict_types=1);

namespace App\Application\DTOs;

/**
 * Data Transfer Object for product updates (all fields optional).
 */
final readonly class UpdateProductDto
{
    public function __construct(
        public readonly ?string $categoryId    = null,
        public readonly ?string $name          = null,
        public readonly ?string $description   = null,
        public readonly ?int    $priceInCents  = null,
        public readonly ?int    $reorderLevel  = null,
        public readonly ?string $status        = null,
        public readonly ?array  $attributes    = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            categoryId:   $data['category_id']  ?? null,
            name:         $data['name']          ?? null,
            description:  $data['description']   ?? null,
            priceInCents: isset($data['price'])
                ? (int) round(((float) $data['price']) * 100)
                : null,
            reorderLevel: isset($data['reorder_level']) ? (int) $data['reorder_level'] : null,
            status:       $data['status']        ?? null,
            attributes:   $data['attributes']    ?? null,
        );
    }
}

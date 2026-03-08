<?php

declare(strict_types=1);

namespace App\Application\DTOs;

/**
 * Data Transfer Object for product creation.
 */
final readonly class CreateProductDto
{
    public function __construct(
        public readonly string  $sku,
        public readonly string  $name,
        public readonly string  $categoryId,
        public readonly string  $warehouseId,
        public readonly int     $priceInCents,
        public readonly string  $currency,
        public readonly int     $initialStock,
        public readonly int     $reorderLevel,
        public readonly ?string $description = null,
        public readonly array   $attributes  = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            sku:          $data['sku'],
            name:         $data['name'],
            categoryId:   $data['category_id'],
            warehouseId:  $data['warehouse_id'],
            priceInCents: (int) round(((float) $data['price']) * 100),
            currency:     strtoupper($data['currency'] ?? 'USD'),
            initialStock: (int) ($data['initial_stock'] ?? 0),
            reorderLevel: (int) ($data['reorder_level'] ?? 0),
            description:  $data['description'] ?? null,
            attributes:   $data['attributes']  ?? [],
        );
    }
}

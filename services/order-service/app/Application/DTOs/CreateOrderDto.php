<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class CreateOrderDto
{
    /**
     * @param array<int, array{product_id: string, product_name: string, product_sku: string, quantity: int, unit_price: int, warehouse_id?: string}> $items
     * @param array<string, mixed> $shippingAddress
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string  $customerId,
        public readonly array   $items,
        public readonly array   $shippingAddress,
        public readonly string  $currency     = 'USD',
        public readonly int     $shippingCost = 0,
        public readonly string  $warehouseId  = '',
        public readonly array   $metadata     = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            customerId:      $data['customer_id'],
            items:           $data['items'],
            shippingAddress: $data['shipping_address'] ?? [],
            currency:        strtoupper($data['currency']      ?? 'USD'),
            shippingCost:    (int) ($data['shipping_cost']     ?? 0),
            warehouseId:     $data['warehouse_id']             ?? '',
            metadata:        $data['metadata']                 ?? [],
        );
    }
}

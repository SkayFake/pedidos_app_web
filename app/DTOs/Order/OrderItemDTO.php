<?php

declare(strict_types=1);

namespace App\DTOs\Order;

readonly class OrderItemDTO
{
    /**
     * @param array<int, OrderItemExtraDTO> $extras
     */
    public function __construct(
        public int $productId,
        public ?int $variantId,
        public int $quantity,
        public array $extras,
    ) {}

    public static function fromArray(array $data): self
    {
        $extras = array_map(
            fn(array $extra) => OrderItemExtraDTO::fromArray($extra),
            $data['extras'] ?? []
        );

        return new self(
            productId: (int) $data['product_id'],
            variantId: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            quantity: (int) $data['quantity'],
            extras: $extras,
        );
    }
}

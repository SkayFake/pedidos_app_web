<?php

declare(strict_types=1);

namespace App\DTOs\Order;

readonly class OrderItemExtraDTO
{
    public function __construct(
        public int $extraId,
        public int $quantity,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            extraId: (int) $data['extra_id'],
            quantity: (int) $data['quantity'],
        );
    }
}

<?php

declare(strict_types=1);

namespace App\DTOs\Order;

readonly class CreateOrderDTO
{
    /**
     * @param array<int, OrderItemDTO> $items
     */
    public function __construct(
        public int $branchId,
        public int $addressId,
        public ?float $lat,
        public ?float $lng,
        public ?string $couponCode,
        public bool $useLoyaltyPoints,
        public ?string $notes,
        public array $items,
    ) {}

    public static function fromArray(array $data): self
    {
        $items = array_map(
            fn(array $item) => OrderItemDTO::fromArray($item),
            $data['items'] ?? []
        );

        return new self(
            branchId: (int) $data['branch_id'],
            addressId: (int) $data['address_id'],
            lat: isset($data['lat']) ? (float) $data['lat'] : null,
            lng: isset($data['lng']) ? (float) $data['lng'] : null,
            couponCode: $data['coupon_code'] ?? null,
            useLoyaltyPoints: (bool) ($data['use_loyalty_points'] ?? false),
            notes: $data['notes'] ?? null,
            items: $items,
        );
    }
}

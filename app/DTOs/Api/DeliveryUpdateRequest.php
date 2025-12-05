<?php

namespace App\DTOs\Api;

use Carbon\Carbon;
use DateTimeInterface;

readonly class DeliveryUpdateRequest
{
    public function __construct(
        public DateTimeInterface $deliveredAt,
        public ?string $carrierCode = null,
        public ?string $signature = null,
        public ?string $deliveryNotes = null,
        public ?string $photoUrl = null,
    ) {}

    /**
     * Create from validated request data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            deliveredAt: Carbon::parse($data['delivered_at']),
            carrierCode: $data['carrier_code'] ?? null,
            signature: $data['signature'] ?? null,
            deliveryNotes: $data['delivery_notes'] ?? null,
            photoUrl: $data['photo_url'] ?? null,
        );
    }
}

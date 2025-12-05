<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $carrierCodes = ['ups', 'fedex', 'dhl', 'usps'];
        $carrierCode = fake()->randomElement($carrierCodes);

        return [
            'tenant_id' => Tenant::factory(),
            'order_id' => Order::factory(),
            'magento_shipment_id' => fake()->unique()->numberBetween(1000, 999999),
            'tracking_number' => $this->generateTrackingNumber($carrierCode),
            'carrier_code' => $carrierCode,
            'carrier_title' => $this->getCarrierTitle($carrierCode),
            'status' => 'pending',
            'shipped_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'estimated_delivery_at' => fake()->dateTimeBetween('now', '+7 days'),
            'actual_delivery_at' => null,
            'last_tracking_update_at' => null,
            'delivery_signature' => null,
            'delivery_notes' => null,
            'delivery_photo_url' => null,
        ];
    }

    /**
     * Indicate that the shipment is in transit.
     */
    public function inTransit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_transit',
            'shipped_at' => fake()->dateTimeBetween('-3 days', '-1 day'),
            'last_tracking_update_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Indicate that the shipment is out for delivery.
     */
    public function outForDelivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'out_for_delivery',
            'shipped_at' => fake()->dateTimeBetween('-5 days', '-2 days'),
            'estimated_delivery_at' => now(),
            'last_tracking_update_at' => now()->subHours(fake()->numberBetween(1, 4)),
        ]);
    }

    /**
     * Indicate that the shipment is delivered.
     */
    public function delivered(): static
    {
        $deliveredAt = fake()->dateTimeBetween('-2 days', 'now');

        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'shipped_at' => fake()->dateTimeBetween('-7 days', $deliveredAt),
            'actual_delivery_at' => $deliveredAt,
            'last_tracking_update_at' => $deliveredAt,
            'delivery_signature' => fake()->name(),
            'delivery_notes' => fake()->optional(0.5)->sentence(),
        ]);
    }

    /**
     * Set a specific carrier.
     */
    public function forCarrier(string $carrierCode): static
    {
        return $this->state(fn (array $attributes) => [
            'carrier_code' => $carrierCode,
            'carrier_title' => $this->getCarrierTitle($carrierCode),
            'tracking_number' => $this->generateTrackingNumber($carrierCode),
        ]);
    }

    /**
     * Set a specific tracking number.
     */
    public function withTrackingNumber(string $trackingNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'tracking_number' => $trackingNumber,
        ]);
    }

    /**
     * Generate a realistic tracking number for a carrier.
     */
    protected function generateTrackingNumber(string $carrierCode): string
    {
        return match ($carrierCode) {
            'ups' => '1Z'.strtoupper(fake()->bothify('???###???###????')),
            'fedex' => fake()->numerify('################'),
            'dhl' => fake()->numerify('##########'),
            'usps' => '94'.fake()->numerify('####################'),
            default => strtoupper(fake()->bothify('??##########??')),
        };
    }

    /**
     * Get carrier title from code.
     */
    protected function getCarrierTitle(string $carrierCode): string
    {
        return match ($carrierCode) {
            'ups' => 'UPS',
            'fedex' => 'FedEx',
            'dhl' => 'DHL Express',
            'usps' => 'USPS',
            default => strtoupper($carrierCode),
        };
    }
}

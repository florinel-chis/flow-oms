<?php

namespace Database\Factories;

use App\Models\MagentoStore;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grandTotal = fake()->randomFloat(2, 50, 500);
        $taxAmount = $grandTotal * 0.1;
        $shippingAmount = fake()->randomFloat(2, 5, 25);
        $discountAmount = fake()->optional(0.3)->randomFloat(2, 5, 50) ?? 0;

        return [
            'tenant_id' => Tenant::factory(),
            'magento_store_id' => MagentoStore::factory(),
            'magento_order_id' => fake()->unique()->numberBetween(1000, 999999),
            'increment_id' => fake()->unique()->numerify('10000####'),
            'status' => fake()->randomElement(['pending', 'processing', 'complete', 'canceled']),
            'payment_status' => fake()->randomElement(['pending', 'paid', 'failed']),
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'source' => fake()->randomElement(['web', 'mobile', 'api']),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'grand_total' => $grandTotal,
            'subtotal' => $grandTotal - $taxAmount - $shippingAmount + $discountAmount,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'currency_code' => 'USD',
            'payment_method' => fake()->randomElement(['checkmo', 'cashondelivery', 'paypal', 'stripe']),
            'shipping_method' => fake()->randomElement(['flatrate_flatrate', 'freeshipping_freeshipping', 'ups_ground']),
            'ordered_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'synced_at' => now(),
        ];
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Indicate that the order is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Indicate that the order is complete.
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'complete',
            'payment_status' => 'paid',
        ]);
    }
}

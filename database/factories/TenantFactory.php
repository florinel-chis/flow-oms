<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'subscription_tier' => fake()->randomElement(['basic', 'pro', 'enterprise']),
            'settings' => [],
        ];
    }

    /**
     * Indicate that the tenant has enterprise tier.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'enterprise',
        ]);
    }
}

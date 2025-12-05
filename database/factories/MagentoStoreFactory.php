<?php

namespace Database\Factories;

use App\Models\MagentoStore;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MagentoStore>
 */
class MagentoStoreFactory extends Factory
{
    protected $model = MagentoStore::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $storeName = fake()->company().' Store';

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $storeName,
            'base_url' => 'https://'.fake()->domainName(),
            'access_token' => fake()->sha256(),
            'api_version' => 'V1',
            'sync_enabled' => true,
            'is_active' => true,
            'last_sync_at' => null,
            'settings' => [],
        ];
    }

    /**
     * Indicate that the store is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the store has been synced recently.
     */
    public function recentlySynced(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_sync_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }
}

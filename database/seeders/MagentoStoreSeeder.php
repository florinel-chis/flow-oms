<?php

namespace Database\Seeders;

use App\Models\MagentoStore;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class MagentoStoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::first();

        if (! $tenant) {
            $this->command->error('No tenant found. Run TenantSeeder first.');

            return;
        }

        $store = MagentoStore::create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Store',
            'base_url' => 'https://demo-store.com',
            'access_token' => 'test_token_12345',
            'api_version' => 'V1',
            'sync_enabled' => true,
            'is_active' => true,
            'last_sync_at' => now()->subHours(2),
            'settings' => [
                'sync_interval' => 15,
                'auto_sync' => true,
            ],
        ]);

        $this->command->info("Created Magento store: {$store->name} (ID: {$store->id})");
    }
}

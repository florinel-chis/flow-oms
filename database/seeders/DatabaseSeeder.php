<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            MagentoStoreSeeder::class,
            SettingSeeder::class,
        ]);

        // Orders and invoices are now synced from real Magento data
        // Run: php artisan magento:sync-orders --backfill --sync
        // This will pull the last 30 days of orders and transform them
        // into the normalized Orders, OrderItems, Invoices, and Shipments tables
    }
}

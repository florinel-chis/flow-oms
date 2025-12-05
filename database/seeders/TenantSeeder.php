<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a demo tenant
        $tenant = Tenant::create([
            'name' => 'Demo Company',
            'slug' => 'demo',
            'subscription_tier' => 'professional',
            'settings' => [
                'timezone' => 'America/New_York',
                'currency' => 'USD',
            ],
        ]);

        // Create or find the test user and attach to tenant
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        $tenant->users()->attach($user->id, ['role' => 'admin']);
        $user->update(['latest_tenant_id' => $tenant->id]);

        $this->command->info("Created tenant: {$tenant->name} (ID: {$tenant->id})");
    }
}

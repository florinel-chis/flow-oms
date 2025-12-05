<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FixReadyToShipSettingsSeeder extends Seeder
{
    /**
     * Fix Ready to Ship widget settings to match actual database statuses.
     */
    public function run(): void
    {
        $this->command->info('🔍 Analyzing order statuses in database...');

        // Get all unique order statuses
        $statusDistribution = DB::table('orders')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->command->newLine();
        $this->command->info('Current order status distribution:');
        foreach ($statusDistribution as $item) {
            $this->command->line("  {$item->status}: {$item->count} orders");
        }

        // Detect potentially ready-to-ship statuses
        $excludeStatuses = ['canceled', 'complete', 'closed', 'holded', 'returned',
                           'delivered', 'shipped', 'pending_cancellation'];

        $readyStatuses = $statusDistribution
            ->pluck('status')
            ->reject(fn($status) => in_array($status, $excludeStatuses))
            ->values()
            ->toArray();

        $this->command->newLine();
        $this->command->info('Detected "ready to ship" statuses (excluding obviously completed/cancelled):');
        foreach ($readyStatuses as $status) {
            $count = $statusDistribution->firstWhere('status', $status)->count;
            $hasShipments = DB::table('orders')
                ->where('status', $status)
                ->whereExists(function($q) {
                    $q->select(DB::raw(1))
                      ->from('shipments')
                      ->whereColumn('shipments.order_id', 'orders.id');
                })
                ->count();

            $this->command->line("  ✓ {$status}: {$count} orders ({$hasShipments} already have shipments)");
        }

        // Get tenant
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->command->error('No tenant found. Please run TenantSeeder first.');
            return;
        }

        $this->command->newLine();
        $this->command->warn('This will update the Ready to Ship settings for tenant: ' . $tenant->name);

        if (!$this->command->confirm('Do you want to continue?', true)) {
            $this->command->info('Update cancelled.');
            return;
        }

        // Get current settings
        $currentSettings = DB::table('settings')
            ->where('tenant_id', $tenant->id)
            ->where('group', 'ready_to_ship')
            ->get()
            ->keyBy('key');

        $this->command->newLine();
        $this->command->info('Current settings:');
        foreach ($currentSettings as $key => $setting) {
            $value = json_decode($setting->value, true);
            $display = is_array($value) ? json_encode($value) : $value;
            $this->command->line("  {$key}: {$display}");
        }

        // Update settings
        $this->command->newLine();
        $this->command->info('Updating settings...');

        Setting::set(
            'ready_to_ship',
            'order_statuses',
            $readyStatuses,
            'json',
            'Order statuses that indicate ready to ship (auto-detected)',
            $tenant
        );

        Setting::set(
            'ready_to_ship',
            'payment_statuses',
            ['paid'],
            'json',
            'Payment statuses required for ready to ship',
            $tenant
        );

        Setting::set(
            'ready_to_ship',
            'check_shipments',
            true,
            'boolean',
            'Only include orders without shipments',
            $tenant
        );

        // Clear cache
        Cache::forget("settings:{$tenant->id}:ready_to_ship:order_statuses");
        Cache::forget("settings:{$tenant->id}:ready_to_ship:payment_statuses");
        Cache::forget("settings:{$tenant->id}:ready_to_ship:check_shipments");

        $this->command->newLine();
        $this->command->info('✅ Settings updated successfully!');

        // Show new configuration
        $this->command->newLine();
        $this->command->info('New configuration:');
        $this->command->line('  order_statuses: ' . json_encode($readyStatuses));
        $this->command->line('  payment_statuses: ["paid"]');
        $this->command->line('  check_shipments: true');

        // Show matching orders count
        $matchingOrders = DB::table('orders')
            ->whereIn('status', $readyStatuses)
            ->where('payment_status', 'paid')
            ->whereNotExists(function($q) {
                $q->select(DB::raw(1))
                  ->from('shipments')
                  ->whereColumn('shipments.order_id', 'orders.id');
            })
            ->count();

        $this->command->newLine();
        $this->command->info("📊 Orders now matching 'Ready to Ship' criteria: {$matchingOrders}");

        if ($matchingOrders > 0) {
            $this->command->newLine();
            $this->command->info('💡 Refresh your dashboard to see the updated widget!');
        } else {
            $this->command->newLine();
            $this->command->warn('⚠️  No orders match the criteria. This could mean:');
            $this->command->line('   - All paid orders already have shipments created');
            $this->command->line('   - No orders with detected statuses are marked as paid');
        }
    }
}

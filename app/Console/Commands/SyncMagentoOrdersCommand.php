<?php

namespace App\Console\Commands;

use App\Jobs\SyncMagentoOrdersJob;
use App\Models\MagentoStore;
use Illuminate\Console\Command;

class SyncMagentoOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'magento:sync-orders
                            {--tenant= : REQUIRED: Tenant ID to sync for (security: prevents cross-tenant data mixing)}
                            {--store= : Magento store ID to sync (default: all active stores for tenant)}
                            {--days=1 : Number of days to look back (default: 1)}
                            {--page-size=10 : Orders per page (default: 10)}
                            {--sync : Run sync synchronously instead of queueing}
                            {--truncate : DELETE all orders for this tenant first (DESTRUCTIVE!)}
                            {--backfill : Sync last 30 days (sets --days=30)}
                            {--no-transform : Raw data only, skip transformation to Order tables}';

    /**
     * The console command description.
     */
    protected $description = 'Sync orders from Magento stores';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Require tenant parameter for security
        $tenantId = $this->option('tenant');
        if (! $tenantId) {
            $this->error('ERROR: --tenant parameter is required for security isolation.');
            $this->line('Example: php artisan magento:sync-orders --tenant=1');
            $this->newLine();
            $this->warn('This prevents accidentally syncing data across multiple tenants.');

            return self::FAILURE;
        }

        // Validate tenant exists
        if (! \App\Models\Tenant::find($tenantId)) {
            $this->error("Tenant ID {$tenantId} not found.");

            return self::FAILURE;
        }

        $storeId = $this->option('store');
        $days = $this->option('backfill') ? 30 : (int) $this->option('days');
        $pageSize = (int) $this->option('page-size');
        $sync = $this->option('sync');
        $truncate = $this->option('truncate');
        $noTransform = $this->option('no-transform');
        $transformToOrders = ! $noTransform;

        // Validate inputs
        if ($days < 1 || $days > 365) {
            $this->error('Days must be between 1 and 365');

            return self::FAILURE;
        }

        if ($pageSize < 1 || $pageSize > 100) {
            $this->error('Page size must be between 1 and 100');

            return self::FAILURE;
        }

        // Handle truncate option
        if ($truncate) {
            if (! $this->confirmTruncate($tenantId)) {
                $this->warn('Sync cancelled.');

                return self::FAILURE;
            }

            $this->truncateOrders($tenantId);
        }

        // Get stores to sync
        $stores = $this->getStoresToSync($tenantId, $storeId);

        if ($stores->isEmpty()) {
            $this->error('No active Magento stores found with sync enabled');

            return self::FAILURE;
        }

        // Show configuration summary
        $this->info("Syncing orders from {$stores->count()} store(s)");
        $this->info('Configuration:');
        $this->line("  - Last {$days} days");
        $this->line("  - {$pageSize} orders per page");
        $this->line('  - Transform to Orders: '.($transformToOrders ? 'Yes' : 'No'));
        if ($this->option('backfill')) {
            $this->line('  - Backfill mode: Enabled');
        }
        $this->newLine();

        // Dispatch jobs
        foreach ($stores as $store) {
            $this->info("Processing: {$store->name} (ID: {$store->id})");

            try {
                if ($sync) {
                    // Run synchronously
                    $this->line('  Running synchronously...');
                    $job = new SyncMagentoOrdersJob($store, $days, $pageSize, null, null, $transformToOrders);
                    $job->handle();
                    $this->info('  ✓ Sync completed');
                } else {
                    // Queue the job
                    SyncMagentoOrdersJob::dispatch($store, $days, $pageSize, null, null, $transformToOrders);
                    $this->info('  ✓ Job queued');
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
            }

            $this->newLine();
        }

        if (! $sync) {
            $this->info('All jobs queued. Check queue worker output for progress.');
            $this->line('Monitor logs: tail -f storage/logs/laravel.log');
        }

        return self::SUCCESS;
    }

    /**
     * Get stores to sync based on options.
     *
     * @param  int  $tenantId  Tenant ID for security isolation
     * @param  string|null  $storeId  Optional specific store ID
     */
    private function getStoresToSync(int $tenantId, ?string $storeId)
    {
        $query = MagentoStore::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('sync_enabled', true);

        if ($storeId) {
            $query->where('id', $storeId);
        }

        return $query->get();
    }

    /**
     * Confirm truncation of orders
     *
     * @param  int  $tenantId  Tenant ID to show in warning
     */
    private function confirmTruncate(int $tenantId): bool
    {
        $this->warn("⚠️  WARNING: This will DELETE ALL ORDERS FOR TENANT {$tenantId}!");
        $this->line('This includes:');
        $this->line('  - Invoice items');
        $this->line('  - Invoices');
        $this->line('  - Shipments');
        $this->line('  - Order items');
        $this->line('  - Orders');
        $this->newLine();
        $this->line("Scope: ONLY tenant_id = {$tenantId}");
        $this->newLine();

        return $this->confirm('Are you absolutely sure you want to proceed?', false);
    }

    /**
     * Delete all order-related records for a specific tenant
     *
     * @param  int  $tenantId  Tenant ID for security isolation
     */
    private function truncateOrders(int $tenantId): void
    {
        $this->info("Deleting order data for tenant {$tenantId}...");

        // Delete in reverse order of foreign key dependencies
        // Using DELETE with WHERE instead of TRUNCATE for tenant isolation

        $deletedInvoiceItems = \DB::table('invoice_items')
            ->whereIn('invoice_id', function ($query) use ($tenantId) {
                $query->select('id')
                    ->from('invoices')
                    ->where('tenant_id', $tenantId);
            })
            ->delete();
        $this->line("  ✓ Invoice items deleted: {$deletedInvoiceItems}");

        $deletedInvoices = \DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->delete();
        $this->line("  ✓ Invoices deleted: {$deletedInvoices}");

        $deletedShipments = \DB::table('shipments')
            ->where('tenant_id', $tenantId)
            ->delete();
        $this->line("  ✓ Shipments deleted: {$deletedShipments}");

        $deletedOrderItems = \DB::table('order_items')
            ->where('tenant_id', $tenantId)
            ->delete();
        $this->line("  ✓ Order items deleted: {$deletedOrderItems}");

        $deletedOrders = \DB::table('orders')
            ->where('tenant_id', $tenantId)
            ->delete();
        $this->line("  ✓ Orders deleted: {$deletedOrders}");

        $this->newLine();
        $this->info("All order data for tenant {$tenantId} has been deleted.");
        $this->newLine();
    }
}

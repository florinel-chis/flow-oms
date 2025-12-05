<?php

namespace App\Console\Commands;

use App\Models\MagentoOrderSync;
use App\Services\Magento\OrderSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Transform all existing MagentoOrderSync records into Orders, Invoices, and Shipments
 */
class TransformSyncedOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magento:transform-synced-orders
                            {--tenant= : REQUIRED: Tenant ID (security: prevents cross-tenant data mixing)}
                            {--batch-size=100 : Number of orders to process per batch}
                            {--store= : Only transform orders for specific Magento store ID}
                            {--force : Re-transform already transformed orders}
                            {--dry-run : Show what would be transformed without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transform all synced orders from magento_order_syncs to orders/invoices/shipments tables';

    /**
     * Execute the console command.
     */
    public function handle(OrderSyncService $orderSyncService): int
    {
        // Require tenant parameter for security
        $tenantId = $this->option('tenant');
        if (! $tenantId) {
            $this->error('ERROR: --tenant parameter is required for security isolation.');
            $this->line('Example: php artisan magento:transform-synced-orders --tenant=1');
            $this->newLine();
            $this->warn('This prevents accidentally transforming orders across multiple tenants.');

            return self::FAILURE;
        }

        // Validate tenant exists
        if (! \App\Models\Tenant::find($tenantId)) {
            $this->error("Tenant ID {$tenantId} not found.");

            return self::FAILURE;
        }

        $this->info('🔄 Starting bulk order transformation...');
        $this->newLine();

        // Build query with tenant filter
        $query = MagentoOrderSync::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->with(['magentoStore', 'tenant']);

        // Apply store filter if specified
        if ($storeId = $this->option('store')) {
            $query->where('magento_store_id', $storeId);
        }

        // Only process unprocessed orders unless --force
        if (! $this->option('force')) {
            // Get IDs that already have been transformed FOR THIS TENANT
            $transformedMagentoIds = DB::table('orders')
                ->where('tenant_id', $tenantId)
                ->pluck('magento_order_id')
                ->toArray();

            $query->whereNotIn('entity_id', $transformedMagentoIds);
        }

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->warn('⚠️  No orders to transform.');
            $this->info('💡 Tip: Use --force to re-transform existing orders');

            return self::SUCCESS;
        }

        $this->info("📊 Found {$totalCount} order(s) to transform");

        // Dry run check
        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();

            // Show sample of orders that would be transformed
            $sample = $query->limit(10)->get(['increment_id', 'entity_id', 'order_status', 'has_invoice', 'has_shipment', 'synced_at']);

            $this->table(
                ['Increment ID', 'Entity ID', 'Status', 'Invoice', 'Shipment', 'Synced At'],
                $sample->map(fn ($sync) => [
                    $sync->increment_id,
                    $sync->entity_id,
                    $sync->order_status,
                    $sync->has_invoice ? '✅' : '❌',
                    $sync->has_shipment ? '✅' : '❌',
                    $sync->synced_at?->format('Y-m-d H:i:s'),
                ])->toArray()
            );

            if ($totalCount > 10) {
                $this->info('... and '.($totalCount - 10).' more order(s)');
            }

            return self::SUCCESS;
        }

        // Confirm before proceeding
        if (! $this->confirm("⚠️  This will transform {$totalCount} order(s). Continue?", true)) {
            $this->warn('❌ Transformation cancelled.');

            return self::SUCCESS;
        }

        // Process in batches
        $batchSize = (int) $this->option('batch-size');
        $processed = 0;
        $succeeded = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $progressBar->setMessage('Starting...');
        $progressBar->start();

        $query->chunk($batchSize, function ($syncRecords) use ($orderSyncService, &$processed, &$succeeded, &$failed, $progressBar) {
            foreach ($syncRecords as $syncRecord) {
                try {
                    $order = $orderSyncService->syncOrder($syncRecord);

                    $succeeded++;
                    $progressBar->setMessage("✅ {$order->increment_id}");
                } catch (\Exception $e) {
                    $failed++;
                    $progressBar->setMessage("❌ {$syncRecord->increment_id}: {$e->getMessage()}");

                    // Log error
                    $this->error("Failed to transform order {$syncRecord->increment_id}: ".$e->getMessage());
                }

                $processed++;
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('📈 Transformation Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Orders Processed', $processed],
                ['✅ Successfully Transformed', $succeeded],
                ['❌ Failed', $failed],
                ['Success Rate', $succeeded > 0 ? round(($succeeded / $processed) * 100, 2).'%' : '0%'],
            ]
        );

        // Check what was created
        $this->newLine();
        $this->info('📊 Database Status:');

        $ordersCount = DB::table('orders')->count();
        $itemsCount = DB::table('order_items')->count();
        $invoicesCount = DB::table('invoices')->count();
        $shipmentsCount = DB::table('shipments')->count();

        $this->table(
            ['Table', 'Records'],
            [
                ['Orders', $ordersCount],
                ['Order Items', $itemsCount],
                ['Invoices', $invoicesCount],
                ['Invoice Items', DB::table('invoice_items')->count()],
                ['Shipments', $shipmentsCount],
            ]
        );

        if ($failed > 0) {
            $this->newLine();
            $this->warn("⚠️  {$failed} order(s) failed to transform. Check logs for details.");

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✅ Bulk transformation completed successfully!');

        return self::SUCCESS;
    }
}

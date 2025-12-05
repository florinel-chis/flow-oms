<?php

namespace App\Console\Commands;

use App\Models\MagentoStore;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Magento\MagentoApiClient;
use App\Services\Magento\Parsers\ShipmentParser;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMagentoShipmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'magento:sync-shipments
                            {--store= : Magento store ID to sync (default: all active stores)}
                            {--days=1 : Number of days to look back (default: 1)}
                            {--page-size=100 : Shipments per page (default: 100)}';

    /**
     * The console command description.
     */
    protected $description = 'Sync shipments from Magento stores';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $storeId = $this->option('store');
        $days = (int) $this->option('days');
        $pageSize = (int) $this->option('page-size');

        // Validate inputs
        if ($days < 1 || $days > 365) {
            $this->error('Days must be between 1 and 365');

            return self::FAILURE;
        }

        if ($pageSize < 1 || $pageSize > 100) {
            $this->error('Page size must be between 1 and 100');

            return self::FAILURE;
        }

        // Get stores to sync
        $stores = $this->getStoresToSync($storeId);

        if ($stores->isEmpty()) {
            $this->error('No active Magento stores found with sync enabled');

            return self::FAILURE;
        }

        // Show configuration summary
        $this->info("Syncing shipments from {$stores->count()} store(s)");
        $this->info('Configuration:');
        $this->line("  - Last {$days} days");
        $this->line("  - {$pageSize} shipments per page");
        $this->newLine();

        $totalSynced = 0;
        $totalErrors = 0;

        // Process each store
        foreach ($stores as $store) {
            $this->info("Processing: {$store->name} (ID: {$store->id})");

            try {
                $synced = $this->syncShipmentsForStore($store, $days, $pageSize);
                $totalSynced += $synced;
                $this->info("  ✓ Synced {$synced} shipments");
            } catch (\Exception $e) {
                $totalErrors++;
                $this->error("  ✗ Failed: {$e->getMessage()}");
                Log::error('Shipment sync failed for store', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $this->newLine();
        }

        // Summary
        $this->info('Sync Summary:');
        $this->line("  - Total shipments synced: {$totalSynced}");
        $this->line("  - Stores with errors: {$totalErrors}");

        return self::SUCCESS;
    }

    /**
     * Sync shipments for a specific store
     *
     * @param  MagentoStore  $store  The Magento store
     * @param  int  $days  Number of days to look back
     * @param  int  $pageSize  Shipments per page
     * @return int Number of shipments synced
     */
    protected function syncShipmentsForStore(MagentoStore $store, int $days, int $pageSize): int
    {
        $apiClient = new MagentoApiClient($store);
        $shipmentParser = app(ShipmentParser::class);

        // Calculate date filter
        $fromDate = Carbon::now()->subDays($days)->format('Y-m-d H:i:s');

        $filters = [
            [
                'field' => 'created_at',
                'value' => $fromDate,
                'condition' => 'gteq',
            ],
        ];

        $page = 1;
        $totalSynced = 0;

        do {
            $this->line("  Fetching page {$page}...");

            $response = $apiClient->getShipments($filters, $page, $pageSize);
            $shipments = $response['items'] ?? [];
            $totalCount = $response['total_count'] ?? 0;

            $this->line("  Found {$totalCount} total shipments, processing page {$page}...");

            foreach ($shipments as $shipmentData) {
                try {
                    $this->syncShipment($shipmentData, $store);
                    $totalSynced++;
                } catch (\Exception $e) {
                    $this->warn("  ! Failed to sync shipment {$shipmentData['entity_id']}: {$e->getMessage()}");
                    Log::warning('Individual shipment sync failed', [
                        'store_id' => $store->id,
                        'shipment_id' => $shipmentData['entity_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $page++;
        } while (count($shipments) === $pageSize && $totalSynced < $totalCount);

        return $totalSynced;
    }

    /**
     * Sync a single shipment
     *
     * @param  array  $shipmentData  The raw Magento shipment data
     * @param  MagentoStore  $store  The Magento store
     */
    protected function syncShipment(array $shipmentData, MagentoStore $store): void
    {
        $shipmentParser = app(ShipmentParser::class);

        // Parse the shipment data
        $parsedData = $shipmentParser->parseShipment($shipmentData);

        // Find the order this shipment belongs to
        $magentoOrderId = $shipmentData['order_id'] ?? null;

        if (! $magentoOrderId) {
            throw new \RuntimeException('Shipment missing order_id');
        }

        $order = Order::where('tenant_id', $store->tenant_id)
            ->where('magento_order_id', $magentoOrderId)
            ->first();

        if (! $order) {
            Log::warning('Order not found for shipment, skipping', [
                'store_id' => $store->id,
                'magento_order_id' => $magentoOrderId,
                'shipment_id' => $shipmentData['entity_id'] ?? null,
            ]);

            return;
        }

        // Upsert the shipment
        Shipment::updateOrCreate(
            [
                'tenant_id' => $store->tenant_id,
                'magento_shipment_id' => $parsedData['magento_shipment_id'],
            ],
            array_merge($parsedData, ['order_id' => $order->id])
        );

        Log::debug('Shipment synced', [
            'store_id' => $store->id,
            'order_id' => $order->id,
            'shipment_id' => $parsedData['magento_shipment_id'],
            'tracking_number' => $parsedData['tracking_number'],
        ]);
    }

    /**
     * Get stores to sync based on options
     */
    private function getStoresToSync(?string $storeId)
    {
        $query = MagentoStore::where('is_active', true)
            ->where('sync_enabled', true);

        if ($storeId) {
            $query->where('id', $storeId);
        }

        return $query->get();
    }
}

<?php

namespace App\Console\Commands;

use App\Models\MagentoOrderSync;
use App\Models\MagentoStore;
use App\Services\Magento\MagentoApiClient;
use App\Services\Magento\OrderSyncService;
use Illuminate\Console\Command;

class SyncSingleOrderCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'magento:sync-order
                            {order-id : Magento entity_id or increment_id}
                            {--tenant= : REQUIRED: Tenant ID (security: prevents cross-tenant access)}
                            {--store= : Store ID if multiple exist for tenant}';

    /**
     * The console command description.
     */
    protected $description = 'Sync a single order from Magento (for testing/debugging)';

    /**
     * Execute the console command.
     */
    public function handle(OrderSyncService $orderSyncService): int
    {
        // Require tenant parameter for security
        $tenantId = $this->option('tenant');
        if (! $tenantId) {
            $this->error('ERROR: --tenant parameter is required for security isolation.');
            $this->line('Example: php artisan magento:sync-order 12345 --tenant=1');

            return self::FAILURE;
        }

        // Validate tenant exists
        if (! \App\Models\Tenant::find($tenantId)) {
            $this->error("Tenant ID {$tenantId} not found.");

            return self::FAILURE;
        }

        $orderId = $this->argument('order-id');
        $storeId = $this->option('store');

        // Get store
        $store = $this->getStore($tenantId, $storeId);

        if (! $store) {
            $this->error('Store not found or not active');

            return self::FAILURE;
        }

        $this->info("Syncing order {$orderId} from {$store->name}");
        $this->newLine();

        try {
            // Fetch order from Magento
            $client = new MagentoApiClient($store);
            $this->line('Fetching order from Magento API...');

            $orderData = $this->fetchOrder($client, $orderId);

            if (! $orderData) {
                $this->error('Order not found in Magento');

                return self::FAILURE;
            }

            $this->info("✓ Found order: {$orderData['increment_id']} (Entity ID: {$orderData['entity_id']})");
            $this->newLine();

            // Create MagentoOrderSync record
            $this->line('Creating MagentoOrderSync record...');
            $syncRecord = $this->createSyncRecord($store, $orderData);
            $this->info('✓ Sync record created');
            $this->newLine();

            // Transform to Order
            $this->line('Transforming to Order...');
            $order = $orderSyncService->syncOrder($syncRecord);
            $this->info('✓ Order transformed successfully');
            $this->newLine();

            // Display summary
            $this->displaySummary($order);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Sync failed: '.$e->getMessage());
            $this->line('');
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());

            return self::FAILURE;
        }
    }

    /**
     * Get store by ID or default active store
     *
     * @param  int  $tenantId  Tenant ID for security isolation
     * @param  string|null  $storeId  Optional specific store ID
     */
    private function getStore(int $tenantId, ?string $storeId): ?MagentoStore
    {
        $query = MagentoStore::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        if ($storeId) {
            $query->where('id', $storeId);
        } else {
            // Get first active store with sync enabled
            $query->where('sync_enabled', true);
        }

        return $query->first();
    }

    /**
     * Fetch order from Magento API
     */
    private function fetchOrder(MagentoApiClient $client, string $orderId): ?array
    {
        // Try as entity_id first (numeric)
        if (is_numeric($orderId)) {
            try {
                return $client->getOrder((int) $orderId);
            } catch (\Exception $e) {
                // Fall through to increment_id search
            }
        }

        // Try searching by increment_id
        try {
            $response = $client->getOrders([
                [
                    'field' => 'increment_id',
                    'value' => $orderId,
                    'condition' => 'eq',
                ],
            ], 1, 1);

            $orders = $response['items'] ?? [];

            return $orders[0] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create MagentoOrderSync record
     */
    private function createSyncRecord(MagentoStore $store, array $orderData): MagentoOrderSync
    {
        $hasInvoice = isset($orderData['total_invoiced']) && $orderData['total_invoiced'] > 0;
        $hasShipment = isset($orderData['total_qty_shipped']) && $orderData['total_qty_shipped'] > 0;

        return MagentoOrderSync::updateOrCreate(
            [
                'tenant_id' => $store->tenant_id,
                'magento_store_id' => $store->id,
                'entity_id' => $orderData['entity_id'],
            ],
            [
                'increment_id' => $orderData['increment_id'],
                'order_status' => $orderData['status'],
                'has_invoice' => $hasInvoice,
                'has_shipment' => $hasShipment,
                'raw_data' => $orderData,
                'sync_batch_id' => 'manual-single-order',
                'synced_at' => now(),
            ]
        );
    }

    /**
     * Display order summary
     */
    private function displaySummary($order): void
    {
        $this->info('=== Order Summary ===');
        $this->table(
            ['Field', 'Value'],
            [
                ['Increment ID', $order->increment_id],
                ['Magento Order ID', $order->magento_order_id],
                ['Status', $order->status],
                ['Payment Status', $order->payment_status],
                ['Customer', $order->customer_name],
                ['Email', $order->customer_email ?? 'N/A'],
                ['Grand Total', $order->currency_code.' '.number_format($order->grand_total, 2)],
                ['Subtotal', $order->currency_code.' '.number_format($order->subtotal, 2)],
                ['Tax', $order->currency_code.' '.number_format($order->tax_amount, 2)],
                ['Shipping', $order->currency_code.' '.number_format($order->shipping_amount, 2)],
                ['Ordered At', $order->ordered_at->format('Y-m-d H:i:s')],
            ]
        );

        $this->newLine();

        // Items count
        $itemsCount = $order->items()->count();
        $this->info("Order Items: {$itemsCount}");

        if ($itemsCount > 0) {
            $items = $order->items;
            $itemsData = [];
            foreach ($items as $item) {
                $itemsData[] = [
                    $item->sku,
                    $item->name,
                    $item->qty_ordered,
                    number_format($item->price, 2),
                    number_format($item->row_total, 2),
                ];
            }
            $this->table(
                ['SKU', 'Name', 'Qty', 'Price', 'Row Total'],
                $itemsData
            );
        }

        $this->newLine();

        // Invoices count
        $invoicesCount = $order->invoices()->count();
        $this->info("Invoices: {$invoicesCount}");

        // Shipments count
        $shipmentsCount = $order->shipments()->count();
        $this->info("Shipments: {$shipmentsCount}");

        if ($shipmentsCount > 0) {
            $shipments = $order->shipments;
            $shipmentsData = [];
            foreach ($shipments as $shipment) {
                $shipmentsData[] = [
                    $shipment->tracking_number ?? 'N/A',
                    $shipment->carrier_title ?? $shipment->carrier_code ?? 'N/A',
                    $shipment->status,
                    $shipment->shipped_at?->format('Y-m-d H:i:s') ?? 'N/A',
                ];
            }
            $this->table(
                ['Tracking', 'Carrier', 'Status', 'Shipped At'],
                $shipmentsData
            );
        }
    }
}

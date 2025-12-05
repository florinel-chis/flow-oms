<?php

namespace App\Jobs;

use App\Models\MagentoOrderSync;
use App\Models\MagentoStore;
use App\Services\Magento\MagentoApiClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncMagentoOrdersJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of retry attempts.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    /**
     * Maximum execution time in seconds.
     */
    public int $timeout = 600;

    /**
     * Delete job if models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MagentoStore $store,
        public int $days = 1,
        public int $pageSize = 10,
        public ?int $page = null,
        public ?string $batchId = null,
        public bool $transformToOrders = true,
    ) {
        $this->batchId = $this->batchId ?? Str::uuid()->toString();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting Magento order sync', [
                'store_id' => $this->store->id,
                'store_name' => $this->store->name,
                'days' => $this->days,
                'page_size' => $this->pageSize,
                'page' => $this->page,
                'batch_id' => $this->batchId,
            ]);

            $client = new MagentoApiClient($this->store);

            // If page is specified, sync only that page
            if ($this->page !== null) {
                $this->syncPage($client, $this->page);
            } else {
                // Sync all pages
                $this->syncAllPages($client);
            }

            // Update store's last sync timestamp
            $this->store->update([
                'last_sync_at' => now(),
            ]);

            Log::info('Completed Magento order sync', [
                'store_id' => $this->store->id,
                'batch_id' => $this->batchId,
            ]);
        } catch (\Exception $e) {
            Log::error('Magento order sync failed', [
                'store_id' => $this->store->id,
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync all pages with pagination.
     */
    private function syncAllPages(MagentoApiClient $client): void
    {
        $page = 1;
        do {
            $response = $client->getOrdersLastDays(
                days: $this->days,
                page: $page,
                pageSize: $this->pageSize
            );

            $orders = $response['items'] ?? [];
            $this->processOrders($orders);

            $totalCount = $response['total_count'] ?? 0;
            $readCount = ($page * $this->pageSize);
            $hasMore = $readCount < $totalCount;

            Log::info('Synced page', [
                'store_id' => $this->store->id,
                'page' => $page,
                'orders_count' => count($orders),
                'total_count' => $totalCount,
                'has_more' => $hasMore,
            ]);

            $page++;
        } while ($hasMore);
    }

    /**
     * Sync a single page.
     */
    private function syncPage(MagentoApiClient $client, int $page): void
    {
        $response = $client->getOrdersLastDays(
            days: $this->days,
            page: $page,
            pageSize: $this->pageSize
        );

        $orders = $response['items'] ?? [];
        $this->processOrders($orders);

        Log::info('Synced single page', [
            'store_id' => $this->store->id,
            'page' => $page,
            'orders_count' => count($orders),
        ]);
    }

    /**
     * Process and store orders within a database transaction.
     * Each page of orders is processed atomically - if any order fails,
     * the entire page is rolled back to maintain data consistency.
     */
    private function processOrders(array $orders): void
    {
        if (empty($orders)) {
            return;
        }

        DB::transaction(function () use ($orders) {
            foreach ($orders as $orderData) {
                // Process each order - exceptions will rollback entire page
                $this->processOrder($orderData);
            }
        });

        Log::info('Successfully processed orders page', [
            'store_id' => $this->store->id,
            'orders_count' => count($orders),
        ]);
    }

    /**
     * Process a single order.
     * Throws exceptions to trigger transaction rollback.
     */
    private function processOrder(array $orderData): void
    {
        $hasInvoice = $this->determineHasInvoice($orderData);
        $hasShipment = $this->determineHasShipment($orderData);

        $syncRecord = MagentoOrderSync::updateOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'magento_store_id' => $this->store->id,
                'entity_id' => $orderData['entity_id'],
            ],
            [
                'increment_id' => $orderData['increment_id'],
                'order_status' => $orderData['status'],
                'has_invoice' => $hasInvoice,
                'has_shipment' => $hasShipment,
                'raw_data' => $orderData,
                'sync_batch_id' => $this->batchId,
                'synced_at' => now(),
            ]
        );

        // Transform raw data to normalized Order if enabled
        // Exceptions will bubble up to trigger transaction rollback
        if ($this->transformToOrders) {
            $orderSyncService = app(\App\Contracts\Magento\OrderSyncServiceInterface::class);
            $orderSyncService->syncOrder($syncRecord);

            Log::debug('Order transformed successfully', [
                'increment_id' => $syncRecord->increment_id,
                'entity_id' => $syncRecord->entity_id,
            ]);
        }
    }

    /**
     * Determine if order has invoices.
     */
    private function determineHasInvoice(array $orderData): bool
    {
        // Check total_invoiced amount
        if (isset($orderData['total_invoiced']) && $orderData['total_invoiced'] > 0) {
            return true;
        }

        // Check total_qty_invoiced
        if (isset($orderData['total_qty_invoiced']) && $orderData['total_qty_invoiced'] > 0) {
            return true;
        }

        return false;
    }

    /**
     * Determine if order has shipments.
     */
    private function determineHasShipment(array $orderData): bool
    {
        // Check total_qty_shipped
        if (isset($orderData['total_qty_shipped']) && $orderData['total_qty_shipped'] > 0) {
            return true;
        }

        return false;
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncMagentoOrdersJob permanently failed', [
            'store_id' => $this->store->id,
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage(),
        ]);
    }
}

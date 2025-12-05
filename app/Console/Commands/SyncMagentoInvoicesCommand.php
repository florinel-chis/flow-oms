<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\MagentoStore;
use App\Models\Order;
use App\Services\Magento\MagentoApiClient;
use App\Services\Magento\Parsers\InvoiceParser;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMagentoInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'magento:sync-invoices
                            {--store= : Magento store ID to sync (default: all active stores)}
                            {--days=1 : Number of days to look back (default: 1)}
                            {--page-size=100 : Invoices per page (default: 100)}';

    /**
     * The console command description.
     */
    protected $description = 'Sync invoices from Magento stores';

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
        $this->info("Syncing invoices from {$stores->count()} store(s)");
        $this->info('Configuration:');
        $this->line("  - Last {$days} days");
        $this->line("  - {$pageSize} invoices per page");
        $this->newLine();

        $totalSynced = 0;
        $totalErrors = 0;

        // Process each store
        foreach ($stores as $store) {
            $this->info("Processing: {$store->name} (ID: {$store->id})");

            try {
                $synced = $this->syncInvoicesForStore($store, $days, $pageSize);
                $totalSynced += $synced;
                $this->info("  ✓ Synced {$synced} invoices");
            } catch (\Exception $e) {
                $totalErrors++;
                $this->error("  ✗ Failed: {$e->getMessage()}");
                Log::error('Invoice sync failed for store', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $this->newLine();
        }

        // Summary
        $this->info('Sync Summary:');
        $this->line("  - Total invoices synced: {$totalSynced}");
        $this->line("  - Stores with errors: {$totalErrors}");

        return self::SUCCESS;
    }

    /**
     * Sync invoices for a specific store
     *
     * @param  MagentoStore  $store  The Magento store
     * @param  int  $days  Number of days to look back
     * @param  int  $pageSize  Invoices per page
     * @return int Number of invoices synced
     */
    protected function syncInvoicesForStore(MagentoStore $store, int $days, int $pageSize): int
    {
        $apiClient = new MagentoApiClient($store);
        $invoiceParser = app(InvoiceParser::class);

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

            $response = $apiClient->getInvoices($filters, $page, $pageSize);
            $invoices = $response['items'] ?? [];
            $totalCount = $response['total_count'] ?? 0;

            $this->line("  Found {$totalCount} total invoices, processing page {$page}...");

            foreach ($invoices as $invoiceData) {
                try {
                    $this->syncInvoice($invoiceData, $store);
                    $totalSynced++;
                } catch (\Exception $e) {
                    $this->warn("  ! Failed to sync invoice {$invoiceData['entity_id']}: {$e->getMessage()}");
                    Log::warning('Individual invoice sync failed', [
                        'store_id' => $store->id,
                        'invoice_id' => $invoiceData['entity_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $page++;
        } while (count($invoices) === $pageSize && $totalSynced < $totalCount);

        return $totalSynced;
    }

    /**
     * Sync a single invoice
     *
     * @param  array  $invoiceData  The raw Magento invoice data
     * @param  MagentoStore  $store  The Magento store
     */
    protected function syncInvoice(array $invoiceData, MagentoStore $store): void
    {
        $invoiceParser = app(InvoiceParser::class);

        // We need a minimal "order" structure for the parser
        // The parser expects full order data for fallback values
        // For standalone invoice sync, we'll use empty order data
        $orderData = [
            'customer_firstname' => $invoiceData['customer_firstname'] ?? '',
            'customer_lastname' => $invoiceData['customer_lastname'] ?? '',
            'customer_email' => $invoiceData['customer_email'] ?? '',
        ];

        // Parse the invoice data
        $parsedData = $invoiceParser->parseInvoice($invoiceData, $store->id, $orderData);

        // Find the order this invoice belongs to
        $magentoOrderId = $invoiceData['order_id'] ?? null;

        if (! $magentoOrderId) {
            throw new \RuntimeException('Invoice missing order_id');
        }

        $order = Order::where('tenant_id', $store->tenant_id)
            ->where('magento_order_id', $magentoOrderId)
            ->first();

        if (! $order) {
            Log::warning('Order not found for invoice, skipping', [
                'store_id' => $store->id,
                'magento_order_id' => $magentoOrderId,
                'invoice_id' => $invoiceData['entity_id'] ?? null,
            ]);

            return;
        }

        // Extract items from parsed data
        $items = $parsedData['items'] ?? [];
        unset($parsedData['items']);

        // Upsert the invoice
        $invoice = Invoice::updateOrCreate(
            [
                'tenant_id' => $store->tenant_id,
                'magento_invoice_id' => $parsedData['magento_invoice_id'],
            ],
            array_merge($parsedData, ['order_id' => $order->id])
        );

        // Sync invoice items (delete old and recreate)
        $invoice->items()->delete();

        foreach ($items as $itemData) {
            // Map Magento order_item_id to our database order_items.id
            $magentoOrderItemId = $itemData['order_item_id'];
            $orderItem = null;

            if ($magentoOrderItemId) {
                $orderItem = $order->items()
                    ->where('magento_item_id', $magentoOrderItemId)
                    ->first();
            }

            // Set order_item_id to our database ID, or null if not found
            $itemData['order_item_id'] = $orderItem?->id;

            $invoice->items()->create(array_merge($itemData, [
                'tenant_id' => $store->tenant_id,
            ]));
        }

        Log::debug('Invoice synced', [
            'store_id' => $store->id,
            'order_id' => $order->id,
            'invoice_id' => $parsedData['magento_invoice_id'],
            'increment_id' => $parsedData['increment_id'],
            'items_count' => count($items),
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

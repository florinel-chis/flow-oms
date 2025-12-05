<?php

namespace App\Services\Magento;

use App\Contracts\Magento\OrderSyncServiceInterface;
use App\Contracts\Magento\Parsers\InvoiceParserInterface;
use App\Contracts\Magento\Parsers\ShipmentParserInterface;
use App\Events\OrderSynced;
use App\Events\OrderSyncFailed;
use App\Exceptions\OrderSyncException;
use App\Models\Invoice;
use App\Models\MagentoOrderSync;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Magento\Parsers\OrderItemParser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for transforming raw Magento order data into normalized Order records
 */
class OrderSyncService implements OrderSyncServiceInterface
{
    public function __construct(
        protected OrderItemParser $orderItemParser,
        protected InvoiceParserInterface $invoiceParser,
        protected ShipmentParserInterface $shipmentParser,
    ) {}

    /**
     * Synchronize a single order from MagentoOrderSync to Order
     *
     * @param  MagentoOrderSync  $syncRecord  The sync record containing raw Magento data
     * @return Order The synchronized order
     *
     * @throws OrderSyncException
     */
    public function syncOrder(MagentoOrderSync $syncRecord): Order
    {
        try {
            // Define the sync logic
            $syncLogic = function () use ($syncRecord) {
                $rawData = $syncRecord->raw_data;

                // Transform raw data to Order model attributes
                $orderData = $this->transformOrderData($rawData, $syncRecord);

                // Upsert order using unique constraint [tenant_id, magento_order_id]
                $order = Order::updateOrCreate(
                    [
                        'tenant_id' => $syncRecord->tenant_id,
                        'magento_order_id' => $syncRecord->entity_id,
                    ],
                    $orderData
                );

                $wasRecentlyCreated = $order->wasRecentlyCreated;

                // Sync order items (delete old and recreate)
                $this->syncOrderItems($order, $rawData);

                // Sync invoices if present
                $this->syncInvoicesIfPresent($order, $rawData, $syncRecord->magento_store_id);

                // Sync shipments if present
                $this->syncShipmentsIfPresent($order, $rawData);

                Log::info('Order synchronized successfully', [
                    'order_id' => $order->id,
                    'increment_id' => $order->increment_id,
                    'magento_order_id' => $order->magento_order_id,
                    'is_new' => $wasRecentlyCreated,
                ]);

                // Dispatch success event
                event(new OrderSynced($order, $wasRecentlyCreated));

                return $order;
            };

            // If already in transaction (e.g., called from job), use savepoint
            // Otherwise, create new transaction
            if (DB::transactionLevel() > 0) {
                return $syncLogic();
            }

            return DB::transaction($syncLogic);
        } catch (\Exception $e) {
            // Dispatch failure event
            event(new OrderSyncFailed($syncRecord, $e));

            throw new OrderSyncException(
                "Failed to sync order {$syncRecord->increment_id}: {$e->getMessage()}",
                $syncRecord->raw_data,
                $e
            );
        }
    }

    /**
     * Synchronize multiple orders
     *
     * @param  Collection  $syncRecords  Collection of MagentoOrderSync records
     * @return Collection Collection of synchronized Order records
     */
    public function syncOrders(Collection $syncRecords): Collection
    {
        $orders = collect();

        foreach ($syncRecords as $syncRecord) {
            try {
                $order = $this->syncOrder($syncRecord);
                $orders->push($order);
            } catch (OrderSyncException $e) {
                Log::error('Order sync failed in batch', $e->getContext());
                // Continue with next order
            }
        }

        return $orders;
    }

    /**
     * Transform raw Magento data to Order model attributes
     *
     * @param  array  $rawData  The raw Magento order data
     * @param  MagentoOrderSync  $syncRecord  The sync record for tenant context
     * @return array Order attributes
     */
    protected function transformOrderData(array $rawData, MagentoOrderSync $syncRecord): array
    {
        return [
            'tenant_id' => $syncRecord->tenant_id,
            'magento_store_id' => $syncRecord->magento_store_id,
            'magento_order_id' => $rawData['entity_id'],
            'increment_id' => $rawData['increment_id'],
            'status' => $rawData['status'],
            'payment_status' => $this->derivePaymentStatus($rawData),
            'priority' => 'normal', // Default priority, can be enhanced later
            'source' => 'magento',
            'customer_name' => $this->getCustomerName($rawData),
            'customer_email' => $rawData['customer_email'] ?? null,
            'grand_total' => (float) ($rawData['grand_total'] ?? 0),
            'subtotal' => (float) ($rawData['subtotal'] ?? 0),
            'tax_amount' => (float) ($rawData['tax_amount'] ?? 0),
            'shipping_amount' => (float) ($rawData['shipping_amount'] ?? 0),
            'discount_amount' => abs((float) ($rawData['discount_amount'] ?? 0)),
            'currency_code' => $rawData['order_currency_code'] ?? 'USD',
            'payment_method' => $this->getPaymentMethod($rawData),
            'shipping_method' => $rawData['shipping_description'] ?? null,
            'ordered_at' => $this->parseOrderedAt($rawData),
            'synced_at' => now(),
        ];
    }

    /**
     * Derive payment status from raw order data
     *
     * Uses Magento's payment fields to accurately determine status:
     * - total_paid: Amount actually paid by customer
     * - total_due: Remaining amount to be paid (grand_total - total_paid + total_refunded)
     * - total_refunded: Amount refunded to customer
     * - grand_total: Total order amount
     *
     * @param  array  $rawData  The raw Magento order data
     * @return string Payment status
     */
    protected function derivePaymentStatus(array $rawData): string
    {
        $grandTotal = (float) ($rawData['grand_total'] ?? 0);
        $totalPaid = (float) ($rawData['total_paid'] ?? 0);
        $totalDue = (float) ($rawData['total_due'] ?? 0);
        $totalRefunded = (float) ($rawData['total_refunded'] ?? 0);
        $status = $rawData['status'] ?? '';

        // If fully refunded, mark as refunded
        if ($totalRefunded > 0 && $totalRefunded >= $totalPaid) {
            return 'refunded';
        }

        // If no amount due, order is fully paid (accounting for partial refunds)
        if ($totalDue == 0 && $totalPaid > 0) {
            return 'paid';
        }

        // Alternative check: total_paid matches or exceeds grand_total
        if ($totalPaid >= $grandTotal && $grandTotal > 0) {
            return 'paid';
        }

        // Check status for payment indicators
        if (in_array($status, ['canceled', 'closed'])) {
            return 'failed';
        }

        if ($status === 'pending_payment' || $totalDue > 0) {
            return 'pending';
        }

        // Default to pending for new orders
        return 'pending';
    }

    /**
     * Get customer name from raw data
     *
     * @param  array  $rawData  The raw Magento order data
     * @return string Customer name
     */
    protected function getCustomerName(array $rawData): string
    {
        $firstname = $rawData['customer_firstname'] ?? '';
        $lastname = $rawData['customer_lastname'] ?? '';

        if (empty($firstname) && empty($lastname)) {
            return 'Guest';
        }

        return trim("{$firstname} {$lastname}");
    }

    /**
     * Get payment method from raw data
     *
     * @param  array  $rawData  The raw Magento order data
     * @return string|null Payment method
     */
    protected function getPaymentMethod(array $rawData): ?string
    {
        return $rawData['payment']['method'] ?? null;
    }

    /**
     * Parse ordered_at timestamp
     *
     * @param  array  $rawData  The raw Magento order data
     */
    protected function parseOrderedAt(array $rawData): ?Carbon
    {
        $createdAt = $rawData['created_at'] ?? null;

        if (empty($createdAt)) {
            return null;
        }

        try {
            return Carbon::parse($createdAt);
        } catch (\Exception $e) {
            Log::warning('Failed to parse order created_at timestamp', [
                'order_id' => $rawData['entity_id'] ?? null,
                'created_at' => $createdAt,
            ]);

            return null;
        }
    }

    /**
     * Sync order items using optimized batch operations.
     *
     * Performance optimization:
     * - Uses single bulk INSERT for all items (vs N individual INSERTs)
     * - Uses single CASE UPDATE for parent links (vs N individual UPDATEs)
     * - Reduces query count from 2N+1 to 4 queries per order
     *
     * @param  Order  $order  The order to sync items for
     * @param  array  $rawData  The raw Magento order data
     */
    protected function syncOrderItems(Order $order, array $rawData): void
    {
        // Delete existing items (single query)
        $order->items()->delete();

        // Parse new items
        $itemsData = $this->orderItemParser->parse($rawData);

        if (empty($itemsData)) {
            return;
        }

        // Prepare bulk insert data
        $now = now();
        $insertData = [];
        $parentMapping = []; // Track which items have parents

        foreach ($itemsData as $itemData) {
            $magentoParentItemId = $itemData['magento_parent_item_id'] ?? null;
            unset($itemData['magento_parent_item_id']);

            $insertData[] = array_merge($itemData, [
                'order_id' => $order->id,
                'tenant_id' => $order->tenant_id,
                'parent_item_id' => null, // Will be set after insert
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($magentoParentItemId && $itemData['magento_item_id']) {
                $parentMapping[$itemData['magento_item_id']] = $magentoParentItemId;
            }
        }

        // Bulk insert all items (single query)
        DB::table('order_items')->insert($insertData);

        // If there are parent-child relationships, update them
        if (! empty($parentMapping)) {
            // Fetch inserted items to get their IDs (single query)
            $insertedItems = $order->items()
                ->whereNotNull('magento_item_id')
                ->get(['id', 'magento_item_id'])
                ->keyBy('magento_item_id');

            // Build parent_item_id updates
            $updates = [];
            foreach ($parentMapping as $childMagentoId => $parentMagentoId) {
                $childItem = $insertedItems[$childMagentoId] ?? null;
                $parentItem = $insertedItems[$parentMagentoId] ?? null;

                if ($childItem && $parentItem) {
                    $updates[$childItem->id] = $parentItem->id;
                }
            }

            // Batch update parent_item_id using CASE WHEN (single query)
            if (! empty($updates)) {
                $cases = [];
                $ids = [];
                foreach ($updates as $childId => $parentId) {
                    $cases[] = "WHEN id = {$childId} THEN {$parentId}";
                    $ids[] = $childId;
                }

                DB::statement(
                    'UPDATE order_items SET parent_item_id = CASE '.
                    implode(' ', $cases).
                    ' END WHERE id IN ('.implode(',', $ids).')'
                );
            }

            Log::debug('Order items synced with batch operations', [
                'order_id' => $order->id,
                'items_count' => count($itemsData),
                'parent_links_created' => count($updates),
            ]);
        } else {
            Log::debug('Order items synced (no parent-child relationships)', [
                'order_id' => $order->id,
                'items_count' => count($itemsData),
            ]);
        }
    }

    /**
     * Sync invoices if present in order data.
     *
     * Performance optimization: Uses bulk inserts for invoice items.
     *
     * @param  Order  $order  The order to sync invoices for
     * @param  array  $rawData  The raw Magento order data
     * @param  int  $magentoStoreId  The Magento store ID
     */
    protected function syncInvoicesIfPresent(Order $order, array $rawData, int $magentoStoreId): void
    {
        $invoicesData = $this->invoiceParser->parse($rawData, $magentoStoreId);

        if (empty($invoicesData)) {
            return;
        }

        $now = now();

        foreach ($invoicesData as $invoiceData) {
            $items = $invoiceData['items'] ?? [];
            unset($invoiceData['items']);

            // Upsert invoice
            $invoice = Invoice::updateOrCreate(
                [
                    'tenant_id' => $order->tenant_id,
                    'magento_invoice_id' => $invoiceData['magento_invoice_id'],
                ],
                array_merge($invoiceData, ['order_id' => $order->id])
            );

            // Sync invoice items with bulk insert
            $invoice->items()->delete();

            if (! empty($items)) {
                $insertData = array_map(fn ($item) => array_merge($item, [
                    'invoice_id' => $invoice->id,
                    'tenant_id' => $order->tenant_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]), $items);

                DB::table('invoice_items')->insert($insertData);
            }
        }

        Log::debug('Invoices synced with batch operations', [
            'order_id' => $order->id,
            'invoice_count' => count($invoicesData),
        ]);
    }

    /**
     * Sync shipments if present in order data
     *
     * @param  Order  $order  The order to sync shipments for
     * @param  array  $rawData  The raw Magento order data
     */
    protected function syncShipmentsIfPresent(Order $order, array $rawData): void
    {
        $shipmentsData = $this->shipmentParser->parse($rawData);

        if (empty($shipmentsData)) {
            return;
        }

        foreach ($shipmentsData as $shipmentData) {
            // Upsert shipment
            Shipment::updateOrCreate(
                [
                    'tenant_id' => $order->tenant_id,
                    'magento_shipment_id' => $shipmentData['magento_shipment_id'],
                ],
                array_merge($shipmentData, ['order_id' => $order->id])
            );
        }

        Log::debug('Shipments synced', [
            'order_id' => $order->id,
            'shipment_count' => count($shipmentsData),
        ]);
    }
}

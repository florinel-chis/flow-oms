<?php

namespace App\Services\Magento\Parsers;

use App\Contracts\Magento\MagentoApiClientInterface;
use App\Contracts\Magento\Parsers\InvoiceParserInterface;
use App\Enums\InvoiceState;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Parses invoice data from Magento API response
 */
class InvoiceParser implements InvoiceParserInterface
{
    public function __construct(
        protected MagentoApiClientInterface $apiClient
    ) {}

    /**
     * Parse invoices from raw Magento order data
     *
     * @param  array  $rawData  The raw Magento order data
     * @param  int  $magentoStoreId  The Magento store ID
     * @return array Array of invoice data with their items
     */
    public function parse(array $rawData, int $magentoStoreId): array
    {
        $invoices = [];

        // Strategy 1: Check for embedded invoices in extension_attributes
        if (! empty($rawData['extension_attributes']['invoices'])) {
            Log::debug('Found embedded invoices in order', [
                'order_id' => $rawData['entity_id'] ?? null,
                'invoice_count' => count($rawData['extension_attributes']['invoices']),
            ]);

            foreach ($rawData['extension_attributes']['invoices'] as $invoiceData) {
                $invoices[] = $this->parseInvoice($invoiceData, $magentoStoreId, $rawData);
            }

            return $invoices;
        }

        // Strategy 2: If total_invoiced > 0 but no embedded invoices, fetch from API
        $totalInvoiced = (float) ($rawData['total_invoiced'] ?? 0);
        if ($totalInvoiced > 0) {
            Log::debug('Order has invoices but not embedded, fetching from API', [
                'order_id' => $rawData['entity_id'] ?? null,
                'total_invoiced' => $totalInvoiced,
            ]);

            try {
                $orderId = $rawData['entity_id'];
                $fetchedInvoices = $this->apiClient->getInvoicesForOrder($orderId);

                foreach ($fetchedInvoices as $invoiceData) {
                    $invoices[] = $this->parseInvoice($invoiceData, $magentoStoreId, $rawData);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch invoices from API', [
                    'order_id' => $rawData['entity_id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $invoices;
    }

    /**
     * Parse a single invoice
     *
     * @param  array  $invoiceData  The raw Magento invoice data
     * @param  int  $magentoStoreId  The Magento store ID
     * @param  array  $orderData  The parent order data for fallback values
     * @return array Parsed invoice with items
     */
    public function parseInvoice(array $invoiceData, int $magentoStoreId, array $orderData): array
    {
        $invoice = [
            'magento_store_id' => $magentoStoreId,
            'magento_invoice_id' => $invoiceData['entity_id'] ?? null,
            'increment_id' => $invoiceData['increment_id'] ?? null,
            'state' => $this->mapInvoiceState($invoiceData['state'] ?? 1),
            'grand_total' => (float) ($invoiceData['grand_total'] ?? 0),
            'subtotal' => (float) ($invoiceData['subtotal'] ?? 0),
            'tax_amount' => (float) ($invoiceData['tax_amount'] ?? 0),
            'shipping_amount' => (float) ($invoiceData['shipping_amount'] ?? 0),
            'discount_amount' => abs((float) ($invoiceData['discount_amount'] ?? 0)),
            'base_grand_total' => (float) ($invoiceData['base_grand_total'] ?? 0),
            'base_subtotal' => (float) ($invoiceData['base_subtotal'] ?? 0),
            'billing_address_id' => $invoiceData['billing_address_id'] ?? null,
            'customer_name' => $this->getCustomerName($invoiceData, $orderData),
            'customer_email' => $invoiceData['customer_email'] ?? $orderData['customer_email'] ?? null,
            'invoiced_at' => $this->parseInvoicedAt($invoiceData),
        ];

        // Parse invoice items
        $invoice['items'] = $this->parseInvoiceItems($invoiceData['items'] ?? []);

        return $invoice;
    }

    /**
     * Parse invoice items
     *
     * @param  array  $items  The raw Magento invoice items
     * @return array Parsed invoice items
     */
    protected function parseInvoiceItems(array $items): array
    {
        $parsedItems = [];

        foreach ($items as $item) {
            $parsedItems[] = [
                'order_item_id' => $item['order_item_id'] ?? null,
                'magento_item_id' => $item['entity_id'] ?? null,
                'product_name' => $item['name'] ?? '',
                'sku' => $item['sku'] ?? '',
                'qty' => (int) ($item['qty'] ?? 0),
                'price' => (float) ($item['price'] ?? 0),
                'row_total' => (float) ($item['row_total'] ?? 0),
                'tax_amount' => (float) ($item['tax_amount'] ?? 0),
            ];
        }

        return $parsedItems;
    }

    /**
     * Map Magento invoice state to InvoiceState enum
     *
     * Magento invoice states:
     * 1 = Open
     * 2 = Paid
     * 3 = Canceled
     *
     * @param  int|string  $magentoState  The Magento invoice state
     */
    protected function mapInvoiceState(int|string $magentoState): InvoiceState
    {
        return match ((int) $magentoState) {
            1 => InvoiceState::OPEN,
            2 => InvoiceState::PAID,
            3 => InvoiceState::CANCELED,
            default => InvoiceState::OPEN,
        };
    }

    /**
     * Get customer name from invoice or order data
     *
     * @param  array  $invoiceData  The invoice data
     * @param  array  $orderData  The order data as fallback
     */
    protected function getCustomerName(array $invoiceData, array $orderData): string
    {
        // Try invoice data first
        if (! empty($invoiceData['customer_firstname']) && ! empty($invoiceData['customer_lastname'])) {
            return trim($invoiceData['customer_firstname'].' '.$invoiceData['customer_lastname']);
        }

        // Fallback to order data
        if (! empty($orderData['customer_firstname']) && ! empty($orderData['customer_lastname'])) {
            return trim($orderData['customer_firstname'].' '.$orderData['customer_lastname']);
        }

        return 'Guest';
    }

    /**
     * Parse invoiced_at timestamp
     *
     * @param  array  $invoiceData  The invoice data
     */
    protected function parseInvoicedAt(array $invoiceData): ?Carbon
    {
        $createdAt = $invoiceData['created_at'] ?? null;

        if (empty($createdAt)) {
            return null;
        }

        try {
            return Carbon::parse($createdAt);
        } catch (\Exception $e) {
            Log::warning('Failed to parse invoice created_at timestamp', [
                'invoice_id' => $invoiceData['entity_id'] ?? null,
                'created_at' => $createdAt,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

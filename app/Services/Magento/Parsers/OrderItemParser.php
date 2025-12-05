<?php

namespace App\Services\Magento\Parsers;

use Illuminate\Support\Facades\Log;

/**
 * Parses order items from Magento API response
 *
 * Preserves complete product hierarchy including:
 * - Bundle products (parent) with child items
 * - Configurable products (parent) with child items
 * - Simple standalone products
 */
class OrderItemParser
{
    /**
     * Parse order items from raw Magento data
     *
     * @param  array  $rawData  The raw Magento order data
     * @return array Array of order item data with parent-child relationships
     */
    public function parse(array $rawData): array
    {
        $items = $rawData['items'] ?? [];
        $parsedItems = [];

        // First pass: Parse all items (including parents)
        foreach ($items as $item) {
            if (! $this->shouldSyncItem($item)) {
                continue;
            }

            $parsedItems[] = $this->parseItem($item);
        }

        Log::debug('Order items parsed', [
            'total_items' => count($parsedItems),
            'parent_items' => count(array_filter($parsedItems, fn ($i) => in_array($i['product_type'], ['bundle', 'configurable']))),
            'child_items' => count(array_filter($parsedItems, fn ($i) => $i['magento_parent_item_id'] !== null)),
        ]);

        return $parsedItems;
    }

    /**
     * Parse a single order item
     *
     * @param  array  $item  The raw Magento item data
     * @return array Parsed item data
     */
    protected function parseItem(array $item): array
    {
        return [
            // Magento identifiers
            'magento_item_id' => $item['item_id'] ?? null,
            'magento_parent_item_id' => $item['parent_item_id'] ?? null, // Will be mapped to DB ID later

            // Product info
            'product_id' => null, // Will be linked later if product exists in our system
            'product_type' => $item['product_type'] ?? 'simple',
            'sku' => $item['sku'] ?? '',
            'name' => $item['name'] ?? '',

            // Quantities
            'qty_ordered' => (float) ($item['qty_ordered'] ?? 0),
            'qty_shipped' => (float) ($item['qty_shipped'] ?? 0),
            'qty_canceled' => (float) ($item['qty_canceled'] ?? 0),

            // Pricing
            'price' => (float) ($item['price'] ?? 0),
            'row_total' => (float) ($item['row_total'] ?? 0),
            'tax_amount' => (float) ($item['tax_amount'] ?? 0),
            'discount_amount' => (float) ($item['discount_amount'] ?? 0),
        ];
    }

    /**
     * Check if an item should be synced
     *
     * @param  array  $item  The raw Magento item data
     */
    protected function shouldSyncItem(array $item): bool
    {
        // Skip items with no SKU
        if (empty($item['sku'])) {
            Log::warning('Skipping item with empty SKU', [
                'item_id' => $item['item_id'] ?? null,
                'name' => $item['name'] ?? 'unknown',
            ]);

            return false;
        }

        return true;
    }
}

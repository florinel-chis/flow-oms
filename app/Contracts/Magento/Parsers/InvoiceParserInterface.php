<?php

namespace App\Contracts\Magento\Parsers;

interface InvoiceParserInterface
{
    /**
     * Parse invoices from raw Magento order data.
     *
     * @param  array  $rawData  Raw Magento order data
     * @param  int  $magentoStoreId  The Magento store ID
     * @return array Array of parsed invoice data with items
     */
    public function parse(array $rawData, int $magentoStoreId): array;

    /**
     * Parse a single invoice.
     *
     * @param  array  $invoiceData  Raw Magento invoice data
     * @param  int  $magentoStoreId  The Magento store ID
     * @param  array  $orderData  Parent order data for fallback values
     * @return array Parsed invoice with items
     */
    public function parseInvoice(array $invoiceData, int $magentoStoreId, array $orderData): array;
}

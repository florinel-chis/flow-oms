<?php

namespace App\Contracts\Magento;

interface MagentoApiClientInterface
{
    /**
     * Test connection to Magento store.
     *
     * @return array Store configuration
     *
     * @throws \RuntimeException On connection failure
     */
    public function testConnection(): array;

    /**
     * Get orders with filters and pagination.
     *
     * @param  array  $filters  Array of filter conditions
     * @param  int  $page  Page number
     * @param  int  $pageSize  Items per page
     * @return array Orders response with items and total_count
     */
    public function getOrders(array $filters = [], int $page = 1, int $pageSize = 100): array;

    /**
     * Get orders since a specific date.
     *
     * @param  \DateTimeInterface  $since  Start date
     * @param  int  $page  Page number
     * @param  int  $pageSize  Items per page
     * @return array Orders response
     */
    public function getOrdersSince(\DateTimeInterface $since, int $page = 1, int $pageSize = 100): array;

    /**
     * Get orders from last N days.
     *
     * @param  int  $days  Number of days to look back
     * @param  int  $page  Page number
     * @param  int  $pageSize  Items per page
     * @return array Orders response
     */
    public function getOrdersLastDays(int $days = 1, int $page = 1, int $pageSize = 100): array;

    /**
     * Get a single order by ID.
     *
     * @param  int  $orderId  Magento order entity ID
     * @return array Order data
     *
     * @throws \RuntimeException On fetch failure
     */
    public function getOrder(int $orderId): array;

    /**
     * Get invoices with filters.
     *
     * @param  array  $filters  Array of filter conditions
     * @param  int  $page  Page number
     * @param  int  $pageSize  Items per page
     * @return array Invoices response with items and total_count
     */
    public function getInvoices(array $filters = [], int $page = 1, int $pageSize = 100): array;

    /**
     * Get invoices for a specific order.
     *
     * @param  int  $orderId  The Magento order entity ID
     * @return array Array of invoice items
     */
    public function getInvoicesForOrder(int $orderId): array;

    /**
     * Get shipments with filters.
     *
     * @param  array  $filters  Array of filter conditions
     * @param  int  $page  Page number
     * @param  int  $pageSize  Items per page
     * @return array Shipments response with items and total_count
     */
    public function getShipments(array $filters = [], int $page = 1, int $pageSize = 100): array;

    /**
     * Get shipments for a specific order.
     *
     * @param  int  $orderId  The Magento order entity ID
     * @return array Array of shipment items
     */
    public function getShipmentsForOrder(int $orderId): array;

    /**
     * Get products with pagination.
     *
     * @param  int  $page  Page number
     * @param  int  $pageSize  Items per page
     * @return array Products response
     */
    public function getProducts(int $page = 1, int $pageSize = 100): array;

    /**
     * Generator for all products.
     *
     * @param  int  $pageSize  Items per page
     * @return \Generator Yields individual product arrays
     */
    public function getAllProducts(int $pageSize = 100): \Generator;

    /**
     * Generator for all orders matching filters.
     *
     * @param  array  $filters  Array of filter conditions
     * @param  int  $pageSize  Items per page
     * @return \Generator Yields individual order arrays
     */
    public function getAllOrders(array $filters = [], int $pageSize = 100): \Generator;

    /**
     * Cancel an order in Magento.
     *
     * @param  int  $orderId  The Magento order entity ID
     * @return array Cancellation response data
     *
     * @throws \RuntimeException On cancellation failure
     */
    public function cancelOrder(int $orderId): array;
}

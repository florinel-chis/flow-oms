<?php

namespace App\Contracts\Magento;

use App\Models\MagentoOrderSync;
use App\Models\Order;
use Illuminate\Support\Collection;

interface OrderSyncServiceInterface
{
    /**
     * Synchronize a single order from raw sync record to normalized Order.
     *
     * @param  MagentoOrderSync  $syncRecord  Raw sync record
     * @return Order The synchronized order
     *
     * @throws \App\Exceptions\OrderSyncException On sync failure
     */
    public function syncOrder(MagentoOrderSync $syncRecord): Order;

    /**
     * Synchronize multiple orders.
     *
     * @param  Collection  $syncRecords  Collection of MagentoOrderSync records
     * @return Collection Collection of synchronized Order records
     */
    public function syncOrders(Collection $syncRecords): Collection;
}

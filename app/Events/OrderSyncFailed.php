<?php

namespace App\Events;

use App\Models\MagentoOrderSync;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Event dispatched when order synchronization fails
 */
class OrderSyncFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new OrderSyncFailed event instance
     *
     * @param  MagentoOrderSync  $syncRecord  The sync record that failed transformation
     * @param  Throwable  $exception  The exception that caused the failure
     */
    public function __construct(
        public MagentoOrderSync $syncRecord,
        public Throwable $exception,
    ) {}

    /**
     * Get the order increment ID for logging
     */
    public function getIncrementId(): ?string
    {
        return $this->syncRecord->increment_id;
    }

    /**
     * Get context for logging
     */
    public function getContext(): array
    {
        return [
            'sync_record_id' => $this->syncRecord->id,
            'increment_id' => $this->syncRecord->increment_id,
            'magento_order_id' => $this->syncRecord->entity_id,
            'error_message' => $this->exception->getMessage(),
            'error_class' => get_class($this->exception),
            'order_status' => $this->syncRecord->order_status,
        ];
    }
}

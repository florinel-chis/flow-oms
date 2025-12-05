<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when order synchronization fails
 */
class OrderSyncException extends Exception
{
    /**
     * Create a new OrderSyncException instance
     *
     * @param  string  $message  The exception message
     * @param  array|null  $orderData  The raw order data that failed to sync
     * @param  \Throwable|null  $previous  The previous exception
     */
    public function __construct(
        string $message,
        public readonly ?array $orderData = null,
        public readonly ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the order increment ID if available
     */
    public function getOrderIncrementId(): ?string
    {
        return $this->orderData['increment_id'] ?? null;
    }

    /**
     * Get the order entity ID if available
     */
    public function getOrderEntityId(): ?int
    {
        return $this->orderData['entity_id'] ?? null;
    }

    /**
     * Get context for logging
     */
    public function getContext(): array
    {
        return [
            'message' => $this->getMessage(),
            'increment_id' => $this->getOrderIncrementId(),
            'entity_id' => $this->getOrderEntityId(),
            'previous_error' => $this->previous?->getMessage(),
        ];
    }
}

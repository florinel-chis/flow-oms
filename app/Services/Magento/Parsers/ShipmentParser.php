<?php

namespace App\Services\Magento\Parsers;

use App\Contracts\Magento\MagentoApiClientInterface;
use App\Contracts\Magento\Parsers\ShipmentParserInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Parses shipment data from Magento API response
 */
class ShipmentParser implements ShipmentParserInterface
{
    public function __construct(
        protected MagentoApiClientInterface $apiClient
    ) {}

    /**
     * Parse shipments from raw Magento order data
     *
     * @param  array  $rawData  The raw Magento order data
     * @return array Array of shipment data
     */
    public function parse(array $rawData): array
    {
        $shipments = [];

        // Strategy 1: Check for embedded shipments in extension_attributes
        if (! empty($rawData['extension_attributes']['shipments'])) {
            Log::debug('Found embedded shipments in order', [
                'order_id' => $rawData['entity_id'] ?? null,
                'shipment_count' => count($rawData['extension_attributes']['shipments']),
            ]);

            foreach ($rawData['extension_attributes']['shipments'] as $shipmentData) {
                $shipments[] = $this->parseShipment($shipmentData);
            }

            return $shipments;
        }

        // Strategy 2: If total_qty_shipped > 0 but no embedded shipments, fetch from API
        $totalQtyShipped = (float) ($rawData['total_qty_shipped'] ?? 0);
        if ($totalQtyShipped > 0) {
            Log::debug('Order has shipments but not embedded, fetching from API', [
                'order_id' => $rawData['entity_id'] ?? null,
                'total_qty_shipped' => $totalQtyShipped,
            ]);

            try {
                $orderId = $rawData['entity_id'];
                $fetchedShipments = $this->apiClient->getShipmentsForOrder($orderId);

                foreach ($fetchedShipments as $shipmentData) {
                    $shipments[] = $this->parseShipment($shipmentData);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch shipments from API', [
                    'order_id' => $rawData['entity_id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $shipments;
    }

    /**
     * Parse a single shipment
     *
     * @param  array  $shipmentData  The raw Magento shipment data
     * @return array Parsed shipment data
     */
    public function parseShipment(array $shipmentData): array
    {
        // Extract tracking information from tracks array (use first track if multiple exist)
        $trackingData = $this->extractTrackingData($shipmentData['tracks'] ?? []);

        return [
            'magento_shipment_id' => $shipmentData['entity_id'] ?? null,
            'tracking_number' => $trackingData['tracking_number'],
            'carrier_code' => $trackingData['carrier_code'],
            'carrier_title' => $trackingData['carrier_title'],
            'status' => 'in_transit', // Default status, will be updated by tracking sync later
            'shipped_at' => $this->parseShippedAt($shipmentData),
            'estimated_delivery_at' => null, // Will be populated by tracking sync
            'actual_delivery_at' => null, // Will be populated by tracking sync
            'last_tracking_update_at' => null, // Will be populated by tracking sync
        ];
    }

    /**
     * Extract tracking data from tracks array
     *
     * @param  array  $tracks  The tracks array from shipment data
     * @return array Tracking data with keys: tracking_number, carrier_code, carrier_title
     */
    protected function extractTrackingData(array $tracks): array
    {
        $defaultData = [
            'tracking_number' => null,
            'carrier_code' => null,
            'carrier_title' => null,
        ];

        if (empty($tracks)) {
            return $defaultData;
        }

        // Use first track (most shipments have single tracking number)
        $track = $tracks[0];

        return [
            'tracking_number' => $track['track_number'] ?? null,
            'carrier_code' => $track['carrier_code'] ?? null,
            'carrier_title' => $track['title'] ?? null,
        ];
    }

    /**
     * Parse shipped_at timestamp from shipment created_at
     *
     * @param  array  $shipmentData  The shipment data
     */
    protected function parseShippedAt(array $shipmentData): ?Carbon
    {
        $createdAt = $shipmentData['created_at'] ?? null;

        if (empty($createdAt)) {
            return null;
        }

        try {
            return Carbon::parse($createdAt);
        } catch (\Exception $e) {
            Log::warning('Failed to parse shipment created_at timestamp', [
                'shipment_id' => $shipmentData['entity_id'] ?? null,
                'created_at' => $createdAt,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

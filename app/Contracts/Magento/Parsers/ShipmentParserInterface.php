<?php

namespace App\Contracts\Magento\Parsers;

interface ShipmentParserInterface
{
    /**
     * Parse shipments from raw Magento order data.
     *
     * @param  array  $rawData  Raw Magento order data
     * @return array Array of parsed shipment data
     */
    public function parse(array $rawData): array;

    /**
     * Parse a single shipment.
     *
     * @param  array  $shipmentData  Raw Magento shipment data
     * @return array Parsed shipment data
     */
    public function parseShipment(array $shipmentData): array;
}

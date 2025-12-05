<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Api\ApiResponse;
use App\DTOs\Api\DeliveryUpdateRequest;
use App\Enums\ApiErrorCode;
use App\Events\ShipmentDelivered;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateShipmentDeliveryRequest;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class ShipmentDeliveryController extends Controller
{
    /**
     * Update delivery information for a shipment.
     *
     * PATCH /api/v1/shipments/{magento_shipment_id}/delivery
     */
    public function updateDelivery(
        UpdateShipmentDeliveryRequest $request,
        string $magentoShipmentId,
    ): JsonResponse {
        $token = $this->getCurrentToken($request);

        // Find the shipment with tenant awareness
        $shipment = $this->findShipment($magentoShipmentId, $token);

        if (! $shipment) {
            return ApiResponse::error(
                errorCode: ApiErrorCode::SHIPMENT_NOT_FOUND,
                message: "No shipment found with Magento shipment ID: {$magentoShipmentId}",
            )->toResponse();
        }

        // Verify carrier code matches if provided
        $carrierCode = $request->validated('carrier_code');
        if ($carrierCode && $shipment->carrier_code !== $carrierCode) {
            Log::warning('Carrier code mismatch in delivery update', [
                'magento_shipment_id' => $magentoShipmentId,
                'expected_carrier' => $carrierCode,
                'actual_carrier' => $shipment->carrier_code,
                'token_id' => $token?->id,
            ]);
        }

        // Check if already delivered (optional: you might want to allow updates)
        if ($shipment->actual_delivery_at !== null) {
            // Allow update but log the overwrite
            Log::info('Updating existing delivery information', [
                'shipment_id' => $shipment->id,
                'magento_shipment_id' => $magentoShipmentId,
                'previous_delivery_at' => $shipment->actual_delivery_at->toIso8601String(),
            ]);
        }

        try {
            // Create DTO from validated request
            $dto = DeliveryUpdateRequest::fromArray($request->validated());

            // Update the shipment
            DB::transaction(function () use ($shipment, $dto) {
                $shipment->markAsDelivered(
                    deliveredAt: $dto->deliveredAt,
                    signature: $dto->signature,
                    notes: $dto->deliveryNotes,
                    photoUrl: $dto->photoUrl,
                );
            });

            // Refresh to get updated data
            $shipment->refresh();

            // Dispatch event for listeners
            event(new ShipmentDelivered($shipment, 'api'));

            Log::info('Shipment delivery updated via API', [
                'shipment_id' => $shipment->id,
                'magento_shipment_id' => $magentoShipmentId,
                'delivered_at' => $shipment->actual_delivery_at->toIso8601String(),
                'token_id' => $token?->id,
            ]);

            return ApiResponse::success(
                data: [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'order_number' => $shipment->order?->increment_id,
                    'delivered_at' => $shipment->actual_delivery_at->toIso8601String(),
                    'status' => $shipment->status,
                    'updated_at' => $shipment->updated_at->toIso8601String(),
                ],
                message: 'Shipment delivery information updated successfully',
            )->toResponse();

        } catch (\Throwable $e) {
            Log::error('Failed to update shipment delivery', [
                'magento_shipment_id' => $magentoShipmentId,
                'error' => $e->getMessage(),
                'token_id' => $token?->id,
            ]);

            return ApiResponse::error(
                errorCode: ApiErrorCode::INTERNAL_ERROR,
                message: 'Failed to update delivery information. Please try again.',
            )->toResponse();
        }
    }

    /**
     * Get the current access token from the request.
     */
    protected function getCurrentToken(Request $request): ?PersonalAccessToken
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return $user->currentAccessToken();
    }

    /**
     * Find a shipment by Magento shipment ID with tenant awareness.
     *
     * @throws \RuntimeException if token does not have tenant context
     */
    protected function findShipment(string $magentoShipmentId, ?PersonalAccessToken $token): ?Shipment
    {
        // Require tenant context - fail-safe approach
        if (! $token || ! $token->tenant_id) {
            throw new \RuntimeException(
                'API access requires tenant-scoped token. '.
                'Token must have tenant_id set for security isolation.'
            );
        }

        // Explicit tenant filtering for security
        return Shipment::withoutGlobalScope('tenant')
            ->where('tenant_id', $token->tenant_id)
            ->where('magento_shipment_id', $magentoShipmentId)
            ->with('order:id,increment_id')
            ->first();
    }
}

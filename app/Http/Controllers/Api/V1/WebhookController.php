<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Api\ApiResponse;
use App\Enums\ApiErrorCode;
use App\Events\ShipmentDelivered;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class WebhookController extends Controller
{
    /**
     * Receive shipment status updates from carriers.
     *
     * POST /api/v1/webhooks/shipment-status
     * 
     * Request body:
     * {
     *   "tracking_number": "1Z999AA10123456784",
     *   "carrier_code": "ups",
     *   "status": "delivered",
     *   "delivered_at": "2025-12-04T14:30:00Z",
     *   "signature": "John Doe",
     *   "delivery_notes": "Left at front door",
     *   "delivery_photo_url": "https://...",
     *   "estimated_delivery_at": "2025-12-05T18:00:00Z"
     * }
     */
    public function shipmentStatus(Request $request): JsonResponse
    {
        $token = $this->getCurrentToken($request);

        // Require tenant context
        if (! $token || ! $token->tenant_id) {
            return ApiResponse::error(
                errorCode: ApiErrorCode::UNAUTHORIZED,
                message: 'API access requires tenant-scoped token.',
            )->toResponse();
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'tracking_number' => 'required|string|max:255',
            'carrier_code' => 'required|string|max:50',
            'status' => 'required|string|in:pending,in_transit,out_for_delivery,delivered,exception,returned,canceled',
            'delivered_at' => 'nullable|date',
            'signature' => 'nullable|string|max:255',
            'delivery_notes' => 'nullable|string|max:1000',
            'delivery_photo_url' => 'nullable|url|max:500',
            'estimated_delivery_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors()->toArray())->toResponse();
        }

        $validated = $validator->validated();

        try {
            // Find shipment by tracking number with tenant awareness
            $shipment = Shipment::withoutGlobalScope('tenant')
                ->where('tenant_id', $token->tenant_id)
                ->where('tracking_number', $validated['tracking_number'])
                ->first();

            if (! $shipment) {
                return ApiResponse::error(
                    errorCode: ApiErrorCode::SHIPMENT_NOT_FOUND,
                    message: "No shipment found with tracking number: {$validated['tracking_number']}",
                )->toResponse();
            }

            // Verify carrier code matches if shipment already has one
            if ($shipment->carrier_code && $shipment->carrier_code !== $validated['carrier_code']) {
                Log::warning('Carrier code mismatch in webhook', [
                    'tracking_number' => $validated['tracking_number'],
                    'expected_carrier' => $validated['carrier_code'],
                    'actual_carrier' => $shipment->carrier_code,
                    'tenant_id' => $token->tenant_id,
                ]);
            }

            $previousStatus = $shipment->status;
            $wasDelivered = false;

            DB::transaction(function () use ($shipment, $validated, &$wasDelivered) {
                // Update status
                $shipment->status = $validated['status'];

                // Update carrier code if not set
                if (empty($shipment->carrier_code)) {
                    $shipment->carrier_code = $validated['carrier_code'];
                }

                // Update estimated delivery if provided
                if (isset($validated['estimated_delivery_at'])) {
                    $shipment->estimated_delivery_at = $validated['estimated_delivery_at'];
                }

                // If delivered, mark as delivered
                if ($validated['status'] === 'delivered' && isset($validated['delivered_at'])) {
                    $shipment->markAsDelivered(
                        deliveredAt: $validated['delivered_at'],
                        signature: $validated['signature'] ?? null,
                        notes: $validated['delivery_notes'] ?? null,
                        photoUrl: $validated['delivery_photo_url'] ?? null,
                    );
                    $wasDelivered = true;
                } else {
                    $shipment->save();
                }
            });

            // Dispatch event if delivered
            if ($wasDelivered) {
                event(new ShipmentDelivered($shipment, 'webhook'));
            }

            Log::info('Shipment status updated via webhook', [
                'shipment_id' => $shipment->id,
                'tracking_number' => $validated['tracking_number'],
                'previous_status' => $previousStatus,
                'new_status' => $validated['status'],
                'tenant_id' => $token->tenant_id,
                'token_id' => $token->id,
            ]);

            return ApiResponse::success(
                data: [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'status' => $shipment->status,
                    'previous_status' => $previousStatus,
                    'order_number' => $shipment->order?->increment_id,
                    'updated_at' => $shipment->updated_at->toIso8601String(),
                ],
                message: 'Shipment status updated successfully',
            )->toResponse();

        } catch (\Throwable $e) {
            Log::error('Failed to process shipment status webhook', [
                'tracking_number' => $validated['tracking_number'] ?? null,
                'tenant_id' => $token->tenant_id,
                'error' => $e->getMessage(),
                'token_id' => $token->id,
            ]);

            return ApiResponse::error(
                errorCode: ApiErrorCode::INTERNAL_ERROR,
                message: 'Failed to process shipment status update. Please try again.',
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
}

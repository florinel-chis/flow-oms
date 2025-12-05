<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Api\ApiResponse;
use App\Enums\ApiErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class OrderController extends Controller
{
    /**
     * Get orders list with optional filtering.
     *
     * GET /api/v1/orders
     * 
     * Query parameters:
     * - status: Filter by order status
     * - payment_status: Filter by payment status
     * - customer_email: Filter by customer email
     * - date_from: Filter orders from this date (ISO 8601)
     * - date_to: Filter orders until this date (ISO 8601)
     * - per_page: Results per page (default: 15, max: 100)
     * - page: Page number
     * - include: Relationships to include (items,shipments,invoices)
     */
    public function index(Request $request): JsonResponse
    {
        $token = $this->getCurrentToken($request);

        // Require tenant context
        if (! $token || ! $token->tenant_id) {
            return ApiResponse::error(
                errorCode: ApiErrorCode::UNAUTHORIZED,
                message: 'API access requires tenant-scoped token.',
            )->toResponse();
        }

        try {
            $query = Order::withoutGlobalScope('tenant')
                ->where('tenant_id', $token->tenant_id)
                ->with('magentoStore:id,name');

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->input('payment_status'));
            }

            if ($request->filled('customer_email')) {
                $query->where('customer_email', 'like', '%' . $request->input('customer_email') . '%');
            }

            if ($request->filled('date_from')) {
                $query->where('ordered_at', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->where('ordered_at', '<=', $request->input('date_to'));
            }

            // Handle includes
            $includes = $request->input('include', '');
            $allowedIncludes = ['items', 'shipments', 'invoices'];
            $requestedIncludes = array_filter(
                explode(',', $includes),
                fn($inc) => in_array($inc, $allowedIncludes)
            );

            if (!empty($requestedIncludes)) {
                $query->with($requestedIncludes);
            }

            // Pagination
            $perPage = min((int) $request->input('per_page', 15), 100);
            $orders = $query->latest('ordered_at')->paginate($perPage);

            Log::info('Orders list retrieved via API', [
                'tenant_id' => $token->tenant_id,
                'token_id' => $token->id,
                'count' => $orders->count(),
                'total' => $orders->total(),
                'filters' => $request->only(['status', 'payment_status', 'customer_email', 'date_from', 'date_to']),
            ]);

            return ApiResponse::success(
                data: [
                    'orders' => OrderResource::collection($orders->items()),
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                        'last_page' => $orders->lastPage(),
                        'from' => $orders->firstItem(),
                        'to' => $orders->lastItem(),
                    ],
                ],
            )->toResponse();

        } catch (\Throwable $e) {
            Log::error('Failed to retrieve orders via API', [
                'tenant_id' => $token->tenant_id,
                'error' => $e->getMessage(),
                'token_id' => $token->id,
            ]);

            return ApiResponse::error(
                errorCode: ApiErrorCode::INTERNAL_ERROR,
                message: 'Failed to retrieve orders. Please try again.',
            )->toResponse();
        }
    }

    /**
     * Get a single order by increment ID.
     *
     * GET /api/v1/orders/{increment_id}
     */
    public function show(Request $request, string $incrementId): JsonResponse
    {
        $token = $this->getCurrentToken($request);

        // Require tenant context
        if (! $token || ! $token->tenant_id) {
            return ApiResponse::error(
                errorCode: ApiErrorCode::UNAUTHORIZED,
                message: 'API access requires tenant-scoped token.',
            )->toResponse();
        }

        try {
            $order = Order::withoutGlobalScope('tenant')
                ->where('tenant_id', $token->tenant_id)
                ->where('increment_id', $incrementId)
                ->with(['magentoStore:id,name', 'items', 'shipments', 'invoices'])
                ->first();

            if (! $order) {
                return ApiResponse::error(
                    errorCode: ApiErrorCode::ORDER_NOT_FOUND,
                    message: "No order found with increment ID: {$incrementId}",
                )->toResponse();
            }

            Log::info('Order retrieved via API', [
                'order_id' => $order->id,
                'increment_id' => $incrementId,
                'tenant_id' => $token->tenant_id,
                'token_id' => $token->id,
            ]);

            return ApiResponse::success(
                data: [
                    'order' => new OrderResource($order),
                ],
            )->toResponse();

        } catch (\Throwable $e) {
            Log::error('Failed to retrieve order via API', [
                'increment_id' => $incrementId,
                'tenant_id' => $token->tenant_id,
                'error' => $e->getMessage(),
                'token_id' => $token->id,
            ]);

            return ApiResponse::error(
                errorCode: ApiErrorCode::INTERNAL_ERROR,
                message: 'Failed to retrieve order. Please try again.',
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

<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Api\ApiResponse;
use App\Enums\ApiErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class InvoiceController extends Controller
{
    /**
     * Get invoices list with optional filtering.
     *
     * GET /api/v1/invoices
     * 
     * Query parameters:
     * - state: Filter by invoice state (paid, open, canceled)
     * - order_id: Filter by order ID
     * - customer_email: Filter by customer email
     * - date_from: Filter invoices from this date (ISO 8601)
     * - date_to: Filter invoices until this date (ISO 8601)
     * - per_page: Results per page (default: 15, max: 100)
     * - page: Page number
     * - include: Relationships to include (items)
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
            $query = Invoice::withoutGlobalScope('tenant')
                ->where('tenant_id', $token->tenant_id)
                ->with(['magentoStore:id,name', 'order:id,increment_id']);

            // Apply filters
            if ($request->filled('state')) {
                $query->where('state', $request->input('state'));
            }

            if ($request->filled('order_id')) {
                $query->where('order_id', $request->input('order_id'));
            }

            if ($request->filled('customer_email')) {
                $query->where('customer_email', 'like', '%' . $request->input('customer_email') . '%');
            }

            if ($request->filled('date_from')) {
                $query->where('invoiced_at', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->where('invoiced_at', '<=', $request->input('date_to'));
            }

            // Handle includes
            $includes = $request->input('include', '');
            $allowedIncludes = ['items'];
            $requestedIncludes = array_filter(
                explode(',', $includes),
                fn($inc) => in_array($inc, $allowedIncludes)
            );

            if (!empty($requestedIncludes)) {
                $query->with($requestedIncludes);
            }

            // Pagination
            $perPage = min((int) $request->input('per_page', 15), 100);
            $invoices = $query->latest('invoiced_at')->paginate($perPage);

            Log::info('Invoices list retrieved via API', [
                'tenant_id' => $token->tenant_id,
                'token_id' => $token->id,
                'count' => $invoices->count(),
                'total' => $invoices->total(),
                'filters' => $request->only(['state', 'order_id', 'customer_email', 'date_from', 'date_to']),
            ]);

            return ApiResponse::success(
                data: [
                    'invoices' => InvoiceResource::collection($invoices->items()),
                    'pagination' => [
                        'current_page' => $invoices->currentPage(),
                        'per_page' => $invoices->perPage(),
                        'total' => $invoices->total(),
                        'last_page' => $invoices->lastPage(),
                        'from' => $invoices->firstItem(),
                        'to' => $invoices->lastItem(),
                    ],
                ],
            )->toResponse();

        } catch (\Throwable $e) {
            Log::error('Failed to retrieve invoices via API', [
                'tenant_id' => $token->tenant_id,
                'error' => $e->getMessage(),
                'token_id' => $token->id,
            ]);

            return ApiResponse::error(
                errorCode: ApiErrorCode::INTERNAL_ERROR,
                message: 'Failed to retrieve invoices. Please try again.',
            )->toResponse();
        }
    }

    /**
     * Get a single invoice by increment ID.
     *
     * GET /api/v1/invoices/{increment_id}
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
            $invoice = Invoice::withoutGlobalScope('tenant')
                ->where('tenant_id', $token->tenant_id)
                ->where('increment_id', $incrementId)
                ->with(['magentoStore:id,name', 'order:id,increment_id', 'items'])
                ->first();

            if (! $invoice) {
                return ApiResponse::error(
                    errorCode: ApiErrorCode::INVOICE_NOT_FOUND,
                    message: "No invoice found with increment ID: {$incrementId}",
                )->toResponse();
            }

            Log::info('Invoice retrieved via API', [
                'invoice_id' => $invoice->id,
                'increment_id' => $incrementId,
                'tenant_id' => $token->tenant_id,
                'token_id' => $token->id,
            ]);

            return ApiResponse::success(
                data: [
                    'invoice' => new InvoiceResource($invoice),
                ],
            )->toResponse();

        } catch (\Throwable $e) {
            Log::error('Failed to retrieve invoice via API', [
                'increment_id' => $incrementId,
                'tenant_id' => $token->tenant_id,
                'error' => $e->getMessage(),
                'token_id' => $token->id,
            ]);

            return ApiResponse::error(
                errorCode: ApiErrorCode::INTERNAL_ERROR,
                message: 'Failed to retrieve invoice. Please try again.',
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

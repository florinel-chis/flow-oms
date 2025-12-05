<?php

use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ShipmentDeliveryController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// API Version 1
Route::prefix('v1')->group(function () {

    // Protected routes requiring Sanctum authentication
    Route::middleware([
        'auth:sanctum',
        'throttle:60,1',  // Rate limiting: 60 requests per minute per user
        \App\Http\Middleware\LogApiRequest::class,
    ])->group(function () {

        // Orders
        Route::get('orders', [OrderController::class, 'index'])
            ->name('api.v1.orders.index');
        Route::get('orders/{increment_id}', [OrderController::class, 'show'])
            ->name('api.v1.orders.show');

        // Invoices
        Route::get('invoices', [InvoiceController::class, 'index'])
            ->name('api.v1.invoices.index');
        Route::get('invoices/{increment_id}', [InvoiceController::class, 'show'])
            ->name('api.v1.invoices.show');

        // Webhooks
        Route::post('webhooks/shipment-status', [WebhookController::class, 'shipmentStatus'])
            ->name('api.v1.webhooks.shipment-status');

        // Shipment delivery updates
        Route::patch(
            'shipments/{magento_shipment_id}/delivery',
            [ShipmentDeliveryController::class, 'updateDelivery']
        )->name('api.v1.shipments.update-delivery');

    });

});

// Health check endpoint (no auth required, but rate-limited)
Route::middleware('throttle:300,1')->get('health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('api.health');

// Debug authentication endpoint (NO AUTH - for debugging)
Route::get('debug/token', function (Request $request) {
    $bearerToken = $request->bearerToken();

    if (!$bearerToken) {
        return response()->json([
            'error' => 'No bearer token provided',
            'all_headers' => $request->headers->all(),
            'authorization_header' => $request->header('Authorization'),
        ]);
    }

    // Parse token manually
    $tokenParts = explode('|', $bearerToken);
    if (count($tokenParts) !== 2) {
        return response()->json(['error' => 'Invalid token format']);
    }

    [$id, $token] = $tokenParts;
    $tokenHash = hash('sha256', $token);

    // Find token in database
    $dbToken = \Laravel\Sanctum\PersonalAccessToken::find($id);

    if (!$dbToken) {
        return response()->json(['error' => 'Token not found in database', 'token_id' => $id]);
    }

    // Check hash
    $hashMatches = hash_equals($dbToken->token, $tokenHash);

    // Try to get user
    $user = $dbToken->tokenable;

    return response()->json([
        'token_found' => true,
        'token_id' => $dbToken->id,
        'hash_matches' => $hashMatches,
        'tokenable_type' => $dbToken->tokenable_type,
        'tokenable_id' => $dbToken->tokenable_id,
        'tenant_id' => $dbToken->tenant_id,
        'user_found' => $user ? true : false,
        'user_email' => $user ? $user->email : null,
        'guards_configured' => array_keys(config('auth.guards')),
        'default_guard' => config('auth.defaults.guard'),
    ]);
})->name('api.debug.token');

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Debug endpoint to test authentication
Route::middleware(['auth:sanctum'])->get('/debug/auth', function (Request $request) {
    $user = $request->user();
    $sanctumUser = $request->user('sanctum');
    $apiUser = $request->user('api');

    return response()->json([
        'authenticated' => auth()->check(),
        'user_default' => $user ? ['id' => $user->id, 'email' => $user->email] : null,
        'user_sanctum' => $sanctumUser ? ['id' => $sanctumUser->id, 'email' => $sanctumUser->email] : null,
        'user_api' => $apiUser ? ['id' => $apiUser->id, 'email' => $apiUser->email] : null,
        'guards' => [
            'default' => config('auth.defaults.guard'),
            'available' => array_keys(config('auth.guards')),
        ],
        'bearer_token_present' => $request->bearerToken() ? 'yes' : 'no',
    ]);
});

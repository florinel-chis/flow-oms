<?php

use App\Models\MagentoOrderSync;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// JSON Viewer for MagentoOrderSync raw data
Route::get('/magento-order-sync/{id}/json', function ($id) {
    $sync = MagentoOrderSync::findOrFail($id);

    return view('magento-order-sync-json', [
        'sync' => $sync,
        'json' => json_encode($sync->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);
})->name('magento-order-sync.json');

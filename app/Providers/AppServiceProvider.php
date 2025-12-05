<?php

namespace App\Providers;

use App\Contracts\ExternalNotificationClientInterface;
use App\Contracts\Magento\OrderSyncServiceInterface;
use App\Contracts\Magento\Parsers\InvoiceParserInterface;
use App\Contracts\Magento\Parsers\ShipmentParserInterface;
use App\Events\SlaBreached;
use App\Events\SlaBreachImminent;
use App\Listeners\SendSlaBreachNotification;
use App\Models\Order;
use App\Observers\OrderObserver;
use App\Services\ExternalNotificationClient;
use App\Services\Magento\OrderSyncService;
use App\Services\Magento\Parsers\InvoiceParser;
use App\Services\Magento\Parsers\ShipmentParser;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Service bindings for dependency injection.
     */
    public array $bindings = [
        OrderSyncServiceInterface::class => OrderSyncService::class,
        InvoiceParserInterface::class => InvoiceParser::class,
        ShipmentParserInterface::class => ShipmentParser::class,
        ExternalNotificationClientInterface::class => ExternalNotificationClient::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // MagentoApiClient is not bound globally as it requires a MagentoStore instance
        // Create via new MagentoApiClient($store) when needed
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Order observer for SLA deadline calculation
        Order::observe(OrderObserver::class);

        // Register SLA breach event listeners
        Event::listen(SlaBreachImminent::class, [SendSlaBreachNotification::class, 'handleImminent']);
        Event::listen(SlaBreached::class, [SendSlaBreachNotification::class, 'handleBreached']);
    }
}

<?php

namespace App\Console\Commands\Magento;

use App\Models\MagentoProduct;
use App\Models\MagentoProductStockItem;
use App\Models\MagentoStore;
use App\Models\Tenant;
use App\Services\Magento\MagentoApiClient;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class SyncProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magento:sync-products {tenant? : The ID of the tenant to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products from Magento for all or a specific tenant';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Magento product sync.');

        $tenants = $this->getTenants();

        $tenants->each(fn (Tenant $tenant) => $this->syncTenant($tenant));

        $this->info('Product sync completed successfully.');

        return self::SUCCESS;
    }

    protected function syncTenant(Tenant $tenant): void
    {
        $this->info("Syncing products for tenant: {$tenant->name}");
        $tenant->magentoStores()->each(fn (MagentoStore $store) => $this->syncStore($store));
    }

    protected function syncStore(MagentoStore $store): void
    {
        $this->info("Syncing products for store: {$store->name} ({$store->url})");
        $apiClient = new MagentoApiClient($store);

        $progressBar = $this->output->createProgressBar();
        $count = 0;

        $user = $store->tenant->users()->first();

        if (! $user) {
            $this->error("No users found for tenant: {$store->tenant->name}");

            return;
        }

        Auth::login($user);

        Filament::setTenant($store->tenant);

        foreach ($apiClient->getAllProducts() as $productData) {
            $product = MagentoProduct::updateOrCreate(
                [
                    'tenant_id' => $store->tenant_id,
                    'sku' => $productData['sku'],
                ],
                [
                    'name' => $productData['name'],
                    'product_type' => $productData['type_id'],
                    'raw_data' => $productData,
                ]
            );

            // Sync stock information from extension_attributes
            $this->syncStockItem($product, $productData, $apiClient);

            $count++;
            $progressBar->advance();
        }

        Filament::setTenant(null);

        Auth::logout();

        $progressBar->finish();
        $this->newLine();
        $this->info("Synced {$count} products for store: {$store->name}");
    }

    protected function getTenants(): Collection
    {
        $tenantId = $this->argument('tenant');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                $this->error("Tenant with ID {$tenantId} not found.");

                return new Collection;
            }

            return new Collection([$tenant]);
        }

        return Tenant::with('magentoStores')->get();
    }

    protected function syncStockItem(MagentoProduct $product, array $productData, MagentoApiClient $apiClient): void
    {
        // Stock information is in extension_attributes.stock_item
        $stockData = $productData['extension_attributes']['stock_item'] ?? null;

        // If not in product response, fetch separately
        if (! $stockData) {
            $stockData = $apiClient->getStockItem($product->sku);
        }

        if (! $stockData) {
            return;
        }

        MagentoProductStockItem::updateOrCreate(
            [
                'magento_product_id' => $product->id,
                'tenant_id' => $product->tenant_id,
            ],
            [
                'magento_item_id' => $stockData['item_id'] ?? null,
                'qty' => $stockData['qty'] ?? 0,
                'is_in_stock' => $stockData['is_in_stock'] ?? false,
                'manage_stock' => $stockData['manage_stock'] ?? true,
                'use_config_manage_stock' => $stockData['use_config_manage_stock'] ?? true,
                'backorders' => $stockData['backorders'] ?? 0,
                'use_config_backorders' => $stockData['use_config_backorders'] ?? true,
                'min_qty' => $stockData['min_qty'] ?? 0,
                'min_sale_qty' => $stockData['min_sale_qty'] ?? 1,
                'max_sale_qty' => $stockData['max_sale_qty'] ?? 10000,
                'notify_stock_qty' => $stockData['notify_stock_qty'] ?? null,
                'enable_qty_increments' => $stockData['enable_qty_increments'] ?? false,
                'qty_increments' => $stockData['qty_increments'] ?? 0,
                'raw_data' => $stockData,
            ]
        );
    }
}

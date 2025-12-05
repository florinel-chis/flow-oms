<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('magento_product_stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magento_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Stock item ID from Magento
            $table->unsignedBigInteger('magento_item_id')->nullable();

            // Quantity information
            $table->decimal('qty', 12, 4)->default(0);
            $table->boolean('is_in_stock')->default(false);
            $table->boolean('manage_stock')->default(true);
            $table->boolean('use_config_manage_stock')->default(true);

            // Backorders
            $table->integer('backorders')->default(0); // 0=No, 1=Allow Qty Below 0, 2=Allow Qty Below 0 and Notify
            $table->boolean('use_config_backorders')->default(true);

            // Min/Max quantities
            $table->decimal('min_qty', 12, 4)->default(0);
            $table->decimal('min_sale_qty', 12, 4)->default(1);
            $table->decimal('max_sale_qty', 12, 4)->default(10000);

            // Notify stock quantity
            $table->decimal('notify_stock_qty', 12, 4)->nullable();

            // Enable qty increments
            $table->boolean('enable_qty_increments')->default(false);
            $table->decimal('qty_increments', 12, 4)->default(0);

            // Raw data from Magento
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'is_in_stock']);
            $table->index(['magento_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magento_product_stock_items');
    }
};

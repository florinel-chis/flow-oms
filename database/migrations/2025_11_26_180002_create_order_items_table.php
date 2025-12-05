<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('magento_products')->nullOnDelete();

            // Product identification
            $table->string('sku');
            $table->string('name');

            // Quantities
            $table->decimal('qty_ordered', 10, 2);
            $table->decimal('qty_shipped', 10, 2)->default(0);
            $table->decimal('qty_canceled', 10, 2)->default(0);

            // Pricing
            $table->decimal('price', 12, 4);
            $table->decimal('row_total', 12, 4);
            $table->decimal('tax_amount', 12, 4)->default(0);
            $table->decimal('discount_amount', 12, 4)->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['order_id', 'sku']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

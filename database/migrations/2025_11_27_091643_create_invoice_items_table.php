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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();

            // Magento item identifier
            $table->unsignedBigInteger('magento_item_id');

            // Product information
            $table->string('product_name');
            $table->string('sku');

            // Quantity invoiced
            $table->integer('qty');

            // Pricing
            $table->decimal('price', 12, 4);
            $table->decimal('row_total', 12, 4);
            $table->decimal('tax_amount', 12, 4)->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'invoice_id']);
            $table->index(['tenant_id', 'sku']);
            $table->index(['invoice_id', 'sku']);
            $table->index('order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};

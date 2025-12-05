<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('magento_store_id')->constrained()->cascadeOnDelete();

            // Magento identifiers
            $table->unsignedBigInteger('magento_order_id');
            $table->string('increment_id')->index();

            // Order status
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending');

            // Customer information
            $table->string('customer_name');
            $table->string('customer_email');

            // Financial information
            $table->decimal('grand_total', 12, 4);
            $table->decimal('subtotal', 12, 4);
            $table->decimal('tax_amount', 12, 4)->default(0);
            $table->decimal('shipping_amount', 12, 4)->default(0);
            $table->decimal('discount_amount', 12, 4)->default(0);
            $table->string('currency_code', 3)->default('USD');

            // Payment and shipping
            $table->string('payment_method')->nullable();
            $table->string('shipping_method')->nullable();

            // Timestamps
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->unique(['tenant_id', 'magento_order_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'payment_status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'ordered_at']);
            $table->index(['magento_store_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('magento_store_id')->constrained()->cascadeOnDelete();

            // Magento identifiers
            $table->unsignedBigInteger('magento_invoice_id');
            $table->string('increment_id')->index();

            // Invoice state (paid, open, canceled)
            $table->string('state')->default('open');

            // Financial information
            $table->decimal('grand_total', 12, 4);
            $table->decimal('subtotal', 12, 4);
            $table->decimal('tax_amount', 12, 4)->default(0);
            $table->decimal('shipping_amount', 12, 4)->default(0);
            $table->decimal('discount_amount', 12, 4)->default(0);

            // Base currency amounts (store's base currency)
            $table->decimal('base_grand_total', 12, 4);
            $table->decimal('base_subtotal', 12, 4);

            // Billing information
            $table->unsignedBigInteger('billing_address_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_email');

            // Timestamps
            $table->timestamp('invoiced_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->unique(['tenant_id', 'magento_invoice_id']);
            $table->index(['tenant_id', 'order_id']);
            $table->index(['tenant_id', 'magento_store_id']);
            $table->index(['tenant_id', 'state']);
            $table->index(['tenant_id', 'invoiced_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

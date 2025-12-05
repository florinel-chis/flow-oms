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
        Schema::create('magento_order_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('magento_store_id')->constrained()->cascadeOnDelete();

            // Magento order identifiers
            $table->unsignedBigInteger('entity_id')->comment('Magento order ID');
            $table->string('increment_id')->comment('Human-readable order number');

            // Order status and flags
            $table->string('order_status')->index()->comment('Magento order status');
            $table->boolean('has_invoice')->default(false)->index()->comment('Order has invoices');
            $table->boolean('has_shipment')->default(false)->index()->comment('Order has shipments');

            // Raw Magento API response
            $table->json('raw_data')->comment('Full Magento order JSON response');

            // Sync metadata
            $table->string('sync_batch_id')->nullable()->index()->comment('Batch ID for this sync run');
            $table->timestamp('synced_at')->useCurrent()->index()->comment('When this record was synced');

            $table->timestamps();

            // Indexes for performance
            $table->unique(['tenant_id', 'magento_store_id', 'entity_id'], 'unique_tenant_store_order');
            $table->index(['tenant_id', 'magento_store_id', 'order_status'], 'idx_tenant_store_status');
            $table->index(['tenant_id', 'synced_at'], 'idx_tenant_synced');
            $table->index(['increment_id'], 'idx_increment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magento_order_syncs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // Magento identifier
            $table->unsignedBigInteger('magento_shipment_id')->nullable();

            // Tracking information
            $table->string('tracking_number')->nullable()->index();
            $table->string('carrier_code')->nullable();
            $table->string('carrier_title')->nullable();

            // Shipment status
            $table->string('status')->default('pending');

            // Delivery tracking
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('actual_delivery_at')->nullable();
            $table->timestamp('last_tracking_update_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['order_id', 'created_at']);
            $table->index(['tracking_number', 'carrier_code']);
            $table->index('estimated_delivery_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};

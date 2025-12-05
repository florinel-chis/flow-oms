<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds unique constraint to prevent duplicate shipments from Magento.
     * This improves upsert performance and ensures data integrity.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->unique(['tenant_id', 'magento_shipment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'magento_shipment_id']);
        });
    }
};

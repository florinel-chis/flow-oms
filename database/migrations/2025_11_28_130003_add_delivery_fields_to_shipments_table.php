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
        Schema::table('shipments', function (Blueprint $table) {
            // Delivery confirmation fields
            $table->string('delivery_signature')->nullable()->after('actual_delivery_at');
            $table->text('delivery_notes')->nullable()->after('delivery_signature');
            $table->string('delivery_photo_url')->nullable()->after('delivery_notes');

            // Index for querying delivery-related data
            $table->index(['tenant_id', 'actual_delivery_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'actual_delivery_at']);
            $table->dropColumn([
                'delivery_signature',
                'delivery_notes',
                'delivery_photo_url',
            ]);
        });
    }
};

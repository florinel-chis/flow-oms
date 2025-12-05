<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds tenant_id column to order_items for direct tenant scoping.
     * This improves query performance by avoiding subquery lookups through orders table.
     */
    public function up(): void
    {
        // Step 1: Add nullable tenant_id column
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->cascadeOnDelete();
        });

        // Step 2: Backfill tenant_id from orders table
        DB::statement('
            UPDATE order_items
            SET tenant_id = (
                SELECT tenant_id FROM orders WHERE orders.id = order_items.order_id
            )
        ');

        // Step 3: Make tenant_id NOT NULL after backfill
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable(false)->change();
        });

        // Step 4: Add composite indexes for performance
        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['tenant_id', 'order_id']);
            $table->index(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'product_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'order_id']);
            $table->dropIndex(['tenant_id', 'sku']);
            $table->dropIndex(['tenant_id', 'product_type']);
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};

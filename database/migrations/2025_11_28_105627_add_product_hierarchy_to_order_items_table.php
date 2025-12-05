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
        Schema::table('order_items', function (Blueprint $table) {
            // Add Magento item ID for parent-child linking
            $table->unsignedBigInteger('magento_item_id')->nullable()->after('order_id');

            // Add product type (simple, configurable, bundle, etc.)
            $table->string('product_type', 50)->nullable()->after('product_id');

            // Add parent-child relationship
            $table->foreignId('parent_item_id')->nullable()->after('product_type')
                ->constrained('order_items')
                ->cascadeOnDelete();

            // Index for performance
            $table->index(['order_id', 'parent_item_id']);
            $table->index('magento_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['parent_item_id']);
            $table->dropIndex(['order_id', 'parent_item_id']);
            $table->dropIndex(['magento_item_id']);
            $table->dropColumn(['magento_item_id', 'product_type', 'parent_item_id']);
        });
    }
};

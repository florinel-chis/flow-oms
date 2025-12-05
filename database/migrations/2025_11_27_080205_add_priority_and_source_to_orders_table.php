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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('priority')->default('normal')->after('payment_status');
            $table->string('source')->nullable()->after('priority');

            // Add indexes for filtering performance
            $table->index(['tenant_id', 'priority']);
            $table->index(['tenant_id', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'priority']);
            $table->dropIndex(['tenant_id', 'source']);
            $table->dropColumn(['priority', 'source']);
        });
    }
};

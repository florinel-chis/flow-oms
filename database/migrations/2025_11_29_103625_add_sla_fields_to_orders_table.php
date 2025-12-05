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
            $table->timestamp('sla_deadline')->nullable()->after('ordered_at');
            $table->timestamp('shipped_at')->nullable()->after('sla_deadline');
            $table->boolean('sla_breached')->default(false)->after('shipped_at');
            $table->index(['tenant_id', 'sla_deadline']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'sla_deadline']);
            $table->dropColumn(['sla_deadline', 'shipped_at', 'sla_breached']);
        });
    }
};

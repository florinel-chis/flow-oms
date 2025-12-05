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
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Add tenant_id for tenant-specific tokens (nullable for multi-tenant carriers)
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();

            // Add metadata for audit purposes
            $table->string('created_by_name')->nullable()->after('abilities');
            $table->string('created_by_email')->nullable()->after('created_by_name');
            $table->string('description')->nullable()->after('created_by_email');

            // Indexes for querying
            $table->index('tenant_id');
            $table->index(['tenant_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn([
                'tenant_id',
                'created_by_name',
                'created_by_email',
                'description',
            ]);
        });
    }
};

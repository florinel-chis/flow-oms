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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('token_id')->nullable();
            $table->string('token_name')->nullable();

            // Request details
            $table->string('method', 10);
            $table->string('endpoint');
            $table->string('ip_address', 45)->nullable(); // IPv6 compatible
            $table->string('user_agent')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();

            // Response details
            $table->unsignedSmallInteger('response_status');
            $table->json('response_body')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();

            // Result categorization
            $table->boolean('is_success');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();

            // Resource tracking
            $table->string('resource_type')->nullable(); // e.g., 'shipment'
            $table->string('resource_id')->nullable(); // e.g., tracking number

            $table->timestamps();

            // Indexes for querying and analytics
            $table->index(['tenant_id', 'created_at']);
            $table->index(['token_id', 'created_at']);
            $table->index(['endpoint', 'created_at']);
            $table->index(['is_success', 'created_at']);
            $table->index('ip_address');
            $table->index('response_status');
            $table->index('error_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates table for tracking unpaid order warning and cancellation notifications.
     * Part of the Automated Unpaid Order Management System.
     */
    public function up(): void
    {
        Schema::create('unpaid_order_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('notification_type', ['warning', 'cancellation'])->comment('Type of notification sent');
            $table->timestamp('triggered_at')->comment('When the notification was triggered');
            $table->decimal('hours_unpaid', 8, 2)->comment('Hours order was unpaid when notification triggered');
            $table->string('endpoint_url', 500)->comment('External endpoint where notification was sent');
            $table->json('payload')->comment('Complete JSON payload sent to external endpoint');
            $table->integer('response_status')->nullable()->comment('HTTP status code from external endpoint');
            $table->text('response_body')->nullable()->comment('Response body from external endpoint');
            $table->boolean('sent_successfully')->default(false)->comment('Whether notification was sent successfully');
            $table->integer('retry_count')->default(0)->comment('Number of retry attempts made');
            $table->timestamp('last_retry_at')->nullable()->comment('Timestamp of last retry attempt');
            $table->text('error_message')->nullable()->comment('Error message if notification failed');
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'order_id'], 'idx_tenant_order');
            $table->index('notification_type', 'idx_notification_type');
            $table->index('triggered_at', 'idx_triggered_at');
            $table->index('sent_successfully', 'idx_sent_successfully');
            $table->index(['tenant_id', 'notification_type', 'sent_successfully'], 'idx_tenant_type_success');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unpaid_order_notifications');
    }
};

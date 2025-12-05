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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('group')->index(); // e.g., 'ready_to_ship', 'sla', 'notifications'
            $table->string('key')->index(); // e.g., 'payment_statuses', 'order_statuses'
            $table->text('value')->nullable(); // JSON or text value
            $table->string('type')->default('string'); // string, json, boolean, integer
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'group', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

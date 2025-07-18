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
        Schema::create('completion_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('item_id'); // WooCommerce order/delivery ID
            $table->enum('item_type', ['delivery', 'collection']);
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->timestamp('completed_at');
            $table->string('completed_by')->nullable(); // Admin user who marked it complete
            $table->text('notes')->nullable(); // Optional completion notes
            $table->timestamps();
            
            // Ensure unique completion per item
            $table->unique(['item_id', 'item_type']);
            
            // Index for faster lookups
            $table->index(['item_type', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('completion_tracking');
    }
};

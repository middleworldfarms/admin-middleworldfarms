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
        Schema::create('delivery_completions', function (Blueprint $table) {
            $table->id();
            $table->string('external_id'); // The delivery/collection ID from WordPress/WooCommerce
            $table->enum('type', ['delivery', 'collection']); // Type of completion
            $table->string('customer_name')->nullable(); // For reference
            $table->string('customer_email')->nullable(); // For reference
            $table->timestamp('completed_at'); // When it was marked complete
            $table->string('completed_by')->nullable(); // Staff member (if we have auth)
            $table->text('notes')->nullable(); // Optional completion notes
            $table->timestamps();
            
            // Ensure one completion per external_id and type combination
            $table->unique(['external_id', 'type']);
            $table->index('completed_at'); // For date queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_completions');
    }
};

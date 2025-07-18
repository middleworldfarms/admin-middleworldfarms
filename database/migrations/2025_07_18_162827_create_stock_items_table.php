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
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Product name
            $table->string('slug')->unique(); // URL-friendly identifier
            $table->string('sku')->nullable()->unique(); // Stock keeping unit
            $table->string('crop_type')->nullable(); // Related to FarmOS plant type
            $table->decimal('current_stock', 10, 3)->default(0); // Current stock level
            $table->decimal('reserved_stock', 10, 3)->default(0); // Stock reserved for orders
            $table->decimal('available_stock', 10, 3)->default(0); // Available = current - reserved
            $table->string('units'); // Units (kg, lbs, pieces, etc.)
            $table->decimal('unit_price', 8, 2)->nullable(); // Price per unit
            $table->decimal('minimum_stock', 10, 3)->default(0); // Low stock alert threshold
            $table->string('storage_location')->nullable(); // Where it's stored
            $table->date('last_harvest_date')->nullable(); // Most recent harvest
            $table->boolean('is_active')->default(true); // Whether item is active
            $table->boolean('track_stock')->default(true); // Whether to track stock levels
            $table->text('description')->nullable(); // Product description
            $table->json('metadata')->nullable(); // Additional data (seasonality, etc.)
            $table->timestamps();
            
            $table->index(['current_stock']);
            $table->index(['available_stock']);
            $table->index(['is_active']);
            $table->index(['crop_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};

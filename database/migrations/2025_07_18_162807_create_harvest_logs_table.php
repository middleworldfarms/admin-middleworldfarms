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
        Schema::create('harvest_logs', function (Blueprint $table) {
            $table->id();
            $table->string('farmos_id')->unique(); // FarmOS log ID
            $table->string('farmos_asset_id'); // FarmOS plant asset ID
            $table->string('crop_name'); // Name of the crop/variety
            $table->string('crop_type')->nullable(); // Plant type (tomatoes, lettuce, etc.)
            $table->decimal('quantity', 10, 3); // Harvested quantity
            $table->string('units'); // Units (kg, lbs, pieces, etc.)
            $table->string('measure')->default('weight'); // Measure type (weight, count, volume)
            $table->timestamp('harvest_date'); // When it was harvested
            $table->string('location')->nullable(); // Field/greenhouse location
            $table->text('notes')->nullable(); // Additional notes from FarmOS
            $table->string('status')->default('active'); // Status of the harvest
            $table->boolean('synced_to_stock')->default(false); // Whether it's been added to stock
            $table->json('farmos_data')->nullable(); // Full FarmOS log data
            $table->timestamps();
            
            $table->index(['harvest_date']);
            $table->index(['crop_name']);
            $table->index(['synced_to_stock']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harvest_logs');
    }
};

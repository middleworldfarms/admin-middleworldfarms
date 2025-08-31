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
        Schema::create('plant_varieties', function (Blueprint $table) {
            $table->id();
            
            // FarmOS identifiers
            $table->string('farmos_id')->unique(); // FarmOS UUID
            $table->unsignedInteger('farmos_tid')->nullable(); // FarmOS internal taxonomy ID
            
            // Basic information
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('scientific_name')->nullable();
            
            // Taxonomy relationships
            $table->string('crop_family')->nullable();
            $table->string('plant_type')->nullable(); // Parent plant type
            $table->string('plant_type_id')->nullable(); // Parent plant type FarmOS ID
            
            // Growing parameters
            $table->integer('maturity_days')->nullable(); // Days to maturity
            $table->integer('transplant_days')->nullable(); // Days to transplant
            $table->integer('harvest_days')->nullable(); // Days to harvest
            $table->decimal('min_temperature', 5, 2)->nullable(); // Minimum temperature °C
            $table->decimal('max_temperature', 5, 2)->nullable(); // Maximum temperature °C
            $table->decimal('optimal_temperature', 5, 2)->nullable(); // Optimal temperature °C
            
            // Season information
            $table->string('season')->nullable(); // cool, warm, all-season
            $table->string('frost_tolerance')->nullable(); // frost-sensitive, frost-tolerant, etc.
            
            // Additional metadata
            $table->json('companions')->nullable(); // Companion plants
            $table->json('external_uris')->nullable(); // External links
            $table->json('farmos_data')->nullable(); // Complete FarmOS JSON response
            
            // Status and sync tracking
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status')->default('pending'); // pending, synced, failed
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['plant_type']);
            $table->index(['crop_family']);
            $table->index(['season']);
            $table->index(['is_active']);
            $table->index(['last_synced_at']);
            $table->index(['name']); // For search
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plant_varieties');
    }
};

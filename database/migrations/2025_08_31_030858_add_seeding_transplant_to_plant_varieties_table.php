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
        Schema::table('plant_varieties', function (Blueprint $table) {
            // Indoor seeding dates
            $table->date('indoor_seed_start')->nullable(); // Earliest indoor seeding date (MM-DD)
            $table->date('indoor_seed_end')->nullable(); // Latest indoor seeding date (MM-DD)
            
            // Outdoor seeding dates
            $table->date('outdoor_seed_start')->nullable(); // Earliest direct seeding date (MM-DD)
            $table->date('outdoor_seed_end')->nullable(); // Latest direct seeding date (MM-DD)
            
            // Transplant timing
            $table->date('transplant_start')->nullable(); // Earliest transplant date (MM-DD)
            $table->date('transplant_end')->nullable(); // Latest transplant date (MM-DD)
            $table->integer('transplant_window_days')->nullable(); // Days transplant can be held
            
            // Germination requirements
            $table->integer('germination_days_min')->nullable(); // Minimum days to germinate
            $table->integer('germination_days_max')->nullable(); // Maximum days to germinate
            $table->decimal('germination_temp_min', 5, 2)->nullable(); // Minimum soil temp for germination (°F)
            $table->decimal('germination_temp_max', 5, 2)->nullable(); // Maximum soil temp for germination (°F)
            $table->decimal('germination_temp_optimal', 5, 2)->nullable(); // Optimal soil temp for germination (°F)
            
            // Planting specifications
            $table->decimal('planting_depth_inches', 4, 2)->nullable(); // Planting depth in inches
            $table->decimal('seed_spacing_inches', 5, 2)->nullable(); // Spacing between seeds in inches
            $table->decimal('row_spacing_inches', 5, 2)->nullable(); // Spacing between rows in inches
            $table->integer('seeds_per_hole')->nullable(); // Number of seeds per planting hole
            
            // Seed starting requirements
            $table->boolean('requires_light_for_germination')->default(false); // Does seed need light to germinate?
            $table->text('seed_starting_notes')->nullable(); // Special seed starting instructions
            $table->string('seed_type')->nullable(); // pelleted, coated, raw, etc.
            
            // Transplant requirements
            $table->decimal('transplant_soil_temp_min', 5, 2)->nullable(); // Minimum soil temp for transplanting (°F)
            $table->decimal('transplant_soil_temp_max', 5, 2)->nullable(); // Maximum soil temp for transplanting (°F)
            $table->text('transplant_notes')->nullable(); // Special transplanting instructions
            
            // Hardening off period
            $table->integer('hardening_off_days')->nullable(); // Days needed to harden off seedlings
            $table->text('hardening_off_notes')->nullable(); // Hardening off instructions
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plant_varieties', function (Blueprint $table) {
            $table->dropColumn([
                'indoor_seed_start',
                'indoor_seed_end',
                'outdoor_seed_start',
                'outdoor_seed_end',
                'transplant_start',
                'transplant_end',
                'transplant_window_days',
                'germination_days_min',
                'germination_days_max',
                'germination_temp_min',
                'germination_temp_max',
                'germination_temp_optimal',
                'planting_depth_inches',
                'seed_spacing_inches',
                'row_spacing_inches',
                'seeds_per_hole',
                'requires_light_for_germination',
                'seed_starting_notes',
                'seed_type',
                'transplant_soil_temp_min',
                'transplant_soil_temp_max',
                'transplant_notes',
                'hardening_off_days',
                'hardening_off_notes'
            ]);
        });
    }
};

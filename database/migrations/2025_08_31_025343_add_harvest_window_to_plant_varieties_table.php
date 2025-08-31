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
            // Harvest window data - critical for succession planning
            $table->date('harvest_start')->nullable(); // Optimal harvest start date (MM-DD format, year-agnostic)
            $table->date('harvest_end')->nullable(); // Optimal harvest end date (MM-DD format, year-agnostic)
            $table->date('yield_peak')->nullable(); // Peak yield date (MM-DD format, year-agnostic)
            $table->integer('harvest_window_days')->nullable(); // Duration of harvest window in days
            
            // Additional harvest metadata
            $table->text('harvest_notes')->nullable(); // Special harvest instructions
            $table->string('harvest_method')->nullable(); // continuous, once-over, multiple-passes
            $table->decimal('expected_yield_per_plant', 8, 2)->nullable(); // Expected yield per plant
            $table->string('yield_unit')->nullable(); // pounds, ounces, bunches, etc.
            
            // Seasonal adjustments
            $table->json('seasonal_adjustments')->nullable(); // JSON with seasonal timing adjustments
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plant_varieties', function (Blueprint $table) {
            $table->dropColumn([
                'harvest_start',
                'harvest_end', 
                'yield_peak',
                'harvest_window_days',
                'harvest_notes',
                'harvest_method',
                'expected_yield_per_plant',
                'yield_unit',
                'seasonal_adjustments'
            ]);
        });
    }
};

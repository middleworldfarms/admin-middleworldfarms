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
            // Plant spacing in centimeters (metric)
            // These will be synced from FarmOS taxonomy fields
            $table->decimal('in_row_spacing_cm', 5, 1)->nullable()->after('row_spacing_inches')
                  ->comment('Distance between plants in a row (centimeters)');
            
            $table->decimal('between_row_spacing_cm', 5, 1)->nullable()->after('in_row_spacing_cm')
                  ->comment('Distance between rows (centimeters)');
            
            // Planting method - helps determine overseeding calculations
            $table->enum('planting_method', ['direct', 'transplant', 'both'])->nullable()->after('between_row_spacing_cm')
                  ->comment('Primary planting method for this variety');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plant_varieties', function (Blueprint $table) {
            $table->dropColumn(['in_row_spacing_cm', 'between_row_spacing_cm', 'planting_method']);
        });
    }
};

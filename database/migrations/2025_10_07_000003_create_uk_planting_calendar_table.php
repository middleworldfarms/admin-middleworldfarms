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
        Schema::create('uk_planting_calendar', function (Blueprint $table) {
            $table->id();
            $table->string('crop_name')->index();
            $table->string('crop_family')->nullable()->index();
            $table->string('variety_type')->nullable(); // e.g., 'summer', 'winter', 'early', 'maincrop'
            
            // Seeding windows
            $table->string('indoor_seed_months')->nullable(); // e.g., 'Feb-Apr'
            $table->string('outdoor_seed_months')->nullable(); // e.g., 'May-Jul'
            $table->string('transplant_months')->nullable(); // e.g., 'May-Jun'
            
            // Harvest windows
            $table->string('harvest_months')->nullable(); // e.g., 'Nov-Feb'
            
            // UK-specific timing
            $table->boolean('frost_hardy')->default(false);
            $table->string('uk_hardiness_zone')->nullable(); // H1-H7 RHS scale
            $table->date('typical_last_frost')->nullable(); // e.g., May 15
            $table->date('typical_first_frost')->nullable(); // e.g., Oct 15
            
            // Regional notes
            $table->string('uk_region')->default('general'); // 'south', 'north', 'scotland', 'general'
            $table->text('seasonal_notes')->nullable();
            $table->text('uk_specific_advice')->nullable();
            
            // Protection requirements
            $table->boolean('needs_cloche')->default(false);
            $table->boolean('needs_fleece')->default(false);
            $table->boolean('needs_polytunnel')->default(false);
            
            $table->string('source')->nullable();
            $table->integer('confidence_score')->default(5);
            $table->timestamps();
            
            // Indexes
            $table->index(['crop_name', 'variety_type'], 'uk_cal_crop_var_idx');
            $table->index(['crop_family', 'uk_region'], 'uk_cal_fam_reg_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uk_planting_calendar');
    }
};

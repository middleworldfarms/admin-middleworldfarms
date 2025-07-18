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
        Schema::create('crop_plans', function (Blueprint $table) {
            $table->id();
            $table->string('farmos_asset_id')->nullable(); // FarmOS plant asset ID
            $table->string('crop_name'); // Name of the crop/variety
            $table->string('crop_type'); // Plant type (tomatoes, lettuce, etc.)
            $table->string('variety')->nullable(); // Specific variety
            $table->date('planned_seeding_date')->nullable();
            $table->date('actual_seeding_date')->nullable();
            $table->date('planned_transplant_date')->nullable();
            $table->date('actual_transplant_date')->nullable();
            $table->date('planned_harvest_start')->nullable();
            $table->date('planned_harvest_end')->nullable();
            $table->date('actual_harvest_start')->nullable();
            $table->date('actual_harvest_end')->nullable();
            $table->string('location')->nullable(); // Field/greenhouse location
            $table->integer('planned_quantity')->nullable(); // Plants or area
            $table->integer('actual_quantity')->nullable();
            $table->string('quantity_units')->nullable(); // plants, m², etc.
            $table->decimal('expected_yield', 10, 3)->nullable(); // Expected harvest
            $table->decimal('actual_yield', 10, 3)->default(0); // Actual harvest total
            $table->string('yield_units')->nullable(); // kg, lbs, etc.
            $table->string('status')->default('planned'); // planned, seeded, growing, harvesting, completed
            $table->text('notes')->nullable();
            $table->json('farmos_data')->nullable(); // Full FarmOS asset data
            $table->timestamps();
            
            $table->index(['crop_type']);
            $table->index(['status']);
            $table->index(['planned_harvest_start']);
            $table->index(['actual_harvest_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_plans');
    }
};

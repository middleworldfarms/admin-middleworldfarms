<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL doesn't allow ALTER on ENUMs directly, so we need to change the column type
        DB::statement("ALTER TABLE plant_varieties MODIFY COLUMN planting_method ENUM('direct', 'transplant', 'both', 'either') NULL COMMENT 'Primary planting method for this variety'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert 'either' values to 'both' before reverting
        DB::statement("UPDATE plant_varieties SET planting_method = 'both' WHERE planting_method = 'either'");
        DB::statement("ALTER TABLE plant_varieties MODIFY COLUMN planting_method ENUM('direct', 'transplant', 'both') NULL COMMENT 'Primary planting method for this variety'");
    }
};

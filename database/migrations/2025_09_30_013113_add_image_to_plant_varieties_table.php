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
            $table->string('image_url')->nullable()->after('description');
            $table->string('image_alt_text')->nullable()->after('image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plant_varieties', function (Blueprint $table) {
            $table->dropColumn(['image_url', 'image_alt_text']);
        });
    }
};

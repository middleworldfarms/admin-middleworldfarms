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
        Schema::create('weather_historical_data', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->year('year');
            $table->tinyInteger('month');
            $table->tinyInteger('day');

            // Temperature data
            $table->decimal('temperature_max', 5, 2)->nullable();
            $table->decimal('temperature_min', 5, 2)->nullable();
            $table->decimal('temperature_avg', 5, 2)->nullable();

            // Atmospheric conditions
            $table->unsignedTinyInteger('humidity')->nullable();
            $table->decimal('pressure', 7, 2)->nullable();
            $table->decimal('wind_speed', 5, 2)->nullable();
            $table->unsignedSmallInteger('wind_direction')->nullable();
            $table->decimal('precipitation', 6, 2)->default(0);
            $table->decimal('snowfall', 6, 2)->default(0);
            $table->unsignedTinyInteger('cloudiness')->nullable();

            // Weather conditions
            $table->string('weather_condition', 50)->nullable();
            $table->string('weather_description', 100)->nullable();
            $table->decimal('uv_index', 3, 1)->nullable();

            // Sun times
            $table->timestamp('sunrise')->nullable();
            $table->timestamp('sunset')->nullable();

            // Agricultural calculations
            $table->decimal('growing_degree_days', 6, 2)->nullable();
            $table->enum('frost_risk', [
                'no_frost_risk',
                'frost_risk',
                'light_frost_risk',
                'frost',
                'severe_frost'
            ])->default('no_frost_risk');
            $table->enum('planting_suitability', [
                'poor',
                'good',
                'excellent'
            ])->default('poor');

            // Raw API response for future analysis
            $table->json('raw_data')->nullable();

            // Indexes for performance
            $table->index(['date', 'latitude', 'longitude'], 'weather_location_date');
            $table->index(['year', 'month'], 'weather_year_month');
            $table->index('frost_risk', 'weather_frost_risk');
            $table->index('planting_suitability', 'weather_planting_suitability');

            $table->timestamps();

            // Ensure no duplicate entries for same location/date
            $table->unique(['date', 'latitude', 'longitude'], 'weather_unique_location_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_historical_data');
    }
};

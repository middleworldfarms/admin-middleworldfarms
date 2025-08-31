<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlantVariety extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmos_id',
        'farmos_tid',
        'name',
        'description',
        'scientific_name',
        'crop_family',
        'plant_type',
        'plant_type_id',
        'maturity_days',
        'transplant_days',
        'harvest_days',
        'min_temperature',
        'max_temperature',
        'optimal_temperature',
        'season',
        'frost_tolerance',
        'companions',
        'external_uris',
        'farmos_data',
        'is_active',
        'last_synced_at',
        'sync_status',
        // Harvest window data
        'harvest_start',
        'harvest_end',
        'yield_peak',
        'harvest_window_days',
        'harvest_notes',
        'harvest_method',
        'expected_yield_per_plant',
        'yield_unit',
        'seasonal_adjustments',
        // Seeding and transplant data
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
    ];

    protected $casts = [
        'companions' => 'array',
        'external_uris' => 'array',
        'farmos_data' => 'array',
        'seasonal_adjustments' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'min_temperature' => 'decimal:2',
        'max_temperature' => 'decimal:2',
        'optimal_temperature' => 'decimal:2',
        'expected_yield_per_plant' => 'decimal:2',
        'harvest_window_days' => 'integer',
        // Seeding and transplant casts
        'indoor_seed_start' => 'date',
        'indoor_seed_end' => 'date',
        'outdoor_seed_start' => 'date',
        'outdoor_seed_end' => 'date',
        'transplant_start' => 'date',
        'transplant_end' => 'date',
        'transplant_window_days' => 'integer',
        'germination_days_min' => 'integer',
        'germination_days_max' => 'integer',
        'germination_temp_min' => 'decimal:2',
        'germination_temp_max' => 'decimal:2',
        'germination_temp_optimal' => 'decimal:2',
        'planting_depth_inches' => 'decimal:2',
        'seed_spacing_inches' => 'decimal:2',
        'row_spacing_inches' => 'decimal:2',
        'seeds_per_hole' => 'integer',
        'requires_light_for_germination' => 'boolean',
        'transplant_soil_temp_min' => 'decimal:2',
        'transplant_soil_temp_max' => 'decimal:2',
        'hardening_off_days' => 'integer',
        // Harvest date casts
        'harvest_start' => 'date',
        'harvest_end' => 'date',
        'yield_peak' => 'date'
    ];

    /**
     * Scope for active varieties
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for varieties by plant type
     */
    public function scopeByPlantType($query, $plantType)
    {
        return $query->where('plant_type', $plantType);
    }

    /**
     * Scope for varieties by season
     */
    public function scopeBySeason($query, $season)
    {
        return $query->where('season', $season);
    }

    /**
     * Scope for search functionality
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('scientific_name', 'like', "%{$searchTerm}%");
    }

    /**
     * Get varieties that need syncing
     */
    public function scopeNeedsSync($query)
    {
        return $query->where(function($q) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subDays(7))
              ->orWhere('sync_status', 'failed');
        });
    }
}

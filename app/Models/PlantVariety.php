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
        'sync_status'
    ];

    protected $casts = [
        'companions' => 'array',
        'external_uris' => 'array',
        'farmos_data' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'min_temperature' => 'decimal:2',
        'max_temperature' => 'decimal:2',
        'optimal_temperature' => 'decimal:2'
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

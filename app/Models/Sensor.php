<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sensor extends Model
{
    protected $fillable = [
        'sensor_id',
        'name',
        'type',
        'branch',
        'location',
        'zone',
        'min_threshold',
        'max_threshold',
        'warning_threshold_min',
        'warning_threshold_max',
        'unit',
        'status',
        'calibration_data',
        'last_reading_at',
        'last_reading_value',
        'notes',
    ];

    protected $casts = [
        'calibration_data' => 'array',
        'last_reading_at' => 'datetime',
        'min_threshold' => 'decimal:2',
        'max_threshold' => 'decimal:2',
        'warning_threshold_min' => 'decimal:2',
        'warning_threshold_max' => 'decimal:2',
        'last_reading_value' => 'decimal:2',
    ];

    // Relationships
    public function readings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(EnvironmentalAlert::class);
    }

    // Scopes
    public function scopeByBranch($query, $branch)
    {
        return $query->where('branch', $branch);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Methods
    public function isWithinThreshold($value = null): bool
    {
        $value = $value ?? $this->last_reading_value;
        
        if (!$value || !$this->min_threshold || !$this->max_threshold) {
            return true;
        }

        return $value >= $this->min_threshold && $value <= $this->max_threshold;
    }

    public function isInWarningRange($value = null): bool
    {
        $value = $value ?? $this->last_reading_value;
        
        if (!$value || !$this->warning_threshold_min || !$this->warning_threshold_max) {
            return false;
        }

        return $value <= $this->warning_threshold_min || $value >= $this->warning_threshold_max;
    }

    public function getLatestReading(): ?SensorReading
    {
        return $this->readings()->latest('recorded_at')->first();
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'active' => 'bg-success',
            'inactive' => 'bg-secondary',
            'maintenance' => 'bg-warning',
            'error' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    public function updateLastReading($value, $status = 'normal')
    {
        $this->update([
            'last_reading_value' => $value,
            'last_reading_at' => now(),
        ]);

        // Create reading record
        return $this->readings()->create([
            'value' => $value,
            'status' => $status,
            'recorded_at' => now(),
        ]);
    }
}

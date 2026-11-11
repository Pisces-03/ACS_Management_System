<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    protected $fillable = [
        'sensor_id',
        'value',
        'status',
        'metadata',
        'recorded_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'metadata' => 'array',
        'recorded_at' => 'datetime',
    ];

    // Relationships
    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }

    // Scopes
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCritical($query)
    {
        return $query->where('status', 'critical');
    }

    public function scopeWarning($query)
    {
        return $query->where('status', 'warning');
    }

    // Methods
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'normal' => 'bg-success',
            'warning' => 'bg-warning',
            'critical' => 'bg-danger',
            'error' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    public function getFormattedValue(): string
    {
        return $this->value . ' ' . $this->sensor->unit;
    }

    public function isOutOfRange(): bool
    {
        return in_array($this->status, ['warning', 'critical']);
    }
}

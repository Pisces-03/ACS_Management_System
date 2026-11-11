<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'maintenance_log_id',
        'employee_id',
        'customer_name',
        'customer_contact',
        'rating',
        'feedback',
        'service_aspects',
        'would_recommend',
        'suggestions',
        'branch',
        'submitted_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'service_aspects' => 'array',
        'would_recommend' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    // Relationships
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function maintenanceLog()
    {
        return $this->belongsTo(MaintenanceLog::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    // Scopes
    public function scopeByBranch($query, $branch)
    {
        return $query->where('branch', $branch);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    // Accessors
    public function getBranchNameAttribute()
    {
        return match($this->branch) {
            'las_pinas' => 'Las Piñas',
            'cebu' => 'Cebu',
            'davao' => 'Davao',
            default => $this->branch
        };
    }

    // The log linked directly, otherwise the employee's latest log for the same task
    // (evaluations are submitted from the task page, often before the log is written)
    public function getLinkedLogAttribute()
    {
        if ($this->maintenanceLog) {
            return $this->maintenanceLog;
        }

        return $this->task?->maintenanceLogs
            ->where('employee_id', $this->employee_id)
            ->sortByDesc('id')
            ->first();
    }

    public function getAverageRatingAttribute()
    {
        return $this->rating;
    }

    public function getRatingTextAttribute()
    {
        return match($this->rating) {
            1 => 'Poor',
            2 => 'Fair',
            3 => 'Good',
            4 => 'Very Good',
            5 => 'Excellent',
            default => 'Not Rated'
        };
    }
}

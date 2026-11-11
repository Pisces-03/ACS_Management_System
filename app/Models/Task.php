<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_number',
        'title',
        'description',
        'type',
        'category',
        'priority',
        'status',
        'branch',
        'assigned_to',
        'assigned_employees',
        'maintenance_schedule_id',
        'created_by',
        'due_date',
        'started_at',
        'completed_at',
        'completion_notes',
        'cancellation_reason',
        'cancelled_at',
        'estimated_hours',
        'actual_hours',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'estimated_hours' => 'array',
        'actual_hours' => 'array',
        'assigned_employees' => 'array',
    ];

    // Relationships
    public function assignedEmployee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all assigned employees (for multiple assignees support)
     */
    public function assignedEmployees()
    {
        if (!$this->assigned_employees || empty($this->assigned_employees)) {
            // * Fallback to single assignee if assigned_employees is not set
            return $this->assigned_to ? collect([$this->assignedEmployee]) : collect([]);
        }
        
        return User::whereIn('id', $this->assignedEmployeeIds())->get();
    }

    /**
     * * IDs of everyone assigned to this task (first assignee + assigned_employees)
     */
    public function assignedEmployeeIds(): array
    {
        $ids = $this->assigned_employees;

        // * Older rows were saved double-encoded (a JSON string inside JSON)
        if (is_string($ids)) {
            $ids = json_decode($ids, true);
        }

        $ids = array_map('intval', is_array($ids) ? $ids : []);

        if ($this->assigned_to) {
            $ids[] = (int) $this->assigned_to;
        }

        return array_values(array_unique($ids));
    }

    public function isAssignedTo($userId): bool
    {
        return in_array((int) $userId, $this->assignedEmployeeIds(), true);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function maintenanceLogs()
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class);
    }

    public function customerFeedback()
    {
        return $this->hasMany(CustomerFeedback::class);
    }

    public function maintenanceSchedule()
    {
        return $this->hasOne(TaskMaintenanceSchedule::class);
    }

    /**
     * * The schedule that auto-generated this preventive task (if any)
     */
    public function sourceSchedule()
    {
        return $this->belongsTo(TaskMaintenanceSchedule::class, 'maintenance_schedule_id');
    }

    // Accessors
    public function getTypeNameAttribute()
    {
        return match($this->type) {
            'repair' => 'Repair',
            'installation' => 'Installation',
            'reinstallation' => 'Reinstallation',
            'checkup' => 'Check-up',
            'dismantling' => 'Dismantling',
            'preventive' => 'Preventive Maintenance',
            default => ucfirst($this->type ?? 'N/A')
        };
    }

    public function getStatusNameAttribute()
    {
        return match($this->status) {
            'pending' => 'Pending',
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => $this->status
        };
    }

    public function getPriorityNameAttribute()
    {
        return match($this->priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => $this->priority
        };
    }

    public function getBranchNameAttribute()
    {
        return match($this->branch) {
            'las_pinas' => 'Las Piñas',
            'cebu' => 'Cebu',
            'davao' => 'Davao',
            default => $this->branch
        };
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date && $this->due_date->isPast() && !in_array($this->status, ['completed', 'cancelled']);
    }

    // Scopes
    public function scopeByBranch($query, $branch)
    {
        return $query->where('branch', $branch);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAssignedTo($query, $userId)
    {
        // * Match the first assignee or anyone in the multi-assignee list
        return $query->where(function ($q) use ($userId) {
            $q->where('assigned_to', $userId)
              ->orWhereJsonContains('assigned_employees', (int) $userId)
              ->orWhereJsonContains('assigned_employees', (string) $userId);
        });
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', \Carbon\Carbon::now('Asia/Manila'))
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    // Methods
    public function markAsStarted()
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => \Carbon\Carbon::now('Asia/Manila'),
        ]);
    }

    public function markAsCompleted($notes = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => \Carbon\Carbon::now('Asia/Manila'),
            'completion_notes' => $notes,
        ]);
    }

    public function assignTo($userId)
    {
        $this->update([
            'assigned_to' => $userId,
        ]);
    }

    /**
     * Check if task has an active maintenance schedule.
     */
    public function hasMaintenanceSchedule(): bool
    {
        return $this->maintenanceSchedule && $this->maintenanceSchedule->is_active;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            if (!$task->task_number) {
                $philippineTime = \Carbon\Carbon::now('Asia/Manila');
                // * Count-based numbers repeat after a task is deleted, so skip any number already taken
                $prefix = 'TSK-' . $philippineTime->format('Ymd') . '-';
                $sequence = static::count() + 1;
                while (static::where('task_number', $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT))->exists()) {
                    $sequence++;
                }
                $task->task_number = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            }
            if (!$task->branch && auth()->check()) {
                $task->branch = auth()->user()->branch;
            }
        });

        // * Completing an auto-generated preventive task records the service on its
        // * schedule, which moves the schedule to its next due date
        static::updated(function ($task) {
            if ($task->wasChanged('status') && $task->status === 'completed' && $task->sourceSchedule) {
                $task->sourceSchedule->recordCompletion($task);
            }
        });
    }
}

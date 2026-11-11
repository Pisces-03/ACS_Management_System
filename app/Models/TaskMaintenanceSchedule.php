<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TaskMaintenanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'frequency_type',
        'frequency_value',
        'advance_notice_days',
        'last_maintenance_date',
        'next_due_date',
        'advance_notification_sent_at',
        'due_notification_sent_at',
        'is_active',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'last_maintenance_date' => 'date',
        'next_due_date' => 'date',
        'advance_notification_sent_at' => 'datetime',
        'due_notification_sent_at' => 'datetime',
        'is_active' => 'boolean',
        // * Form input arrives as strings; Carbon's add/sub methods need integers
        'frequency_value' => 'integer',
        'advance_notice_days' => 'integer',
    ];

    /**
     * Get the task that owns this maintenance schedule.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who created this schedule.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate next maintenance due date based on frequency.
     * Uses last_maintenance_date if available, otherwise uses task completion date.
     */
    public function calculateNextDueDate(): void
    {
        $baseDate = $this->last_maintenance_date;
        
        // If no last maintenance date, use task completion date
        if (!$baseDate && $this->task && $this->task->completed_at) {
            $baseDate = $this->task->completed_at->toDateString();
        }
        
        // Fallback to current date if neither exists
        if (!$baseDate) {
            $baseDate = Carbon::now('Asia/Manila')->toDateString();
        }

        $baseDate = Carbon::parse($baseDate, 'Asia/Manila');

        switch ($this->frequency_type) {
            case 'days':
                $this->next_due_date = $baseDate->copy()->addDays($this->frequency_value);
                break;
            case 'weeks':
                $this->next_due_date = $baseDate->copy()->addWeeks($this->frequency_value);
                break;
            case 'months':
                $this->next_due_date = $baseDate->copy()->addMonths($this->frequency_value);
                break;
            case 'years':
                $this->next_due_date = $baseDate->copy()->addYears($this->frequency_value);
                break;
        }

        $this->save();
    }

    /**
     * Check if maintenance is due.
     */
    public function isDue(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return Carbon::now('Asia/Manila')->toDateString() >= $this->next_due_date->toDateString();
    }

    /**
     * Check if advance notification should be sent.
     * Notification should be sent X days before next_due_date (default 10 days).
     * If the advance date has passed but notification wasn't sent, send it anyway.
     */
    public function shouldSendAdvanceNotification(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now('Asia/Manila');
        $advanceDate = $this->next_due_date->copy()->subDays($this->advance_notice_days);
        
        // Check if we're at or past the advance notice date, but before the due date
        $isWithinAdvancePeriod = $now->toDateString() >= $advanceDate->toDateString() 
            && $now->toDateString() < $this->next_due_date->toDateString();
        
        // Check if notification hasn't been sent yet for this maintenance cycle
        // If notification was sent before the advance date, we should send it again
        $notificationNotSent = is_null($this->advance_notification_sent_at);
        
        // If notification was sent, check if it was for a different maintenance cycle
        if (!$notificationNotSent) {
            $sentDate = Carbon::parse($this->advance_notification_sent_at, 'Asia/Manila');
            // If notification was sent before the advance date for this cycle, send again
            $notificationNotSent = $sentDate->toDateString() < $advanceDate->toDateString();
        }

        return $isWithinAdvancePeriod && $notificationNotSent;
    }

    /**
     * Check if due date notification should be sent.
     * Notification should be sent on the next_due_date.
     */
    public function shouldSendDueNotification(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now('Asia/Manila');
        $isDueDate = $now->toDateString() >= $this->next_due_date->toDateString();
        
        // Check if notification hasn't been sent today
        $notificationNotSentToday = is_null($this->due_notification_sent_at) 
            || Carbon::parse($this->due_notification_sent_at, 'Asia/Manila')->toDateString() < $now->toDateString();

        return $isDueDate && $notificationNotSentToday;
    }

    /**
     * Tasks this schedule has auto-generated.
     */
    public function generatedTasks()
    {
        return $this->hasMany(Task::class, 'maintenance_schedule_id');
    }

    /**
     * * The generated task that is still open for the current cycle (if any)
     */
    public function openGeneratedTask(): ?Task
    {
        return $this->generatedTasks()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest('id')
            ->first();
    }

    /**
     * * Automated scheduling: once the schedule is due, create a preventive
     * * check-up task for the same team that handled the original job.
     * * Returns null when the schedule is not due or a task is already open.
     */
    public function generateTask(): ?Task
    {
        if (!$this->isDue() || !$this->task || $this->openGeneratedTask()) {
            return null;
        }

        $source = $this->task;

        // * Keep only assignees who are still active and in the task's branch
        $assignees = User::whereIn('id', $source->assignedEmployeeIds())
            ->where('is_active', true)
            ->where('branch', $source->branch)
            ->get();

        $creatorId = $this->created_by ?? $source->created_by;
        $now = Carbon::now('Asia/Manila');
        $dueDate = $this->next_due_date->copy()->endOfDay();
        if ($dueDate->lt($now)) {
            // * Overdue schedules get a short window instead of a due date in the past
            $dueDate = $now->copy()->addDays(2)->endOfDay();
        }

        $lastServiced = $this->last_maintenance_date ?? $source->completed_at;

        $task = Task::create([
            'title' => 'Preventive Maintenance: ' . $source->title,
            'description' => "Automatically generated from the maintenance schedule of task {$source->task_number} "
                . "({$this->frequency_description})."
                . ($lastServiced ? "\nLast serviced: " . $lastServiced->format('M d, Y') . '.' : '')
                . ($this->notes ? "\n\nSchedule notes: {$this->notes}" : '')
                . "\n\nOriginal job details:\n{$source->description}",
            'type' => 'checkup',
            'priority' => 'medium',
            'status' => 'pending',
            'branch' => $source->branch,
            'assigned_to' => $assignees->first()?->id,
            'assigned_employees' => $assignees->pluck('id')->all(),
            'maintenance_schedule_id' => $this->id,
            'created_by' => $creatorId,
            'due_date' => $dueDate,
        ]);

        foreach ($assignees as $employee) {
            try {
                \App\Services\NotificationService::taskAssigned($task, $employee);
            } catch (\Throwable $e) {
                \Log::warning('Preventive task notification failed: ' . $e->getMessage());
            }
        }

        AuditLog::log(
            'task_auto_generated',
            "Auto-generated preventive maintenance task {$task->task_number} from schedule of task {$source->task_number}",
            $task,
            null,
            ['schedule_id' => $this->id, 'next_due_date' => $this->next_due_date->toDateString()],
            $creatorId
        );

        return $task;
    }

    /**
     * * Generate tasks for every active schedule that is due. Returns the created tasks.
     */
    public static function generateDueTasks(?string $branch = null)
    {
        $today = Carbon::now('Asia/Manila')->toDateString();

        return static::with('task')
            ->where('is_active', true)
            ->whereDate('next_due_date', '<=', $today)
            ->when($branch, fn ($q) => $q->whereHas('task', fn ($t) => $t->where('branch', $branch)))
            ->get()
            ->map(fn ($schedule) => $schedule->generateTask())
            ->filter()
            ->values();
    }

    /**
     * * Called when a generated preventive task is completed: the completion becomes the
     * * new "last maintenance" record and the next due date is calculated from it.
     */
    public function recordCompletion(Task $completedTask): void
    {
        $completedOn = ($completedTask->completed_at ?? Carbon::now('Asia/Manila'))->toDateString();

        $this->last_maintenance_date = $completedOn;
        $this->advance_notification_sent_at = null;
        $this->due_notification_sent_at = null;
        $this->calculateNextDueDate();

        // * Close this cycle's pending maintenance entry and open one for the next cycle
        $pendingLog = MaintenanceLog::where('task_id', $this->task_id)->where('status', 'pending')->first();
        if ($pendingLog) {
            $nextLog = $pendingLog->replicate(['log_number', 'performed_at', 'completed_at']);
            $pendingLog->update([
                'status' => 'completed',
                'performed_at' => $completedTask->completed_at ?? Carbon::now('Asia/Manila'),
                'completed_at' => $completedTask->completed_at ?? Carbon::now('Asia/Manila'),
                'notes' => trim(($pendingLog->notes ?? '') . "\nServiced via task {$completedTask->task_number}."),
            ]);
            $nextLog->next_maintenance_date = $this->next_due_date;
            $nextLog->started_at = Carbon::now('Asia/Manila');
            $nextLog->save();
        }
    }

    /**
     * Get human-readable frequency description.
     */
    public function getFrequencyDescriptionAttribute(): string
    {
        $type = match($this->frequency_type) {
            'days' => 'day(s)',
            'weeks' => 'week(s)',
            'months' => 'month(s)',
            'years' => 'year(s)',
            default => $this->frequency_type
        };

        return "Every {$this->frequency_value} {$type}";
    }
}


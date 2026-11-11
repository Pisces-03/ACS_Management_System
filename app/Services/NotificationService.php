<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\Task;
use App\Models\MaterialRequest;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send notification for task assignment
     */
    public static function taskAssigned(Task $task, User $assignedTo)
    {
        $notification = Notification::create([
            'user_id' => $assignedTo->id,
            'title' => 'New Task Assigned',
            'message' => "You have been assigned a new task: {$task->title}",
            'type' => 'task_assigned',
            'data' => [
                'task_id' => $task->id,
                'task_number' => $task->task_number,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->format('Y-m-d'),
            ],
            'priority' => $task->priority,
            'branch' => $assignedTo->branch,
        ]);

        // Send email notification if enabled
        self::sendEmailNotification($assignedTo, $notification);

        return $notification;
    }

    /**
     * Send notification for overdue tasks
     */
    public static function taskOverdue(Task $task)
    {
        if ($task->assignedEmployee) {
            $notification = Notification::create([
                'user_id' => $task->assignedEmployee->id,
                'title' => 'Task Overdue',
                'message' => "Task '{$task->title}' is overdue. Please complete it as soon as possible.",
                'type' => 'task_overdue',
                'data' => [
                    'task_id' => $task->id,
                    'task_number' => $task->task_number,
                    'days_overdue' => now()->diffInDays($task->due_date),
                ],
                'priority' => 'high',
                'branch' => $task->assignedEmployee->branch,
            ]);

            self::sendEmailNotification($task->assignedEmployee, $notification);
            return $notification;
        }

        return null;
    }

    /**
     * Send notification for material request approval needed
     */
    public static function materialRequestApprovalNeeded(MaterialRequest $request)
    {
        // Get admins and superadmins for the branch
        $approvers = User::whereIn('role', ['admin', 'superadmin'])
            ->where(function($query) use ($request) {
                $query->where('branch', $request->branch)
                      ->orWhere('role', 'superadmin');
            })
            ->get();

        $notifications = [];

        foreach ($approvers as $approver) {
            $notification = Notification::create([
                'user_id' => $approver->id,
                'title' => 'Material Request Approval Needed',
                'message' => "Material request #{$request->request_number} requires your approval.",
                'type' => 'approval_needed',
                'data' => [
                    'request_id' => $request->id,
                    'request_number' => $request->request_number,
                    'requested_by' => $request->requestedBy->full_name,
                    'total_cost' => $request->total_estimated_cost,
                    'priority' => $request->priority,
                ],
                'priority' => $request->priority,
                'branch' => $approver->branch,
            ]);

            self::sendEmailNotification($approver, $notification);
            $notifications[] = $notification;
        }

        return $notifications;
    }

    /**
     * Send notification for material request status change
     */
    public static function materialRequestStatusChanged(MaterialRequest $request, string $oldStatus)
    {
        $notification = Notification::create([
            'user_id' => $request->requested_by,
            'title' => 'Material Request Status Updated',
            'message' => "Your material request #{$request->request_number} has been {$request->status}.",
            'type' => $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'error' : 'info'),
            'data' => [
                'request_id' => $request->id,
                'request_number' => $request->request_number,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'approved_by' => $request->approvedBy?->full_name,
                'approved_at' => $request->approved_at?->format('Y-m-d H:i'),
            ],
            'priority' => 'medium',
            'branch' => $request->requestedBy->branch,
        ]);

        self::sendEmailNotification($request->requestedBy, $notification);
        return $notification;
    }

    /**
     * Send notification for task maintenance reminder
     * 
     * @param Task $task The task with maintenance schedule
     * @param string $type 'advance' for advance notification, 'due' for due date notification
     */
    public static function taskMaintenanceReminder(Task $task, string $type)
    {
        $schedule = $task->maintenanceSchedule;
        if (!$schedule || !$schedule->is_active) {
            return null;
        }

        $notifications = [];
        
        // Get users to notify: task creator and assigned employee (if exists)
        $usersToNotify = collect([$task->creator]);
        
        if ($task->assignedEmployee && $task->assignedEmployee->id !== $task->creator->id) {
            $usersToNotify->push($task->assignedEmployee);
        }

        // Also notify branch admins
        $branchAdmins = User::whereIn('role', ['admin', 'superadmin'])
            ->where(function($query) use ($task) {
                $query->where('branch', $task->branch)
                      ->orWhere('role', 'superadmin');
            })
            ->where('is_active', true)
            ->get();

        $usersToNotify = $usersToNotify->merge($branchAdmins)->unique('id');

        $nextDueDate = $schedule->next_due_date->format('M d, Y');
        $frequencyDesc = $schedule->frequency_description;

        if ($type === 'advance') {
            $title = 'Task Maintenance Reminder - Advance Notice';
            $message = "Task '{$task->title}' ({$task->task_number}) has scheduled maintenance due on {$nextDueDate}. " .
                      "This is a reminder {$schedule->advance_notice_days} days before the maintenance date. " .
                      "Maintenance frequency: {$frequencyDesc}.";
            $notificationType = 'task_maintenance_advance';
        } else {
            $title = 'Task Maintenance Due Today';
            $message = "Task '{$task->title}' ({$task->task_number}) has scheduled maintenance due today ({$nextDueDate}). " .
                      "Please perform the maintenance as scheduled. " .
                      "Maintenance frequency: {$frequencyDesc}.";
            $notificationType = 'task_maintenance_due';
        }

        foreach ($usersToNotify as $user) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => $notificationType,
                'data' => [
                    'task_id' => $task->id,
                    'task_number' => $task->task_number,
                    'task_title' => $task->title,
                    'next_due_date' => $schedule->next_due_date->format('Y-m-d'),
                    'frequency_type' => $schedule->frequency_type,
                    'frequency_value' => $schedule->frequency_value,
                    'advance_notice_days' => $schedule->advance_notice_days,
                    'reminder_type' => $type,
                ],
                'priority' => $type === 'due' ? 'high' : 'medium',
                'branch' => $user->branch,
            ]);

            self::sendEmailNotification($user, $notification);
            $notifications[] = $notification;
        }

        return $notifications;
    }

    /**
     * Send notification for maintenance due
     */
    public static function maintenanceDue(Task $task)
    {
        // Notify branch admins and the assigned employee
        $users = User::where(function($query) use ($task) {
            $query->where('id', $task->assigned_to)
                  ->orWhere(function($q) use ($task) {
                      $q->whereIn('role', ['admin', 'superadmin'])
                        ->where('branch', $task->branch);
                  });
        })->get();

        $notifications = [];

        foreach ($users as $user) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'title' => 'Maintenance Due',
                'message' => "Preventive maintenance is due: {$task->title}",
                'type' => 'maintenance_due',
                'data' => [
                    'task_id' => $task->id,
                    'task_number' => $task->task_number,
                    'equipment_name' => $task->title,
                    'scheduled_date' => $task->scheduled_date?->format('Y-m-d'),
                ],
                'priority' => 'medium',
                'branch' => $user->branch,
            ]);

            self::sendEmailNotification($user, $notification);
            $notifications[] = $notification;
        }

        return $notifications;
    }

    /**
     * Send email notification if enabled
     */
    private static function sendEmailNotification(User $user, Notification $notification)
    {
        // * Mail delivery itself is configured in .env (MAIL_MAILER)
        if (!$user->email) {
            return;
        }

        try {
            // Here you would implement the actual email sending
            // For now, we'll just log it
            \Log::info("Email notification sent to {$user->email}: {$notification->title}");

            // Example using Laravel Mail:
            // Mail::to($user->email)->send(new NotificationMail($notification));
        } catch (\Exception $e) {
            \Log::error("Failed to send email notification: " . $e->getMessage());
        }
    }

    /**
     * Send notification when employee submits maintenance findings
     */
    public static function maintenanceFindingsSubmitted(\App\Models\MaintenanceLog $maintenanceLog, User $employee)
    {
        \Log::info('NotificationService::maintenanceFindingsSubmitted called', [
            'maintenance_log_id' => $maintenanceLog->id,
            'employee_id' => $employee->id,
            'branch' => $maintenanceLog->branch
        ]);

        // Get branch admins and superadmins to notify
        $usersToNotify = User::whereIn('role', ['admin', 'superadmin'])
            ->where(function($query) use ($maintenanceLog) {
                $query->where('branch', $maintenanceLog->branch)
                      ->orWhere('role', 'superadmin');
            })
            ->where('is_active', true)
            ->get();

        \Log::info('Found users to notify', [
            'count' => $usersToNotify->count(),
            'users' => $usersToNotify->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->full_name,
                'role' => $u->role,
                'branch' => $u->branch
            ])->toArray()
        ]);

        $notifications = [];

        foreach ($usersToNotify as $admin) {
            try {
                $notification = Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'New Maintenance Findings Submitted',
                    'message' => "{$employee->full_name} has submitted new maintenance findings for {$maintenanceLog->equipment_name} (Log #{$maintenanceLog->log_number}).",
                    'type' => 'maintenance_findings_submitted',
                    'data' => [
                        'maintenance_log_id' => $maintenanceLog->id,
                        'log_number' => $maintenanceLog->log_number,
                        'equipment_name' => $maintenanceLog->equipment_name,
                        'type' => $maintenanceLog->type,
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name,
                        'branch' => $maintenanceLog->branch,
                    ],
                    'priority' => 'medium',
                    'branch' => $admin->branch,
                ]);

                \Log::info('Notification created successfully', [
                    'notification_id' => $notification->id,
                    'user_id' => $admin->id,
                    'user_name' => $admin->full_name
                ]);

                self::sendEmailNotification($admin, $notification);
                $notifications[] = $notification;
            } catch (\Exception $e) {
                \Log::error('Failed to create notification for user', [
                    'user_id' => $admin->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        \Log::info('NotificationService::maintenanceFindingsSubmitted completed', [
            'notifications_created' => count($notifications)
        ]);

        return $notifications;
    }

    /**
     * Mark notification as read
     */
    public static function markAsRead(Notification $notification)
    {
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Get unread notifications for a user
     */
    public static function getUnreadNotifications(User $user, $limit = 10)
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get unread count for a user
     */
    public static function getUnreadCount(User $user)
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }
}

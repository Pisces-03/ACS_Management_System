<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $query = Task::with(['assignedEmployee', 'creator']);

        // Filter based on user role
        if ($user->role === 'admin') {
            $query->byBranch($user->branch);
        } elseif ($user->role === 'employee') {
            $query->assignedTo($user->id);
        }

        // Apply optional filters to match UI controls
        if (request('status')) {
            $query->where('status', request('status'));
        }
        if (request('priority')) {
            $query->where('priority', request('priority'));
        }
        if (request('search')) {
            $search = '%' . request('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('task_number', 'like', $search);
            });
        }

        $tasks = $query->latest()->paginate(10);

        // Provide simple aggregate stats for header cards
        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
        ];

        return view('tasks.index', compact('tasks', 'stats'));
    }

    public function create()
    {
        $user = Auth::user();
        
        // * For superadmin, get all employees grouped by branch for dynamic filtering
        // * For admin, only get employees from their branch
        if ($user->role === 'superadmin') {
            $employees = User::where('role', 'employee')
                ->where('is_active', true)
                ->get()
                ->groupBy('branch');
        } else {
            $employees = User::where('role', 'employee')
                ->where('branch', $user->branch)
                ->where('is_active', true)
                ->get();
        }

        // Get recent tasks for reference
        $recentTasks = Task::where('created_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('tasks.create', compact('employees', 'recentTasks'));
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            
            // * Validation rules - branch is required for superadmin, auto-set for admin
            $validationRules = [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'assigned_to' => 'required|array|min:1',
                'assigned_to.*' => 'required|exists:users,id',
                'priority' => 'required|in:low,medium,high,urgent',
                'due_date' => 'required|date|after:today',
                // Match DB enum: installation|reinstallation|repair|dismantling|checkup
                'type' => 'required|in:installation,reinstallation,repair,dismantling,checkup',
                'estimated_hours' => 'nullable|numeric|min:0',
            ];

            // * Superadmin can select branch, admin's branch is auto-set
            if ($user->role === 'superadmin') {
                $validationRules['branch'] = 'required|in:las_pinas,cebu,davao';
            }

            $validated = $request->validate($validationRules);

            // * Determine branch: superadmin selects, admin uses their branch
            $branch = $user->role === 'superadmin' 
                ? $validated['branch'] 
                : $user->branch;

            // * Validate that all assigned employees belong to the selected branch
            $assignedEmployeeIds = $validated['assigned_to'];
            $assignedEmployees = User::whereIn('id', $assignedEmployeeIds)->get();
            
            foreach ($assignedEmployees as $employee) {
                if ($employee->branch !== $branch) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['assigned_to' => "The employee {$employee->full_name} does not belong to the selected branch."]);
                }
            }
            
            // * Get first assignee for backward compatibility (stored in assigned_to field)
            $firstAssignee = $assignedEmployees->first();

            DB::beginTransaction();

            // * Convert estimated_hours from decimal to array format (hours, minutes)
            $estimatedHoursData = null;
            // * Get the value from validated array first, fallback to raw request input
            $estimatedHoursInput = $validated['estimated_hours'] ?? $request->input('estimated_hours');
            if ($estimatedHoursInput !== null && $estimatedHoursInput !== '' && is_numeric($estimatedHoursInput) && (float)$estimatedHoursInput > 0) {
                $totalHours = (float) $estimatedHoursInput;
                $hours = (int) floor($totalHours);
                $minutes = (int) round(($totalHours - $hours) * 60);
                $estimatedHoursData = [
                    'hours' => $hours,
                    'minutes' => $minutes,
                ];
            }

            $task = Task::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'assigned_to' => $firstAssignee->id, // * First assignee for backward compatibility
                'created_by' => Auth::id(),
                'priority' => $validated['priority'],
                'due_date' => $validated['due_date'],
                'type' => $validated['type'],
                // * New tasks wait as Pending until the employee starts them
                'status' => 'pending',
                'branch' => $branch,
                'estimated_hours' => $estimatedHoursData,
                // * Store all assignees in JSON field for multiple assignee support
                'assigned_employees' => array_map('intval', $assignedEmployeeIds),
            ]);

            // Send notification to all assigned employees (best-effort)
            try {
                foreach ($assignedEmployees as $employee) {
                    NotificationService::taskAssigned($task, $employee);
                }
            } catch (\Throwable $notifyError) {
                // Log and continue; should not break task creation
                \Log::warning('Task notification failed: ' . $notifyError->getMessage());
            }

            DB::commit();

            return redirect()->route('tasks.index')
                ->with('success', 'Task created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Task creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'The task could not be created. Please check the details and try again.');
        }
    }

    public function show(Task $task)
    {
        $task->load(['assignedEmployee', 'creator', 'maintenanceSchedule.creator']);

        $user = Auth::user();
        if ($user->role === 'admin' && $task->branch !== $user->branch) {
            abort(403);
        }
        if ($user->role === 'employee' && !$task->isAssignedTo($user->id)) {
            abort(403, 'This task is not assigned to you.');
        }

        return view('tasks.show', compact('task'));
    }

    public function getMaintenanceSchedule(Task $task)
    {
        $user = Auth::user();
        
        // * Only admins and superadmins can view maintenance schedules
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view maintenance schedules.'
            ], 403);
        }

        // * Check branch authorization for admins
        if ($user->role === 'admin' && $task->branch !== $user->branch) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view maintenance schedules for tasks outside your branch.'
            ], 403);
        }

        $schedule = $task->maintenanceSchedule;
        
        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'No maintenance schedule found for this task.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'schedule' => $schedule
        ]);
    }

    public function edit(Task $task)
    {
        $user = Auth::user();
        if ($user->role === 'admin' && $task->branch !== $user->branch) {
            abort(403);
        }

        // * Prevent editing cancelled tasks
        if ($task->status === 'cancelled') {
            return redirect()->route('tasks.show', $task)
                ->with('error', 'Cancelled tasks cannot be edited. You can only delete them.');
        }

        $employees = User::where('role', 'employee')
                        ->where('is_active', true)
                        ->select('id', 'first_name', 'last_name', 'branch')
                        ->get();

        return view('tasks.edit', compact('task', 'employees'));
    }

    public function update(Request $request, Task $task)
    {
        $user = Auth::user();
        if ($user->role === 'admin' && $task->branch !== $user->branch) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        // * Prevent updating cancelled tasks
        if ($task->status === 'cancelled') {
            $message = 'Cancelled tasks cannot be updated. You can only delete them.';
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return redirect()->route('tasks.show', $task)->with('error', $message);
        }

        try {
            // Check if this is a status-only update (from AJAX)
            if ($request->has('status') && count($request->all()) <= 3) { // status + _token + _method
                $validated = $request->validate([
                    'status' => 'required|in:pending,assigned,in_progress,completed,cancelled',
                    'completion_notes' => 'nullable|string',
                ]);

                $updateData = [
                    'status' => $validated['status'],
                ];

                if ($validated['status'] === 'in_progress' && !$task->started_at) {
                    $updateData['started_at'] = now();
                }

                if ($validated['status'] === 'completed') {
                    $updateData['completed_at'] = now();
                    if (isset($validated['completion_notes']) && $validated['completion_notes']) {
                        $updateData['completion_notes'] = $validated['completion_notes'];
                    }
                }

                $task->update($updateData);

                // Return JSON for AJAX requests
                if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Task updated successfully',
                        'task' => $task->refresh()
                    ]);
                }
            } else {
                // Full form update from edit page
                $validated = $request->validate([
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                    // Align type with DB enum values
                    'type' => 'required|in:installation,reinstallation,repair,dismantling,checkup',
                    'priority' => 'required|in:low,medium,high,urgent',
                    'status' => 'required|in:pending,assigned,in_progress,completed,cancelled',
                    'branch' => 'required|in:las_pinas,cebu,davao',
                    'assigned_to' => 'nullable|exists:users,id',
                    'due_date' => 'nullable|date|after_or_equal:today',
                    'estimated_hours' => 'nullable|numeric|min:0',
                    'estimated_minutes' => 'nullable|integer|min:0|max:59',
                    'completion_notes' => 'nullable|string',
                ]);

                $user = Auth::user();

                $updateData = [
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'type' => $validated['type'],
                    'priority' => $validated['priority'],
                    'status' => $validated['status'],
                    'branch' => $validated['branch'],
                    'assigned_to' => $validated['assigned_to'],
                    'due_date' => $validated['due_date'] ? \Carbon\Carbon::parse($validated['due_date']) : null,
                ];

                // If current user is admin, force branch to their branch
                if ($user->role === 'admin') {
                    $updateData['branch'] = $user->branch;
                }

                // Handle estimated time
                if (isset($validated['estimated_hours']) || isset($validated['estimated_minutes'])) {
                    $updateData['estimated_hours'] = [
                        'hours' => (int)($validated['estimated_hours'] ?? 0),
                        'minutes' => (int)($validated['estimated_minutes'] ?? 0)
                    ];
                }

                // Handle status transitions
                if ($validated['status'] === 'in_progress' && !$task->started_at) {
                    $updateData['started_at'] = now();
                }

                if ($validated['status'] === 'completed') {
                    $updateData['completed_at'] = now();
                    if ($validated['completion_notes']) {
                        $updateData['completion_notes'] = $validated['completion_notes'];
                    }
                }

                $task->update($updateData);

                return redirect()->route('tasks.show', $task)
                    ->with('success', 'Task updated successfully');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }

    public function assign(Request $request, Task $task)
    {
        $user = Auth::user();
        if ($user->role === 'admin' && $task->branch !== $user->branch) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        try {
            $validated = $request->validate([
                'assigned_to' => 'required|exists:users,id',
                'reason' => 'nullable|string|max:500'
            ]);

            $oldAssignee = $task->assigned_to;
            $assignee = User::find($validated['assigned_to']);
            if ($user->role === 'admin' && $assignee && $assignee->branch !== $user->branch) {
                return response()->json(['success' => false, 'message' => 'Cannot assign to employee outside your branch'], 403);
            }

            $task->update([
                'assigned_to' => $validated['assigned_to'],
            ]);

            // Optional: Send notification to the newly assigned user
            // NotificationService::taskAssigned($task, User::find($validated['assigned_to']));

            return response()->json([
                'success' => true,
                'message' => 'Task assigned successfully',
                'task' => $task->refresh()->load(['assignedEmployee', 'creator'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, Task $task)
    {
        $user = Auth::user();
        
        // * Only admins and superadmins can delete tasks
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete tasks.'
                ], 403);
            }
            abort(403);
        }
        
        // * Check branch authorization for admins
        if ($user->role === 'admin' && $task->branch !== $user->branch) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this task.'
                ], 403);
            }
            abort(403);
        }

        // * Only cancelled tasks can be deleted
        if ($task->status !== 'cancelled') {
            $message = 'Only cancelled tasks can be deleted.';
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            }
            return redirect()->back()->with('error', $message);
        }

        // * Log before deletion
        \App\Models\AuditLog::log(
            'task_deleted',
            "Cancelled task {$task->task_number} was permanently deleted",
            $task,
            $task->toArray(),
            null
        );

        $task->delete();

        $message = 'Task deleted successfully.';
        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return redirect()->route('tasks.index')
            ->with('success', $message);
    }

    public function updateStatus(Request $request, Task $task)
    {
        try {
            $user = Auth::user();
            
            // * Only employees assigned to the task can update status
            if ($user->role !== 'employee' || !$task->isAssignedTo($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the assigned employee can update task status.'
                ], 403);
            }

            if ($task->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'This task was cancelled and can no longer be updated.'
                ], 422);
            }

            $validated = $request->validate([
                'status' => 'required|in:in_progress,completed',
                'completion_notes' => 'nullable|string',
            ]);

            // * Employees move a task forward one step: Pending -> In Progress -> Completed
            $allowedFrom = [
                'in_progress' => ['pending', 'assigned'],
                'completed' => ['in_progress'],
            ];
            if (!in_array($task->status, $allowedFrom[$validated['status']])) {
                return response()->json([
                    'success' => false,
                    'message' => $validated['status'] === 'completed'
                        ? 'Start the task (In Progress) before marking it as completed.'
                        : 'This task has already been started.'
                ], 422);
            }

            $updateData = [
                'status' => $validated['status'],
            ];

            if ($validated['status'] === 'in_progress') {
                $updateData['started_at'] = now();
            } else {
                $updateData['completed_at'] = now();
                if (isset($validated['completion_notes']) && $validated['completion_notes']) {
                    $updateData['completion_notes'] = $validated['completion_notes'];
                }
            }

            $task->update($updateData);

            return response()->json([
                'success' => true,
                'message' => $validated['status'] === 'in_progress'
                    ? 'Task is now In Progress'
                    : 'Task marked as completed',
                'task' => $task->refresh()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Request $request, Task $task)
    {
        try {
            $user = Auth::user();
            
            // * Check authorization: admins can cancel tasks in their branch, superadmins can cancel any task
            if ($user->role === 'admin' && $task->branch !== $user->branch) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to cancel this task.'
                    ], 403);
                }
                abort(403);
            }

            // * Check if task is already cancelled or completed
            if ($task->status === 'cancelled') {
                $message = 'Task is already cancelled.';
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 400);
                }
                return redirect()->back()->with('error', $message);
            }

            if ($task->status === 'completed') {
                $message = 'Cannot cancel a completed task.';
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 400);
                }
                return redirect()->back()->with('error', $message);
            }

            // * Validate cancellation reason
            $validated = $request->validate([
                'cancellation_reason' => 'required|string|min:10|max:1000',
            ]);

            // * Capture old status before updating
            $oldStatus = $task->status;

            // * Cancel the task with reason
            $task->update([
                'status' => 'cancelled',
                'cancellation_reason' => $validated['cancellation_reason'],
                'cancelled_at' => \Carbon\Carbon::now('Asia/Manila'),
            ]);

            // * Log the cancellation
            \App\Models\AuditLog::log(
                'task_cancelled',
                "Task {$task->task_number} was cancelled. Reason: {$validated['cancellation_reason']}",
                $task,
                ['old_status' => $oldStatus],
                ['new_status' => 'cancelled', 'cancellation_reason' => $validated['cancellation_reason']]
            );

            $message = 'Task cancelled successfully.';
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'task' => $task->refresh()
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            $errorMessage = 'An error occurred while cancelling the task: ' . $e->getMessage();
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    public function storeMaintenanceSchedule(Request $request, Task $task)
    {
        try {
            $user = Auth::user();
            
            // * Only admins and superadmins can set maintenance schedules
            if (!in_array($user->role, ['admin', 'superadmin'])) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to set maintenance schedules.'
                    ], 403);
                }
                abort(403);
            }

            // * Check branch authorization for admins
            if ($user->role === 'admin' && $task->branch !== $user->branch) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to set maintenance schedules for tasks outside your branch.'
                    ], 403);
                }
                abort(403);
            }

            // * Task must be completed to have a maintenance schedule
            if ($task->status !== 'completed') {
                $message = 'Only completed tasks can have maintenance schedules.';
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 400);
                }
                return redirect()->back()->with('error', $message);
            }

            $validated = $request->validate([
                'frequency_type' => 'required|in:days,weeks,months,years',
                'frequency_value' => 'required|integer|min:1',
                'advance_notice_days' => 'required|integer|min:1|max:365',
                'notes' => 'nullable|string|max:1000',
            ]);

            // * Form input arrives as strings; Carbon's add methods need integers
            $validated['frequency_value'] = (int) $validated['frequency_value'];
            $validated['advance_notice_days'] = (int) $validated['advance_notice_days'];

            // * Calculate next due date before creating the schedule
            $baseDate = $task->completed_at ? $task->completed_at : \Carbon\Carbon::now('Asia/Manila');
            $nextDueDate = $baseDate->copy();
            
            switch ($validated['frequency_type']) {
                case 'days':
                    $nextDueDate->addDays($validated['frequency_value']);
                    break;
                case 'weeks':
                    $nextDueDate->addWeeks($validated['frequency_value']);
                    break;
                case 'months':
                    $nextDueDate->addMonths($validated['frequency_value']);
                    break;
                case 'years':
                    $nextDueDate->addYears($validated['frequency_value']);
                    break;
            }

            // * Create or update maintenance schedule
            $schedule = \App\Models\TaskMaintenanceSchedule::updateOrCreate(
                ['task_id' => $task->id],
                [
                    'frequency_type' => $validated['frequency_type'],
                    'frequency_value' => $validated['frequency_value'],
                    'advance_notice_days' => $validated['advance_notice_days'],
                    'next_due_date' => $nextDueDate->toDateString(),
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $user->id,
                    'is_active' => true,
                ]
            );

            // * Log the action
            \App\Models\AuditLog::log(
                'task_maintenance_schedule_created',
                "Maintenance schedule created for task {$task->task_number}",
                $task,
                null,
                $validated
            );

            // * Create or update MaintenanceLog entry to show in maintenance section
            $this->syncMaintenanceLog($task, $schedule);

            $message = 'Maintenance schedule created successfully.';
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'schedule' => $schedule->load('creator')
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            $errorMessage = 'An error occurred while creating maintenance schedule: ' . $e->getMessage();
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    public function updateMaintenanceSchedule(Request $request, Task $task)
    {
        try {
            $user = Auth::user();
            
            // * Only admins and superadmins can update maintenance schedules
            if (!in_array($user->role, ['admin', 'superadmin'])) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to update maintenance schedules.'
                    ], 403);
                }
                abort(403);
            }

            // * Check branch authorization for admins
            if ($user->role === 'admin' && $task->branch !== $user->branch) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to update maintenance schedules for tasks outside your branch.'
                    ], 403);
                }
                abort(403);
            }

            $schedule = $task->maintenanceSchedule;
            if (!$schedule) {
                $message = 'No maintenance schedule found for this task.';
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 404);
                }
                return redirect()->back()->with('error', $message);
            }

            $validated = $request->validate([
                'frequency_type' => 'required|in:days,weeks,months,years',
                'frequency_value' => 'required|integer|min:1',
                'advance_notice_days' => 'required|integer|min:1|max:365',
                'notes' => 'nullable|string|max:1000',
            ]);

            // * Form input arrives as strings; Carbon's add methods need integers
            $validated['frequency_value'] = (int) $validated['frequency_value'];
            $validated['advance_notice_days'] = (int) $validated['advance_notice_days'];

            $oldValues = $schedule->toArray();
            
            $schedule->update([
                'frequency_type' => $validated['frequency_type'],
                'frequency_value' => $validated['frequency_value'],
                'advance_notice_days' => $validated['advance_notice_days'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // * Recalculate next due date if frequency changed
            if ($oldValues['frequency_type'] !== $validated['frequency_type'] 
                || $oldValues['frequency_value'] !== $validated['frequency_value']) {
                $schedule->calculateNextDueDate();
            }

            // * Log the action
            \App\Models\AuditLog::log(
                'task_maintenance_schedule_updated',
                "Maintenance schedule updated for task {$task->task_number}",
                $task,
                $oldValues,
                $validated
            );

            // * Update MaintenanceLog entry to reflect schedule changes
            $this->syncMaintenanceLog($task, $schedule);

            $message = 'Maintenance schedule updated successfully.';
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'schedule' => $schedule->load('creator')
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            $errorMessage = 'An error occurred while updating maintenance schedule: ' . $e->getMessage();
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    public function completeMaintenance(Request $request, Task $task)
    {
        try {
            $user = Auth::user();
            
            // * Only admins and superadmins can mark maintenance as complete
            if (!in_array($user->role, ['admin', 'superadmin'])) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to mark maintenance as complete.'
                    ], 403);
                }
                abort(403);
            }

            // * Check branch authorization for admins
            if ($user->role === 'admin' && $task->branch !== $user->branch) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to mark maintenance as complete for tasks outside your branch.'
                    ], 403);
                }
                abort(403);
            }

            $schedule = $task->maintenanceSchedule;
            if (!$schedule) {
                $message = 'No maintenance schedule found for this task.';
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 404);
                }
                return redirect()->back()->with('error', $message);
            }

            // * Update last maintenance date to today
            $schedule->update([
                'last_maintenance_date' => \Carbon\Carbon::now('Asia/Manila')->toDateString(),
                'advance_notification_sent_at' => null,
                'due_notification_sent_at' => null,
            ]);

            // * Recalculate next due date (manual reschedule)
            $schedule->calculateNextDueDate();

            // * Update existing pending MaintenanceLog to reflect completed maintenance
            $maintenanceLog = \App\Models\MaintenanceLog::where('task_id', $task->id)
                ->where('status', 'pending')
                ->first();
            if ($maintenanceLog) {
                $maintenanceLog->update([
                    'status' => 'completed',
                    'performed_at' => \Carbon\Carbon::now('Asia/Manila'),
                    'completed_at' => \Carbon\Carbon::now('Asia/Manila'),
                    'next_maintenance_date' => $schedule->next_due_date,
                ]);
            }

            // * Log the action
            \App\Models\AuditLog::log(
                'task_maintenance_completed',
                "Maintenance completed for task {$task->task_number}. Next due: {$schedule->next_due_date->format('Y-m-d')}",
                $task,
                ['old_next_due_date' => $schedule->getOriginal('next_due_date')],
                ['new_next_due_date' => $schedule->next_due_date->toDateString(), 'last_maintenance_date' => $schedule->last_maintenance_date->toDateString()]
            );

            // * Create new pending MaintenanceLog entry for next maintenance cycle
            $this->syncMaintenanceLog($task, $schedule);

            $message = 'Maintenance marked as complete. Next maintenance scheduled for ' . $schedule->next_due_date->format('M d, Y') . '.';
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'schedule' => $schedule->load('creator')
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            $errorMessage = 'An error occurred while marking maintenance as complete: ' . $e->getMessage();
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    public function deleteMaintenanceSchedule(Request $request, Task $task)
    {
        try {
            $user = Auth::user();
            
            // * Only admins and superadmins can delete maintenance schedules
            if (!in_array($user->role, ['admin', 'superadmin'])) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to delete maintenance schedules.'
                    ], 403);
                }
                abort(403);
            }

            // * Check branch authorization for admins
            if ($user->role === 'admin' && $task->branch !== $user->branch) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to delete maintenance schedules for tasks outside your branch.'
                    ], 403);
                }
                abort(403);
            }

            $schedule = $task->maintenanceSchedule;
            if (!$schedule) {
                $message = 'No maintenance schedule found for this task.';
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 404);
                }
                return redirect()->back()->with('error', $message);
            }

            // * Log before deletion
            \App\Models\AuditLog::log(
                'task_maintenance_schedule_deleted',
                "Maintenance schedule deleted for task {$task->task_number}",
                $task,
                $schedule->toArray(),
                null
            );

            $schedule->delete();

            // * Delete or update related MaintenanceLog entry
            $maintenanceLog = \App\Models\MaintenanceLog::where('task_id', $task->id)->first();
            if ($maintenanceLog) {
                // * Only delete if it's still pending and was created from this schedule
                // * Otherwise, keep it as it might have been updated manually
                if ($maintenanceLog->status === 'pending' && $maintenanceLog->next_maintenance_date) {
                    $maintenanceLog->delete();
                } else {
                    // * Clear the next_maintenance_date if schedule is deleted
                    $maintenanceLog->update(['next_maintenance_date' => null]);
                }
            }

            $message = 'Maintenance schedule deleted successfully.';
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            $errorMessage = 'An error occurred while deleting maintenance schedule: ' . $e->getMessage();
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * * Sync MaintenanceLog entry when maintenance schedule is created/updated.
     * * This ensures tasks with maintenance schedules appear in the maintenance section.
     */
    private function syncMaintenanceLog(Task $task, \App\Models\TaskMaintenanceSchedule $schedule)
    {
        // * Only sync if schedule is active
        if (!$schedule->is_active) {
            return;
        }

        // * Find existing pending MaintenanceLog for this task
        $existingLog = \App\Models\MaintenanceLog::where('task_id', $task->id)
            ->where('status', 'pending')
            ->first();

        // * If no pending log exists, create a new one
        if (!$existingLog) {
            $maintenanceLog = new \App\Models\MaintenanceLog();
            $maintenanceLog->task_id = $task->id;
        } else {
            $maintenanceLog = $existingLog;
        }

        // * The log belongs to the task's branch (a superadmin's own branch would misfile it)
        $branch = $task->branch;
        
        // Fallback to assigned employee's branch
        if (!$branch && $task->assigned_to) {
            $assignedEmployee = \App\Models\User::find($task->assigned_to);
            if ($assignedEmployee && $assignedEmployee->branch) {
                $branch = $assignedEmployee->branch;
            }
        }
        
        // Final fallback to authenticated user's branch
        if (!$branch && auth()->check()) {
            $branch = auth()->user()->branch;
        }

        // * Update MaintenanceLog with task and schedule information
        $maintenanceLog->fill([
            'employee_id' => $task->assigned_to,
            'type' => $task->type === 'reinstallation' ? 'reinstallation' : ($task->type ?? 'checkup'),
            'equipment_name' => $task->title ?? 'Task Maintenance',
            'description' => $task->description ?? "Scheduled maintenance for task {$task->task_number}",
            'work_performed' => $schedule->notes ?? "Scheduled maintenance: {$schedule->frequency_description}",
            'status' => 'pending',
            'branch' => $branch,
            'next_maintenance_date' => $schedule->next_due_date,
            'notes' => $schedule->notes,
            'performed_at' => null, // * Pending maintenance hasn't been performed yet
            'started_at' => $maintenanceLog->started_at ?? \Carbon\Carbon::now('Asia/Manila'), // * Set started_at if not already set
        ]);

        $maintenanceLog->save();
    }
}

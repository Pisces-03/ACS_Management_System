<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaskMaintenanceSchedule;
use App\Models\AuditLog;
use Carbon\Carbon;

class PreventiveMaintenanceController extends Controller
{
    /**
     * * Overview of every maintenance schedule and the preventive tasks generated from it.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        $today = Carbon::now('Asia/Manila')->startOfDay();
        $branch = $user->role === 'admin' ? $user->branch : $request->input('branch');

        $query = TaskMaintenanceSchedule::with(['task.assignedEmployee', 'creator'])
            ->withCount('generatedTasks')
            ->whereHas('task', fn ($q) => $q->when($branch, fn ($t) => $t->where('branch', $branch)));

        $status = $request->input('status');
        if ($status === 'overdue') {
            $query->where('is_active', true)->whereDate('next_due_date', '<', $today);
        } elseif ($status === 'due_soon') {
            $query->where('is_active', true)
                ->whereDate('next_due_date', '>=', $today)
                ->whereDate('next_due_date', '<=', $today->copy()->addDays(14));
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $baseStats = TaskMaintenanceSchedule::where('is_active', true)
            ->whereHas('task', fn ($q) => $q->when($branch, fn ($t) => $t->where('branch', $branch)));
        $stats = [
            'active' => (clone $baseStats)->count(),
            'due' => (clone $baseStats)->whereDate('next_due_date', '<=', $today)->count(),
            'due_soon' => (clone $baseStats)->whereDate('next_due_date', '>', $today)
                ->whereDate('next_due_date', '<=', $today->copy()->addDays(14))->count(),
            'generated' => \App\Models\Task::whereNotNull('maintenance_schedule_id')
                ->when($branch, fn ($q) => $q->where('branch', $branch))->count(),
        ];

        $schedules = $query->orderBy('next_due_date')->paginate(15)->withQueryString();

        return view('preventive-maintenance.index', compact('schedules', 'stats', 'branch'));
    }

    /**
     * * Run the automated scheduler now instead of waiting for the daily job.
     */
    public function generateTasks(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        $tasks = TaskMaintenanceSchedule::generateDueTasks($user->role === 'admin' ? $user->branch : null);

        AuditLog::log(
            'generate_tasks',
            "Generated {$tasks->count()} preventive maintenance task(s)"
        );

        $message = $tasks->isEmpty()
            ? 'No schedules are due right now, so no new tasks were generated.'
            : "Generated {$tasks->count()} preventive maintenance task(s): " . $tasks->pluck('task_number')->implode(', ');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'tasks_generated' => $tasks->count(),
            ]);
        }

        return redirect()->route('preventive-maintenance.index')->with('success', $message);
    }
}

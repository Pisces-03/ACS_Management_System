<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Task;
use App\Models\MaintenanceLog;
use App\Models\MaterialRequest;
use App\Models\CustomerFeedback;
use App\Models\User;
use App\Models\AttendanceLog;
use App\Models\TaskMaintenanceSchedule;
use App\Services\BranchAnalytics;
use App\Models\AuditLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $branch = $user->branch;

        // Get role-specific dashboard data
        switch ($user->role) {
            case 'superadmin':
                return $this->superadminDashboard();
            case 'admin':
                return $this->adminDashboard($branch);
            case 'employee':
                return $this->employeeDashboard($user->id, $branch);
            default:
                abort(403, 'Unauthorized access');
        }
    }

    private function superadminDashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_tasks' => Task::count(),
            'pending_tasks' => Task::where('status', 'pending')->count(),
            'overdue_tasks' => Task::overdue()->count(),
            'completed_tasks_today' => Task::where('status', 'completed')
                ->whereDate('completed_at', today())->count(),
            'total_maintenance_logs' => MaintenanceLog::count(),
            'pending_requests' => MaterialRequest::where('status', 'pending')->count(),
            'customer_feedback_avg' => CustomerFeedback::avg('rating'),
            'attendance_today' => AttendanceLog::whereDate('date', today())->count(),
            'preventive_maintenance_due' => TaskMaintenanceSchedule::whereDate('next_due_date', '<=', now('Asia/Manila')->toDateString())
                ->where('is_active', true)->count(),
        ];

        $branchStats = [
            'las_pinas' => $this->getBranchStats('las_pinas'),
            'cebu' => $this->getBranchStats('cebu'),
            'davao' => $this->getBranchStats('davao'),
        ];

        $recentActivity = $this->getRecentActivity();
        $tasksByPriority = $this->getTasksByPriority();
        $monthlyStats = $this->getMonthlyStats();

        // * Consolidated view across branches: expenses, recurring issues, personnel performance
        $costSummary = BranchAnalytics::costSummary();
        $recurringIssues = BranchAnalytics::recurringIssues();
        $topPerformers = BranchAnalytics::personnelPerformance();

        return view('dashboard.superadmin', compact(
            'stats', 'branchStats', 'recentActivity', 'tasksByPriority', 'monthlyStats',
            'costSummary', 'recurringIssues', 'topPerformers'
        ));
    }

    private function adminDashboard($branch)
    {
        $stats = [
            'branch_users' => User::byBranch($branch)->count(),
            'branch_tasks' => Task::byBranch($branch)->count(),
            'pending_tasks' => Task::byBranch($branch)->where('status', 'pending')->count(),
            'overdue_tasks' => Task::byBranch($branch)->overdue()->count(),
            'completed_tasks_today' => Task::byBranch($branch)
                ->where('status', 'completed')
                ->whereDate('completed_at', today())->count(),
            'maintenance_logs' => MaintenanceLog::byBranch($branch)->count(),
            'pending_requests' => MaterialRequest::byBranch($branch)
                ->where('status', 'pending')->count(),
            'customer_feedback_avg' => CustomerFeedback::byBranch($branch)
                ->avg('rating'),
        ];

        $recentTasks = Task::byBranch($branch)
            ->with(['assignedEmployee', 'creator'])
            ->latest()
            ->take(5)
            ->get();

        $recentMaintenance = MaintenanceLog::byBranch($branch)
            ->with('employee')
            ->latest()
            ->take(5)
            ->get();

        $pendingApprovals = MaterialRequest::byBranch($branch)
            ->where('status', 'pending')
            ->with('requestedBy')
            ->latest()
            ->get();

        $tasksByStatus = Task::byBranch($branch)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $costSummary = BranchAnalytics::costSummary($branch);
        $recurringIssues = BranchAnalytics::recurringIssues($branch);
        $topPerformers = BranchAnalytics::personnelPerformance($branch);

        return view('dashboard.admin', compact(
            'stats', 'recentTasks', 'recentMaintenance', 'pendingApprovals', 'tasksByStatus',
            'costSummary', 'recurringIssues', 'topPerformers'
        ));
    }

    private function employeeDashboard($userId, $branch)
    {
        $stats = [
            'assigned_tasks' => Task::assignedTo($userId)->count(),
            'pending_tasks' => Task::assignedTo($userId)->where('status', 'pending')->count(),
            'completed_tasks' => Task::assignedTo($userId)->where('status', 'completed')->count(),
            'overdue_tasks' => Task::assignedTo($userId)->overdue()->count(),
            'maintenance_logs' => MaintenanceLog::byEmployee($userId)->count(),
            'hours_today' => $this->getHoursWorkedToday($userId),
            'avg_rating' => \App\Models\CustomerFeedback::where('employee_id', $userId)->avg('rating'),
        ];

        $myTasks = Task::assignedTo($userId)
            ->with(['creator'])
            ->orderByRaw("CASE
                WHEN status = 'pending' THEN 1
                WHEN status = 'assigned' THEN 2
                WHEN status = 'in_progress' THEN 3
                WHEN status = 'completed' THEN 4
                ELSE 5 END")
            ->orderBy('due_date')
            ->take(10)
            ->get();

        $recentMaintenance = MaintenanceLog::byEmployee($userId)
            ->latest()
            ->take(5)
            ->get();

        $upcomingDeadlines = Task::assignedTo($userId)
            ->where('due_date', '>', now())
            ->where('due_date', '<', now()->addDays(7))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('due_date')
            ->get();

        // * Calculate performance metrics from customer evaluations
        $customerFeedbacks = \App\Models\CustomerFeedback::where('employee_id', $userId)->get();
        
        // Calculate service aspects averages
        $qualityRatings = $customerFeedbacks->whereNotNull('service_aspects')
            ->map(function($feedback) {
                return $feedback->service_aspects['quality'] ?? null;
            })
            ->filter(function($value) {
                return $value !== null;
            });
        
        $timelinessRatings = $customerFeedbacks->whereNotNull('service_aspects')
            ->map(function($feedback) {
                return $feedback->service_aspects['timeliness'] ?? null;
            })
            ->filter(function($value) {
                return $value !== null;
            });
        
        $professionalismRatings = $customerFeedbacks->whereNotNull('service_aspects')
            ->map(function($feedback) {
                return $feedback->service_aspects['professionalism'] ?? null;
            })
            ->filter(function($value) {
                return $value !== null;
            });
        
        $performanceData = [
            'total_evaluations' => $customerFeedbacks->count(),
            'average_rating' => $customerFeedbacks->avg('rating') ?? 0,
            'recommendation_rate' => $customerFeedbacks->where('would_recommend', true)->count(),
            'service_aspects' => [
                'quality' => $qualityRatings->count() > 0 ? $qualityRatings->avg() : 0,
                'timeliness' => $timelinessRatings->count() > 0 ? $timelinessRatings->avg() : 0,
                'professionalism' => $professionalismRatings->count() > 0 ? $professionalismRatings->avg() : 0,
            ],
            'rating_distribution' => [
                5 => $customerFeedbacks->where('rating', 5)->count(),
                4 => $customerFeedbacks->where('rating', 4)->count(),
                3 => $customerFeedbacks->where('rating', 3)->count(),
                2 => $customerFeedbacks->where('rating', 2)->count(),
                1 => $customerFeedbacks->where('rating', 1)->count(),
            ],
        ];

        return view('dashboard.employee', compact(
            'stats', 'myTasks', 'recentMaintenance', 'upcomingDeadlines', 'performanceData'
        ));
    }

    private function getBranchStats($branch)
    {
        return [
            'tasks' => Task::byBranch($branch)->count(),
            'pending' => Task::byBranch($branch)->where('status', 'pending')->count(),
            'employees' => User::byBranch($branch)->where('role', 'employee')->count(),
            'maintenance' => MaintenanceLog::byBranch($branch)->count(),
        ];
    }

    private function getRecentActivity()
    {
        // Simple activity log without external package
        $recentTasks = Task::with(['assignedEmployee', 'creator'])
            ->latest()
            ->take(5)
            ->get();

        $recentLogs = MaintenanceLog::with(['employee'])
            ->latest()
            ->take(5)
            ->get();

        return collect()
            ->concat($recentTasks->map(function ($task) {
                return [
                    'type' => 'task',
                    'message' => "Task '{$task->title}' was " . $task->status,
                    'user' => optional($task->creator)->first_name . ' ' . optional($task->creator)->last_name ?: 'System',
                    'time' => $task->updated_at,
                ];
            }))
            ->concat($recentLogs->map(function ($log) {
                return [
                    'type' => 'maintenance',
                    'message' => "Maintenance log for '{$log->equipment_name}' was created",
                    'user' => optional($log->employee)->first_name . ' ' . optional($log->employee)->last_name,
                    'time' => $log->created_at,
                ];
            }))
            ->sortByDesc('time')
            ->take(10);
    }

    private function getTasksByPriority()
    {
        return Task::selectRaw('priority, count(*) as count')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->groupBy('priority')
            ->pluck('count', 'priority');
    }

    private function getMonthlyStats()
    {
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push([
                'month' => $date->format('M Y'),
                'tasks_completed' => Task::where('status', 'completed')
                    ->whereYear('completed_at', $date->year)
                    ->whereMonth('completed_at', $date->month)
                    ->count(),
                'maintenance_logs' => MaintenanceLog::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ]);
        }
        return $months;
    }

    private function getHoursWorkedToday($userId)
    {
        // This would calculate hours from time logs
        // For now, return a placeholder
        return 0;
    }
}

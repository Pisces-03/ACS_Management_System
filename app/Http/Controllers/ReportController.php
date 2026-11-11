<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\MaintenanceLog;
use App\Models\MaterialRequest;
use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\BranchAnalytics;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $reportType = $request->input('report_type', 'overview');
        $dateFrom = $request->input('date_from', Carbon::now()->subMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->format('Y-m-d'));
        $branch = $request->input('branch');
        $employeeOptions = $this->employeeOptions($user);
        $employeeId = $this->selectedEmployeeId($request, $user, $employeeOptions);

        // Generate overview data
        
        // Generate overview and chart data for the selected date range.
        $periodStart = Carbon::parse($dateFrom)->startOfDay();
        $periodEnd = Carbon::parse($dateTo)->endOfDay();

        if ($periodStart->gt($periodEnd)) {
            [$periodStart, $periodEnd] = [
                Carbon::parse($dateTo)->startOfDay(),
                Carbon::parse($dateFrom)->endOfDay(),
            ];
        }

        // Previous period has the same number of calendar days.
        $periodDays = $periodStart->copy()
            ->startOfDay()
            ->diffInDays($periodEnd->copy()->startOfDay()) + 1;

        $previousStart = $periodStart->copy()->subDays($periodDays);
        $previousEnd = $periodStart->copy()->subSecond();

        // Reusable queries with role and branch restrictions.
        // * $employeeId narrows every query to one selected employee
        $makeTaskQuery = function () use ($user, $branch, $employeeId) {
            return Task::query()
                ->when($user->role === 'admin',
                    fn ($q) => $q->where('branch', $user->branch))
                ->when($user->role === 'employee',
                    fn ($q) => $q->where('assigned_to', $user->id))
                ->when($user->isSuperAdmin() && $branch,
                    fn ($q) => $q->where('branch', $branch))
                ->when($employeeId,
                    fn ($q) => $q->assignedTo($employeeId));
        };

        $makeMaintenanceQuery = function () use ($user, $branch, $employeeId) {
            return MaintenanceLog::query()
                ->when($user->role === 'admin',
                    fn ($q) => $q->where('branch', $user->branch))
                ->when($user->role === 'employee',
                    fn ($q) => $q->where('employee_id', $user->id))
                ->when($user->isSuperAdmin() && $branch,
                    fn ($q) => $q->where('branch', $branch))
                ->when($employeeId,
                    fn ($q) => $q->where('employee_id', $employeeId));
        };

        $makeMaterialQuery = function () use ($user, $branch, $employeeId) {
            return MaterialRequest::query()
                ->when($user->role === 'admin',
                    fn ($q) => $q->where('branch', $user->branch))
                ->when($user->role === 'employee',
                    fn ($q) => $q->where('requested_by', $user->id))
                ->when($user->isSuperAdmin() && $branch,
                    fn ($q) => $q->where('branch', $branch))
                ->when($employeeId,
                    fn ($q) => $q->where('requested_by', $employeeId));
        };

        $makeAttendanceQuery = function () use ($user, $branch, $employeeId) {
            return AttendanceLog::query()
                ->when($user->role === 'admin',
                    fn ($q) => $q->where('branch', $user->branch))
                ->when($user->role === 'employee',
                    fn ($q) => $q->where('user_id', $user->id))
                ->when($user->isSuperAdmin() && $branch,
                    fn ($q) => $q->where('branch', $branch))
                ->when($employeeId,
                    fn ($q) => $q->where('user_id', $employeeId));
        };

        // Counts for the selected period and the previous equal-length period.
        $countCreated = function ($makeQuery, $start, $end) {
            return $makeQuery()
                ->whereBetween('created_at', [$start, $end])
                ->count();
        };

        $tasksThisPeriod = $countCreated(
            $makeTaskQuery, $periodStart, $periodEnd
        );

        $tasksPreviousPeriod = $countCreated(
            $makeTaskQuery, $previousStart, $previousEnd
        );

        $materialsThisPeriod = $countCreated(
            $makeMaterialQuery, $periodStart, $periodEnd
        );

        $materialsPreviousPeriod = $countCreated(
            $makeMaterialQuery, $previousStart, $previousEnd
        );

        // Count completed maintenance using completed_at.
        $maintenanceThisPeriod = $makeMaintenanceQuery()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$periodStart, $periodEnd])
            ->count();

        $maintenancePreviousPeriod = $makeMaintenanceQuery()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$previousStart, $previousEnd])
            ->count();

        // Attendance rate = days employees timed in / days they were expected to work.
        // * Expected days = working days (Mon-Sat) in the period, up to today, from the day
        // * each active employee account was created. A working day with no time-in counts as absent.
        $getAttendanceRate = function ($start, $end) use ($user, $branch, $employeeId) {
            $today = Carbon::now('Asia/Manila')->endOfDay();
            $end = $end->copy()->min($today);
            if ($start->gt($end)) {
                return null;
            }

            $employees = User::query()
                ->where('role', 'employee')
                ->where('is_active', true)
                ->when($user->role === 'admin',
                    fn ($q) => $q->where('branch', $user->branch))
                ->when($user->role === 'employee',
                    fn ($q) => $q->where('id', $user->id))
                ->when($user->isSuperAdmin() && $branch,
                    fn ($q) => $q->where('branch', $branch))
                ->when($employeeId,
                    fn ($q) => $q->where('id', $employeeId))
                ->get(['id', 'created_at']);

            $expectedDays = 0;
            foreach ($employees as $employee) {
                $from = $start->copy()->startOfDay();
                if ($employee->created_at && $employee->created_at->copy()->startOfDay()->gt($from)) {
                    $from = $employee->created_at->copy()->startOfDay();
                }
                for ($day = $from; $day->lte($end); $day->addDay()) {
                    if (!$day->isSunday()) {
                        $expectedDays++;
                    }
                }
            }

            if ($expectedDays === 0) {
                return null;
            }

            $attendedDays = AttendanceLog::query()
                ->whereIn('user_id', $employees->pluck('id'))
                ->whereIn('status', ['present', 'late', 'halfday'])
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get(['user_id', 'date'])
                ->filter(fn ($log) => !Carbon::parse($log->date)->isSunday())
                ->unique(fn ($log) => $log->user_id . '|' . Carbon::parse($log->date)->toDateString())
                ->count();

            return round(min($attendedDays / $expectedDays, 1) * 100, 1);
        };

        $attendanceThisPeriod = $getAttendanceRate(
            $periodStart, $periodEnd
        );

        $attendancePreviousPeriod = $getAttendanceRate(
            $previousStart, $previousEnd
        );

        // Overview cards.
        $totalTasks = $tasksThisPeriod;

        $completedTasks = $makeTaskQuery()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $activeEmployees = User::query()
            ->where('role', 'employee')
            ->where('is_active', true)
            ->when($user->role === 'admin',
                fn ($q) => $q->where('branch', $user->branch))
            ->when($user->role === 'employee',
                fn ($q) => $q->where('id', $user->id))
            ->when($user->isSuperAdmin() && $branch,
                fn ($q) => $q->where('branch', $branch))
            ->when($employeeId,
                fn ($q) => $q->where('id', $employeeId))
            ->count();

        $overview = [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'active_employees' => $activeEmployees,

            // Keep the existing keys so the current Blade view still works.
            // These now represent the selected period and previous period.
            'tasks_this_week' => $tasksThisPeriod,
            'tasks_last_week' => $tasksPreviousPeriod,

            'maintenance_this_week' => $maintenanceThisPeriod,
            'maintenance_last_week' => $maintenancePreviousPeriod,

            'materials_this_week' => $materialsThisPeriod,
            'materials_last_week' => $materialsPreviousPeriod,

            'attendance_this_week' => $attendanceThisPeriod,
            'attendance_last_week' => $attendancePreviousPeriod,

            'tasks_change' => $tasksPreviousPeriod > 0
                ? round(
                    (($tasksThisPeriod - $tasksPreviousPeriod)
                        / $tasksPreviousPeriod) * 100,
                    1
                )
                : null,
        ];

        // Task distribution: tasks created during the selected period.
        $chartTaskQuery = function () use (
            $makeTaskQuery, $periodStart, $periodEnd
        ) {
            return $makeTaskQuery()
                ->whereBetween('created_at', [$periodStart, $periodEnd]);
        };

        $chartData = [
            'tasks' => [
                'completed' => $chartTaskQuery()
                    ->where('status', 'completed')->count(),

                'in_progress' => $chartTaskQuery()
                    ->where('status', 'in_progress')->count(),

                'pending' => $chartTaskQuery()
                    ->where('status', 'pending')->count(),

                'overdue' => $chartTaskQuery()
                    ->where('due_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
            ],

            // Maintenance types created during the selected period.
            // * Keys match the maintenance_logs.type enum ('preventive' does not exist there)
            'maintenance' => collect(['repair', 'installation', 'reinstallation', 'dismantling', 'checkup', 'maintenance_logs'])
                ->mapWithKeys(fn ($type) => [$type => $makeMaintenanceQuery()
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->where('type', $type)->count()])
                ->all(),

            'attendance' => [
                'labels' => [],
                'data' => [],
            ],
        ];

        // Daily attendance totals for the selected date range.
        $dailyAttendance = $makeAttendanceQuery()
            ->whereBetween('date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->selectRaw('DATE(date) AS attendance_date, COUNT(*) AS total')
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->pluck('total', 'attendance_date');

        $day = $periodStart->copy();

        while ($day->lte($periodEnd)) {
            $dateKey = $day->toDateString();

            $chartData['attendance']['labels'][] = $day->format('M j');
            $chartData['attendance']['data'][] =
                (int) ($dailyAttendance[$dateKey] ?? 0);

            $day->addDay();
        }


        // Fetch data based on report type
        $data = [];
        switch ($reportType) {
            case 'maintenance':
                $query = MaintenanceLog::with(['employee']);
                if ($user->role === 'admin') {
                    $query->where('branch', $user->branch);
                } elseif ($user->role === 'employee') {
                    $query->where('employee_id', $user->id);
                }
                if ($dateFrom) {
                    $query->where(function($q) use ($dateFrom) {
                        $q->whereDate('performed_at', '>=', $dateFrom)
                          ->orWhereDate('created_at', '>=', $dateFrom);
                    });
                }
                if ($dateTo) {
                    $query->where(function($q) use ($dateTo) {
                        $q->whereDate('performed_at', '<=', $dateTo)
                          ->orWhereDate('created_at', '<=', $dateTo);
                    });
                }
                if ($branch && $user->isSuperAdmin()) {
                    $query->where('branch', $branch);
                }
                $query->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId));
                $data['maintenanceLogs'] = $query->latest()->get();
                break;

            case 'materials':
                $query = MaterialRequest::with(['requestedBy', 'approvedBy', 'task']);
                if ($user->role === 'admin') {
                    $query->where('branch', $user->branch);
                } elseif ($user->role === 'employee') {
                    $query->where('requested_by', $user->id);
                }
                if ($dateFrom) {
                    $query->whereDate('created_at', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('created_at', '<=', $dateTo);
                }
                if ($branch && $user->isSuperAdmin()) {
                    $query->where('branch', $branch);
                }
                $query->when($employeeId, fn ($q) => $q->where('requested_by', $employeeId));
                $data['materialRequests'] = $query->latest()->get();
                break;

            case 'attendance':
                $query = AttendanceLog::with(['user']);
                if ($user->role === 'admin') {
                    $query->where('branch', $user->branch);
                } elseif ($user->role === 'employee') {
                    $query->where('user_id', $user->id);
                }
                if ($dateFrom) {
                    $query->whereDate('date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('date', '<=', $dateTo);
                }
                if ($branch && $user->isSuperAdmin()) {
                    $query->where('branch', $branch);
                }
                $query->when($employeeId, fn ($q) => $q->where('user_id', $employeeId));
                $data['attendanceLogs'] = $query->orderBy('date', 'desc')->get();
                break;

            case 'employees':
                $query = User::where('role', '!=', 'superadmin');
                if ($user->role === 'admin') {
                    $query->where('branch', $user->branch);
                }
                if ($branch && $user->isSuperAdmin()) {
                    $query->where('branch', $branch);
                }
                $query->when($employeeId, fn ($q) => $q->where('id', $employeeId));
                $data['employees'] = $query->latest()->get();
                break;
        }

        // * Per-branch cost analysis, recurring issues and performance for the overview tab
        if ($reportType === 'overview' && $user->role !== 'employee') {
            $analysisBranch = $user->role === 'admin' ? $user->branch : ($branch ?: null);
            // * One employee's figures stay inside that employee's branch
            if ($employeeId) {
                $analysisBranch = $employeeOptions->firstWhere('id', $employeeId)?->branch ?? $analysisBranch;
            }
            $data['costSummary'] = BranchAnalytics::costSummary($analysisBranch, $periodStart, $periodEnd, $employeeId);
            $data['recurringIssues'] = BranchAnalytics::recurringIssues($analysisBranch, 10, $periodStart, $periodEnd, $employeeId);
            $data['topPerformers'] = BranchAnalytics::personnelPerformance($analysisBranch, 10, $periodStart, $periodEnd, $employeeId);
        }

        return view('reports.index', array_merge([
            'overview' => $overview,
            'chartData' => $chartData,
            'employeeOptions' => $employeeOptions,
            'selectedEmployee' => $employeeId ? $employeeOptions->firstWhere('id', $employeeId) : null,
            'period' => [
                'start' => $periodStart, 'end' => $periodEnd,
                'previous_start' => $previousStart, 'previous_end' => $previousEnd,
            ],
        ], $data));
    }

    // * People the current user may filter reports by
    // * (admins: their branch's employees; superadmin: all employees and branch admins)
    private function employeeOptions($user)
    {
        if ($user->role === 'employee') {
            return collect();
        }

        return User::whereIn('role', $user->isSuperAdmin() ? ['employee', 'admin'] : ['employee'])
            ->when($user->role === 'admin', fn ($q) => $q->where('branch', $user->branch))
            ->orderBy('branch')
            ->orderByRaw("role = 'admin' DESC")
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'branch', 'role', 'is_active']);
    }

    // * The chosen employee id, or null when none/not allowed or outside the chosen branch
    private function selectedEmployeeId(Request $request, $user, $employeeOptions): ?int
    {
        $employee = $employeeOptions->firstWhere('id', (int) $request->input('employee'));
        if (!$employee) {
            return null;
        }
        if ($user->isSuperAdmin() && $request->filled('branch') && $employee->branch !== $request->input('branch')) {
            return null;
        }

        return $employee->id;
    }

    // * The standalone report URLs all live as tabs on the main Reports page
    public function maintenance(Request $request)
    {
        return $this->redirectToReportType($request, 'maintenance');
    }

    public function performance(Request $request)
    {
        return $this->redirectToReportType($request, 'employees');
    }

    public function materials(Request $request)
    {
        return $this->redirectToReportType($request, 'materials');
    }

    public function attendance(Request $request)
    {
        return $this->redirectToReportType($request, 'attendance');
    }

    private function redirectToReportType(Request $request, string $reportType)
    {
        return redirect()->route('reports.index', array_merge(
            $request->only(['date_from', 'date_to', 'branch', 'employee']),
            ['report_type' => $reportType]
        ));
    }

    public function export(Request $request)
    {
        $format = $request->input('export', 'pdf');
        $reportType = $request->input('report_type', 'overview');

        switch ($format) {
            case 'pdf':
                return $this->exportPDF($request, $reportType);
            case 'excel':
                return $this->exportExcel($request, $reportType);
            default:
                return redirect()->back()->with('error', 'Invalid export format');
        }
    }

    private function exportPDF($request, $reportType)
    {
        $user = Auth::user();
        $data = $this->getReportData($request, $reportType);
        $employeeOptions = $this->employeeOptions($user);
        $employeeId = $this->selectedEmployeeId($request, $user, $employeeOptions);

        $html = view('reports.export-pdf', [
            'data' => $data,
            'reportType' => $reportType,
            'dateFrom' => $request->input('date_from'),
            'dateTo' => $request->input('date_to'),
            'branch' => $request->input('branch'),
            'employeeName' => $employeeId ? $employeeOptions->firstWhere('id', $employeeId)?->full_name : null,
        ])->render();

        // Return HTML that can be converted to PDF by browser
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'inline; filename="report_' . $reportType . '_' . date('Y-m-d') . '.html"');
    }

    private function exportExcel($request, $reportType)
    {
        $user = Auth::user();
        $data = $this->getReportData($request, $reportType);

        // Generate CSV (Excel-compatible)
        $csv = $this->generateCSV($data, $reportType);

        $filename = 'report_' . $reportType . '_' . date('Y-m-d') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Transfer-Encoding', 'binary');
    }

    private function getReportData($request, $reportType)
    {
        $user = Auth::user();
        $employeeId = $this->selectedEmployeeId($request, $user, $this->employeeOptions($user));

        $data = $this->queryReportData($request, $reportType, $user);

        if (!$employeeId) {
            return $data;
        }

        // * Keep only the selected employee's rows
        return $data->filter(fn ($row) => match ($reportType) {
            'maintenance' => (int) $row->employee_id === $employeeId,
            'attendance' => (int) $row->user_id === $employeeId,
            'materials' => (int) $row->requested_by === $employeeId,
            'tasks' => $row->isAssignedTo($employeeId),
            'employees' => (int) $row->id === $employeeId,
            default => true,
        })->values();
    }

    private function queryReportData($request, $reportType, $user)
    {
        switch ($reportType) {
            case 'maintenance':
                $query = MaintenanceLog::with(['employee']);
                if ($user->role === 'admin') {
                    $query->byBranch($user->branch);
                }
                if ($request->filled('date_from')) {
                    $query->whereDate('performed_at', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $query->whereDate('performed_at', '<=', $request->date_to);
                }
                if ($request->filled('branch') && $user->isSuperAdmin()) {
                    $query->where('branch', $request->branch);
                }
                return $query->latest()->get();

            case 'attendance':
                $query = AttendanceLog::with(['user']);
                if ($user->role === 'admin') {
                    $query->where('branch', $user->branch);
                }
                if ($request->filled('date_from')) {
                    $query->whereDate('date', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $query->whereDate('date', '<=', $request->date_to);
                }
                if ($request->filled('branch') && $user->isSuperAdmin()) {
                    $query->where('branch', $request->branch);
                }
                if ($request->filled('user_id')) {
                    $query->where('user_id', $request->user_id);
                }
                return $query->orderBy('date', 'desc')->get();

            case 'materials':
                $query = MaterialRequest::with(['requestedBy', 'approvedBy']);
                if ($user->role === 'admin') {
                    $query->where('branch', $user->branch);
                } elseif ($user->role === 'employee') {
                    $query->where('requested_by', $user->id);
                }
                if ($request->filled('date_from')) {
                    $query->whereDate('created_at', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $query->whereDate('created_at', '<=', $request->date_to);
                }
                if ($request->filled('branch') && $user->isSuperAdmin()) {
                    $query->where('branch', $request->branch);
                }
                return $query->latest()->get();

            case 'tasks':
                $query = Task::with(['assignedEmployee', 'creator']);
                if ($user->role === 'admin') {
                    $query->where('branch', $user->branch);
                } elseif ($user->role === 'employee') {
                    $query->where('assigned_to', $user->id);
                }
                if ($request->filled('date_from')) {
                    $query->whereDate('created_at', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $query->whereDate('created_at', '<=', $request->date_to);
                }
                if ($request->filled('branch') && $user->isSuperAdmin()) {
                    $query->where('branch', $request->branch);
                }
                return $query->latest()->get();

            case 'employees':
                $query = User::where('role', '!=', 'superadmin');
                if ($user->role === 'admin') {
                    $query->where('branch', $user->branch);
                }
                if ($request->filled('branch') && $user->isSuperAdmin()) {
                    $query->where('branch', $request->branch);
                }
                return $query->latest()->get();

            default:
                return collect();
        }
    }

    private function generateCSV($data, $reportType)
    {
        $csv = '';
        
        switch ($reportType) {
            case 'maintenance':
                $csv = "Date,Log Number,Employee,Equipment,Type,Status,Description\n";
                foreach ($data as $log) {
                    $csv .= sprintf(
                        "%s,%s,%s,%s,%s,%s,%s\n",
                        $log->performed_at ? $log->performed_at->format('Y-m-d') : ($log->created_at ? $log->created_at->format('Y-m-d') : 'N/A'),
                        $log->log_number ?? 'N/A',
                        $log->employee->full_name ?? 'N/A',
                        $log->equipment_name ?? 'N/A',
                        $log->type ?? 'N/A',
                        $log->status ?? 'N/A',
                        str_replace(['"', "\n", "\r"], ['""', ' ', ' '], $log->description ?? '')
                    );
                }
                break;

            case 'attendance':
                $csv = "Date,Employee,Branch,Time In,Time Out,Break Start,Break End,Total Hours,Status\n";
                foreach ($data as $row) {
                    $csv .= sprintf(
                        "%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                        $row->date ? $row->date->format('Y-m-d') : 'N/A',
                        $row->user ? ($row->user->full_name) : 'Unknown',
                        $row->branch ?? 'N/A',
                        $row->time_in ?? '',
                        $row->time_out ?? '',
                        $row->break_start ?? '',
                        $row->break_end ?? '',
                        $row->total_hours ?? '0',
                        ucfirst($row->status ?? 'present')
                    );
                }
                break;

            case 'materials':
                $csv = "Date,Request Number,Requested By,Status,Priority,Total Cost,Justification\n";
                foreach ($data as $request) {
                    $csv .= sprintf(
                        "%s,%s,%s,%s,%s,%s,%s\n",
                        $request->created_at ? $request->created_at->format('Y-m-d') : 'N/A',
                        $request->request_number ?? 'N/A',
                        $request->requestedBy->full_name ?? 'N/A',
                        $request->status ?? 'N/A',
                        $request->priority ?? 'N/A',
                        // * Actual cost once received, otherwise the estimate (same as the report page)
                        $request->actual_cost ?? $request->total_estimated_cost ?? '0',
                        str_replace(['"', "\n", "\r"], ['""', ' ', ' '], $request->justification ?? '')
                    );
                }
                break;

            case 'tasks':
                $csv = "Date,Task Number,Title,Assigned To,Status,Priority,Due Date\n";
                foreach ($data as $task) {
                    $csv .= sprintf(
                        "%s,%s,%s,%s,%s,%s,%s\n",
                        $task->created_at ? $task->created_at->format('Y-m-d') : 'N/A',
                        $task->task_number ?? 'N/A',
                        str_replace(['"', "\n", "\r"], ['""', ' ', ' '], $task->title ?? 'N/A'),
                        $task->assignedEmployee->full_name ?? 'Unassigned',
                        $task->status ?? 'N/A',
                        $task->priority ?? 'N/A',
                        $task->due_date ? $task->due_date->format('Y-m-d') : 'N/A'
                    );
                }
                break;

            case 'employees':
                $csv = "Employee ID,Name,Email,Role,Branch,Position,Department,Status\n";
                foreach ($data as $employee) {
                    $csv .= sprintf(
                        "%s,%s,%s,%s,%s,%s,%s,%s\n",
                        $employee->employee_id ?? 'N/A',
                        $employee->full_name ?? 'N/A',
                        $employee->email ?? 'N/A',
                        $employee->role ?? 'N/A',
                        $employee->branch_name ?? 'N/A',
                        $employee->position ?? 'N/A',
                        $employee->department ?? 'N/A',
                        $employee->is_active ? 'Active' : 'Inactive'
                    );
                }
                break;
        }

        // Add BOM for UTF-8 to ensure Excel displays correctly
        return "\xEF\xBB\xBF" . $csv;
    }
}

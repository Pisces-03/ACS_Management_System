<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomerFeedback;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CustomerFeedbackReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        $query = CustomerFeedback::with(['employee', 'task.maintenanceLogs', 'maintenanceLog']);
        if ($user->role === 'admin') {
            $query->where('branch', $user->branch);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->date_to);
        }
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('branch') && $user->role === 'superadmin') {
            $query->where('branch', $request->branch);
        }

        $feedback = $query->orderBy('submitted_at', 'desc')->paginate(15)->appends($request->all());

        // Summary stats
        $statsQuery = clone $query;
        $stats = [
            'count' => (clone $statsQuery)->count(),
            'avg' => round((clone $statsQuery)->avg('rating') ?? 0, 2),
            'recommend_percent' => $statsQuery->where('would_recommend', true)->count(),
        ];
        $stats['recommend_percent'] = $stats['count'] > 0 ? round(($stats['recommend_percent'] / $stats['count']) * 100, 1) : 0;

        $employees = User::where('role', 'employee')
            ->when($user->role === 'admin', fn($q) => $q->where('branch', $user->branch))
            ->orderBy('first_name')
            ->get(['id','first_name','last_name']);

        return view('reports.customer-feedback.index', compact('feedback', 'stats', 'employees'));
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        $format = $request->input('export', 'excel');

        $query = CustomerFeedback::with(['employee', 'task.maintenanceLogs', 'maintenanceLog']);
        if ($user->role === 'admin') {
            $query->where('branch', $user->branch);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->date_to);
        }
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('branch') && $user->role === 'superadmin') {
            $query->where('branch', $request->branch);
        }

        $data = $query->orderBy('submitted_at', 'desc')->get();

        if ($format === 'pdf') {
            $html = view('reports.export-pdf', [
                'reportType' => 'customer_feedback',
                'data' => $data,
                'dateFrom' => $request->input('date_from'),
                'dateTo' => $request->input('date_to'),
                'branch' => $request->input('branch'),
            ])->render();
            return response($html)->header('Content-Type', 'text/html');
        }

        // CSV
        $csv = "Date,Employee,Branch,Task #,Log #,Rating,Recommend,Comments\n";
        foreach ($data as $row) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s\n",
                $row->submitted_at ? $row->submitted_at->format('Y-m-d') : 'N/A',
                $row->employee ? $row->employee->full_name : 'Unknown',
                $row->branch,
                $row->task->task_number ?? '',
                $row->linked_log->log_number ?? '',
                $row->rating,
                $row->would_recommend ? 'Yes' : 'No',
                str_replace(['"',"\n","\r"],['""',' ',' '], $row->feedback ?? '')
            );
        }
        return response("\xEF\xBB\xBF".$csv)->header('Content-Type','text/csv; charset=UTF-8');
    }
}



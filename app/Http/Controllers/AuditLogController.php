<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        $query = AuditLog::with('user');

        // Filter based on user role
        if ($user->role === 'admin') {
            $query->whereHas('user', function($q) use ($user) {
                $q->where('branch', $user->branch);
            });
        }

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where('description', 'LIKE', '%' . $request->search . '%');
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get users for filter dropdown
        $users = User::when($user->role === 'admin', function($q) use ($user) {
            $q->where('branch', $user->branch);
        })->orderBy('first_name')->get();

        // Get available actions and model types
        $actions = AuditLog::distinct('action')->pluck('action');
        $modelTypes = AuditLog::distinct('model_type')->whereNotNull('model_type')->pluck('model_type');

        return view('audit-logs.index', compact('auditLogs', 'users', 'actions', 'modelTypes'));
    }

    public function show(Request $request, AuditLog $auditLog)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        // Check if admin can view this log (same branch)
        if ($user->role === 'admin' && $auditLog->user?->branch !== $user->branch) {
            abort(403);
        }

        // * The details modal on the list page requests JSON
        if ($request->expectsJson()) {
            $auditLog->load('user');

            return response()->json([
                'id' => $auditLog->id,
                'user' => $auditLog->user ? [
                    'full_name' => $auditLog->user->full_name,
                    'email' => $auditLog->user->email,
                    'role' => $auditLog->user->role,
                ] : null,
                'action' => ucwords(str_replace('_', ' ', $auditLog->action)),
                'model_type' => $auditLog->model_type ? class_basename($auditLog->model_type) : null,
                'model_id' => $auditLog->model_id,
                'description' => $auditLog->description,
                'old_values' => $auditLog->old_values,
                'new_values' => $auditLog->new_values,
                'ip_address' => $auditLog->ip_address,
                'user_agent' => $auditLog->user_agent,
                'created_at' => $auditLog->created_at?->format('M d, Y h:i:s A'),
            ]);
        }

        return view('audit-logs.show', compact('auditLog'));
    }

    public function export(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'superadmin') {
            abort(403);
        }

        $query = AuditLog::with('user');

        // Apply same filters as index
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->get();

        $filename = 'audit_logs_' . now()->format('Y_m_d_H_i_s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($auditLogs) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'Date/Time',
                'User',
                'Action',
                'Model Type',
                'Model ID',
                'Description',
                'IP Address'
            ]);

            foreach ($auditLogs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user ? ($log->user->first_name . ' ' . $log->user->last_name) : 'Unknown',
                    $log->action,
                    $log->model_type,
                    $log->model_id,
                    $log->description,
                    $log->ip_address
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function search(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        $query = $request->get('q');

        if (empty($query)) {
            return response()->json(['results' => []]);
        }

        $logsQuery = AuditLog::with('user')
            ->where('description', 'LIKE', '%' . $query . '%');

        // Filter based on user role
        if ($user->role === 'admin') {
            $logsQuery->whereHas('user', function($q) use ($user) {
                $q->where('branch', $user->branch);
            });
        }

        $logs = $logsQuery->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $results = $logs->map(function($log) {
            return [
                'id' => $log->id,
                'text' => $log->description,
                'user' => $log->user ? ($log->user->first_name . ' ' . $log->user->last_name) : 'Unknown',
                'date' => $log->created_at->format('Y-m-d H:i:s'),
                'action' => $log->action
            ];
        });

        return response()->json(['results' => $results]);
    }
}

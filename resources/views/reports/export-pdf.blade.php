<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Report - {{ ucfirst($reportType) }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header-info { margin-bottom: 20px; }
        .header-info p { margin: 5px 0; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <h1>{{ ucfirst($reportType) }} Report</h1>
    
    <div class="header-info">
        <p><strong>Generated:</strong> {{ now()->format('F d, Y H:i:s') }}</p>
        @if($dateFrom || $dateTo)
        <p><strong>Date Range:</strong> {{ $dateFrom ?? 'N/A' }} to {{ $dateTo ?? 'N/A' }}</p>
        @endif
        @if($branch)
        <p><strong>Branch:</strong> {{ ucfirst(str_replace('_', ' ', $branch)) }}</p>
        @endif
        @if(!empty($employeeName))
        <p><strong>Employee:</strong> {{ $employeeName }}</p>
        @endif
    </div>

    @if($reportType === 'maintenance')
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Log Number</th>
                <th>Employee</th>
                <th>Equipment</th>
                <th>Type</th>
                <th>Status</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $log)
            <tr>
                <td>{{ $log->performed_at ? $log->performed_at->format('Y-m-d') : ($log->created_at ? $log->created_at->format('Y-m-d') : 'N/A') }}</td>
                <td>{{ $log->log_number ?? 'N/A' }}</td>
                <td>{{ $log->employee->full_name ?? 'N/A' }}</td>
                <td>{{ $log->equipment_name ?? 'N/A' }}</td>
                <td>{{ ucfirst($log->type ?? 'N/A') }}</td>
                <td>{{ ucfirst($log->status ?? 'N/A') }}</td>
                <td>{{ $log->description ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr><td colspan="7">No data available</td></tr>
            @endforelse
        </tbody>
    </table>
    @elseif($reportType === 'materials')
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Request Number</th>
                <th>Requested By</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Total Cost</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $request)
            <tr>
                <td>{{ $request->created_at ? $request->created_at->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ $request->request_number ?? 'N/A' }}</td>
                <td>{{ $request->requestedBy->full_name ?? 'N/A' }}</td>
                <td>{{ ucfirst($request->status ?? 'N/A') }}</td>
                <td>{{ ucfirst($request->priority ?? 'N/A') }}</td>
                <td>₱{{ number_format($request->actual_cost ?? $request->total_estimated_cost ?? 0, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="6">No data available</td></tr>
            @endforelse
        </tbody>
    </table>
    @elseif($reportType === 'tasks')
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Task Number</th>
                <th>Title</th>
                <th>Assigned To</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Due Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $task)
            <tr>
                <td>{{ $task->created_at ? $task->created_at->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ $task->task_number ?? 'N/A' }}</td>
                <td>{{ $task->title ?? 'N/A' }}</td>
                <td>{{ $task->assignedEmployee->full_name ?? 'Unassigned' }}</td>
                <td>{{ ucfirst($task->status ?? 'N/A') }}</td>
                <td>{{ ucfirst($task->priority ?? 'N/A') }}</td>
                <td>{{ $task->due_date ? $task->due_date->format('Y-m-d') : 'N/A' }}</td>
            </tr>
            @empty
            <tr><td colspan="7">No data available</td></tr>
            @endforelse
        </tbody>
    </table>
    @elseif($reportType === 'attendance')
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Employee</th>
                <th>Branch</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Break Start</th>
                <th>Break End</th>
                <th>Total Hours</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
            <tr>
                <td>{{ $row->date ? $row->date->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ $row->user ? $row->user->full_name : 'Unknown' }}</td>
                <td>{{ ucfirst(str_replace('_',' ', $row->branch ?? '')) }}</td>
                <td>{{ $row->time_in ?? '' }}</td>
                <td>{{ $row->time_out ?? '' }}</td>
                <td>{{ $row->break_start ?? '' }}</td>
                <td>{{ $row->break_end ?? '' }}</td>
                <td>{{ number_format((float)($row->total_hours ?? 0), 2) }}</td>
                <td>{{ ucfirst($row->status ?? 'present') }}</td>
            </tr>
            @empty
            <tr><td colspan="9">No data available</td></tr>
            @endforelse
        </tbody>
    </table>
    @elseif($reportType === 'employees')
    <table>
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Branch</th>
                <th>Position</th>
                <th>Department</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $employee)
            <tr>
                <td>{{ $employee->employee_id ?? 'N/A' }}</td>
                <td>{{ $employee->full_name ?? 'N/A' }}</td>
                <td>{{ $employee->email ?? 'N/A' }}</td>
                <td>{{ ucfirst($employee->role ?? 'N/A') }}</td>
                <td>{{ $employee->branch_name ?? 'N/A' }}</td>
                <td>{{ $employee->position ?? 'N/A' }}</td>
                <td>{{ $employee->department ?? 'N/A' }}</td>
                <td>{{ $employee->is_active ? 'Active' : 'Inactive' }}</td>
            </tr>
            @empty
            <tr><td colspan="8">No data available</td></tr>
            @endforelse
        </tbody>
    </table>
    @elseif($reportType === 'customer_feedback')
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Employee</th>
                <th>Branch</th>
                <th>Task #</th>
                <th>Log #</th>
                <th>Rating</th>
                <th>Recommend</th>
                <th>Comments</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
            <tr>
                <td>{{ $row->submitted_at ? $row->submitted_at->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ $row->employee ? $row->employee->full_name : 'Unknown' }}</td>
                <td>{{ ucfirst(str_replace('_',' ', $row->branch ?? '')) }}</td>
                <td>{{ $row->task->task_number ?? '-' }}</td>
                <td>{{ $row->linked_log->log_number ?? '-' }}</td>
                <td>{{ $row->rating }}</td>
                <td>{{ $row->would_recommend ? 'Yes' : 'No' }}</td>
                <td>{{ $row->feedback ?? '' }}</td>
            </tr>
            @empty
            <tr><td colspan="8">No data available</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>




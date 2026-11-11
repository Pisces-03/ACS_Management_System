@extends('layouts.app')

@section('title', 'Reports & Analytics')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <h2 class="mb-0"><i class="bi bi-graph-up"></i> Reports & Analytics</h2>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary" onclick="exportReport('pdf')">
                        <i class="bi bi-file-earmark-pdf"></i> Export PDF
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="exportReport('excel')">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </button>
                </div>
            </div>

            <!-- Report Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Report Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-lg-2 col-md-6 col-sm-12 col-12">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select name="report_type" id="report_type" class="form-select">
                                <option value="overview" {{ request('report_type') == 'overview' ? 'selected' : '' }}>System Overview</option>
                                <option value="maintenance" {{ request('report_type') == 'maintenance' ? 'selected' : '' }}>Maintenance Report</option>
                                <option value="materials" {{ request('report_type') == 'materials' ? 'selected' : '' }}>Material Requests</option>
                                <option value="attendance" {{ request('report_type') == 'attendance' ? 'selected' : '' }}>Attendance Report</option>
                                <option value="employees" {{ request('report_type') == 'employees' ? 'selected' : '' }}>Employee Performance</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from', now()->subMonth()->format('Y-m-d')) }}" max="{{ request('date_to', now()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to', now()->format('Y-m-d')) }}" min="{{ request('date_from', now()->subMonth()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                            <label for="branch" class="form-label">Branch</label>
                            @if(auth()->user()->isSuperAdmin())
                                <select name="branch" id="branch" class="form-select">
                                    <option value="">All Branches</option>
                                    <option value="cebu" {{ request('branch') == 'cebu' ? 'selected' : '' }}>Cebu</option>
                                    <option value="las_pinas" {{ request('branch') == 'las_pinas' ? 'selected' : '' }}>Las Piñas</option>
                                    <option value="davao" {{ request('branch') == 'davao' ? 'selected' : '' }}>Davao</option>
                                </select>
                            @else
                                {{-- * Admins and employees only see their own branch: fixed text, no dropdown --}}
                                <input type="text" id="branch" class="form-control-plaintext fw-semibold" readonly
                                       value="{{ ['las_pinas' => 'Las Piñas', 'cebu' => 'Cebu', 'davao' => 'Davao'][auth()->user()->branch] ?? ucfirst(auth()->user()->branch) }}"
                                       title="You can only view your own branch">
                            @endif
                        </div>
                        @if(!empty($employeeOptions) && $employeeOptions->isNotEmpty())
                        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                            <label for="employee" class="form-label">{{ auth()->user()->isSuperAdmin() ? 'Employee / Admin' : 'Employee' }}</label>
                            {{-- * Superadmin: options outside the chosen branch are hidden by the script below --}}
                            <select name="employee" id="employee" class="form-select">
                                <option value="">All Employees</option>
                                @foreach($employeeOptions as $option)
                                    <option value="{{ $option->id }}" data-branch="{{ $option->branch }}"
                                        {{ (int) request('employee') === $option->id ? 'selected' : '' }}>
                                        {{ $option->full_name }}{{ auth()->user()->isSuperAdmin() ? ' (' . (['las_pinas' => 'Las Piñas', 'cebu' => 'Cebu', 'davao' => 'Davao'][$option->branch] ?? $option->branch) . ')' : '' }}{{ $option->role === 'admin' ? ' – Admin' : '' }}{{ $option->is_active ? '' : ' – inactive' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-lg-2 col-md-6 col-sm-6 col-12">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                    @isset($period)
                        <p class="text-muted small mb-0 mt-3">
                            <i class="bi bi-calendar-range"></i>
                            Showing <strong>{{ $period['start']->format('M d, Y') }} – {{ $period['end']->format('M d, Y') }}</strong>{!! !empty($selectedEmployee) ? ' for <strong>' . e($selectedEmployee->full_name) . '</strong> only' : '' !!}.
                            "Previous Period" is the same number of days just before: {{ $period['previous_start']->format('M d') }} – {{ $period['previous_end']->format('M d, Y') }}.
                        </p>
                    @endisset
                </div>
            </div>

            <!-- Report Content Based on Type -->
            @php
                $reportType = request('report_type', 'overview');
            @endphp

            @if($reportType === 'overview')
                @include('reports.partials.overview', ['overview' => $overview ?? [], 'chartData' => $chartData ?? []])
                @include('reports.partials.branch-analysis')
            @elseif($reportType === 'maintenance')
                @include('reports.partials.maintenance', ['maintenanceLogs' => $maintenanceLogs ?? collect()])
            @elseif($reportType === 'materials')
                @include('reports.partials.materials', ['materialRequests' => $materialRequests ?? collect()])
            @elseif($reportType === 'attendance')
                @include('reports.partials.attendance', ['attendanceLogs' => $attendanceLogs ?? collect()])
            @elseif($reportType === 'employees')
                @include('reports.partials.employees', ['employees' => $employees ?? collect()])
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function exportReport(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);

    const url = `/reports/export?${params.toString()}`;
    window.open(url, '_blank');
}

// Initialize charts if they exist
document.addEventListener('DOMContentLoaded', function() {
    // * Superadmin: only list employees of the chosen branch
    const branchSelect = document.querySelector('select[name="branch"]');
    const employeeSelect = document.getElementById('employee');
    if (branchSelect && employeeSelect) {
        const filterEmployees = () => {
            Array.from(employeeSelect.options).forEach(option => {
                if (!option.value) return;
                option.hidden = !!branchSelect.value && option.dataset.branch !== branchSelect.value;
            });
            if (employeeSelect.selectedOptions[0]?.hidden) employeeSelect.value = '';
        };
        branchSelect.addEventListener('change', filterEmployees);
        filterEmployees();
    }

    // * Keep From Date on or before To Date
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    if (dateFrom && dateTo) {
        dateFrom.addEventListener('change', () => { dateTo.min = dateFrom.value; });
        dateTo.addEventListener('change', () => { dateFrom.max = dateTo.value; });
    }

    // Task Status Chart
    const taskCtx = document.getElementById('taskChart');
    if (taskCtx) {
        new Chart(taskCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Pending', 'Overdue'],
                datasets: [{
                    data: [
                        {{ $chartData['tasks']['completed'] ?? 0 }},
                        {{ $chartData['tasks']['in_progress'] ?? 0 }},
                        {{ $chartData['tasks']['pending'] ?? 0 }},
                        {{ $chartData['tasks']['overdue'] ?? 0 }}
                    ],
                    backgroundColor: [
                        '#198754',
                        '#0d6efd',
                        '#ffc107',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Maintenance Chart
    const maintenanceCtx = document.getElementById('maintenanceChart');
    if (maintenanceCtx) {
        new Chart(maintenanceCtx, {
            type: 'line',
            data: {
                labels: ['Repair', 'Installation', 'Re-installation', 'Dismantling', 'Check-up', 'Maintenance Logs'],
                datasets: [{
                    label: 'Maintenance Logs',
                    data: [
                        {{ $chartData['maintenance']['repair'] ?? 0 }},
                        {{ $chartData['maintenance']['installation'] ?? 0 }},
                        {{ $chartData['maintenance']['reinstallation'] ?? 0 }},
                        {{ $chartData['maintenance']['dismantling'] ?? 0 }},
                        {{ $chartData['maintenance']['checkup'] ?? 0 }},
                        {{ $chartData['maintenance']['maintenance_logs'] ?? 0 }}
                    ],
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    // * Whole numbers only - you can't have 1.5 logs
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // Attendance Chart
    const attendanceCtx = document.getElementById('attendanceChart');
    if (attendanceCtx) {
        new Chart(attendanceCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartData['attendance']['labels'] ?? []) !!},
                datasets: [{
                    label: 'Daily Attendance',
                    data: {!! json_encode($chartData['attendance']['data'] ?? []) !!},
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>
@endpush

<!-- Attendance Report Section -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-calendar-check"></i> Attendance Report</h6>
    </div>
    <div class="card-body">
        @php
            // * Summary moved here from the old Attendance > Generate Report page
            $workedLogs = $attendanceLogs->whereNotNull('time_in');
            $hoursLogs = $attendanceLogs->whereNotNull('total_hours');
            $attendanceSummary = [
                ['Total Records', $attendanceLogs->count(), 'bi-calendar3', 'bg-primary'],
                ['Days Worked', $workedLogs->count(), 'bi-check-circle', 'bg-success'],
                ['Total Hours', number_format($hoursLogs->sum('total_hours'), 2), 'bi-clock', 'bg-info'],
                ['Avg Hours/Day', number_format($hoursLogs->count() ? $hoursLogs->avg('total_hours') : 0, 2), 'bi-bar-chart', 'bg-warning'],
            ];
        @endphp
        <div class="row g-3 mb-4">
            @foreach($attendanceSummary as [$label, $value, $icon, $color])
                <div class="col-lg-3 col-6">
                    <div class="card text-white {{ $color }} h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title mb-0">{{ $value }}</h4>
                                <p class="card-text mb-0">{{ $label }}</p>
                            </div>
                            <i class="bi {{ $icon }} fs-3"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($attendanceLogs->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Branch</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Total Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendanceLogs as $log)
                    <tr>
                        <td>{{ $log->date ? $log->date->format('M d, Y') : 'N/A' }}</td>
                        <td>
                            {{ $log->user->full_name ?? 'Unknown' }}
                            @if(($log->user->role ?? null) === 'admin')
                                <span class="badge bg-secondary">Admin</span>
                            @endif
                        </td>
                        <td>{{ $log->branch_name ?? ucfirst(str_replace('_', ' ', $log->branch ?? 'N/A')) }}</td>
                        <td>{{ $log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('h:i A') : '--:--' }}</td>
                        <td>{{ $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('h:i A') : '--:--' }}</td>
                        <td>{{ $log->total_hours !== null ? number_format($log->total_hours, 2) . ' hrs' : '--' }}</td>
                        <td>
                            <span class="badge bg-{{ $log->status === 'present' ? 'success' : ($log->status === 'absent' ? 'danger' : 'warning') }}">
                                {{ ucfirst($log->status ?? 'N/A') }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5">
            <i class="bi bi-calendar-check h1 text-muted"></i>
            <h4 class="text-muted">No Attendance Records Found</h4>
            <p class="text-muted">No attendance records found for the selected date range.</p>
        </div>
        @endif
    </div>
</div>


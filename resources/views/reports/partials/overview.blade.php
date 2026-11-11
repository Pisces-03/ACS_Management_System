
<!-- System Overview Report -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <h5>Total Tasks</h5>
                <h3>{{ $overview['total_tasks'] ?? 0 }}</h3>
                <small>Tasks created in selected period</small>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h5>Completed Tasks</h5>
                <h3>{{ $overview['completed_tasks'] ?? 0 }}</h3>
                @php
                    $totalTasks = $overview['total_tasks'] ?? 0;
                    $completedTasks = $overview['completed_tasks'] ?? 0;
                    $completionRate = $totalTasks > 0
                        ? ($completedTasks / $totalTasks) * 100
                        : 0;
                @endphp
                <small>{{ number_format($completionRate, 1) }}% completion rate</small>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <h5>Active Employees</h5>
                <h3>{{ $overview['active_employees'] ?? 0 }}</h3>
                <small>Currently active accounts</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5>Task Distribution</h5>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="taskChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5>Maintenance Overview</h5>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="maintenanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $activityRows = [
        [
            'label' => 'Tasks Created',
            'current' => $overview['tasks_this_week'] ?? 0,
            'previous' => $overview['tasks_last_week'] ?? 0,
            'type' => 'count',
        ],
        [
            'label' => 'Maintenance Completed',
            'current' => $overview['maintenance_this_week'] ?? 0,
            'previous' => $overview['maintenance_last_week'] ?? 0,
            'type' => 'count',
        ],
        [
            'label' => 'Material Requests',
            'current' => $overview['materials_this_week'] ?? 0,
            'previous' => $overview['materials_last_week'] ?? 0,
            'type' => 'count',
        ],
        [
            'label' => 'Attendance Rate',
            'current' => $overview['attendance_this_week'] ?? null,
            'previous' => $overview['attendance_last_week'] ?? null,
            'type' => 'rate',
        ],
    ];
@endphp

<div class="row mt-4" >
    <div class="col-md-12 ">
        <div class="card">
            <div class="card-header">
                <h5>Recent Activity Summary</h5>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped"> 
                        <thead>
                            <tr>
                                <th>Category</th>  
                                <th>Selected Period</th>
                                <th>Previous Period</th>
                                <th>Change</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($activityRows as $row)
                                @php
                                    $current = $row['current'];
                                    $previous = $row['previous'];
                                    $change = null;
                                    $changeText = 'N/A';

                                    if ($current !== null && $previous !== null) {
                                        if ($row['type'] === 'rate') {
                                            // Attendance is a percentage:
                                            // show percentage-point difference.
                                            $change = $current - $previous;
                                            $changeText =
                                                ($change > 0 ? '+' : '')
                                                . number_format($change, 1) . ' pp';
                                        } else {
                                            $change = $current - $previous;

                                            if ($previous == 0) {
                                                $changeText = $current > 0
                                                    ? 'New'
                                                    : '0.0%';
                                            } else {
                                                $percentage =
                                                    ($change / $previous) * 100;

                                                $changeText =
                                                    ($percentage > 0 ? '+' : '')
                                                    . number_format($percentage, 1) . '%';
                                            }
                                        }
                                    }

                                    $isUp = $change !== null && $change >= 0;
                                @endphp

                                <tr>
                                    <td>{{ $row['label'] }}</td>

                                    <td>
                                        @if ($current === null)
                                            N/A
                                        @elseif ($row['type'] === 'rate')
                                            {{ number_format($current, 1) }}%
                                        @else
                                            {{ $current }}
                                        @endif
                                    </td>

                                    <td>
                                        @if ($previous === null)
                                            N/A
                                        @elseif ($row['type'] === 'rate')
                                            {{ number_format($previous, 1) }}%
                                        @else
                                            {{ $previous }}
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge bg-{{ $change === null ? 'secondary' : ($isUp ? 'success' : 'danger') }}">
                                            {{ $changeText }}
                                        </span>
                                    </td>

                                    <td>
                                        @if ($change === null)
                                            <span class="text-muted">—</span>
                                        @else
                                            <i class="bi bi-{{ $isUp ? 'arrow-up' : 'arrow-down' }} text-{{ $isUp ? 'success' : 'danger' }}"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">
                    Attendance Rate = days employees timed in ÷ working days (Monday–Saturday, up to today) for every active employee.
                    A working day with no time-in counts as absent. "pp" = percentage points.
                </small>
            </div>
        </div>
    </div>
</div>

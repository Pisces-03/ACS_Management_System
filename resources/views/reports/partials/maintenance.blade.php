<!-- Maintenance Report Section -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-tools"></i> Maintenance Report</h6>
    </div>
    <div class="card-body">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $maintenanceLogs->count() }}</h4>
                                <p class="card-text">Total Logs</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-tools fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $maintenanceLogs->where('status', 'completed')->count() }}</h4>
                                <p class="card-text">Completed</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $maintenanceLogs->where('status', 'in_progress')->count() }}</h4>
                                <p class="card-text">In Progress</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-gear fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $maintenanceLogs->where('type', 'checkup')->count() }}</h4>
                                <p class="card-text">Check-ups</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-shield-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Charts -->
        <div class="row mb-4">
            <div class="col-md-6">
                <canvas id="maintenanceTypeChart" width="400" height="200"></canvas>
            </div>
            <div class="col-md-6">
                <canvas id="maintenanceStatusChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Maintenance Table -->
        @if($maintenanceLogs->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Log #</th>
                        <th>Equipment</th>
                        <th>Type</th>
                        <th>Technician</th>
                        <th>Status</th>
                        <th>Repair Cost</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($maintenanceLogs->take(10) as $log)
                    <tr>
                        <td>
                            <strong>{{ $log->log_number }}</strong>
                        </td>
                        <td>
                            <strong>{{ $log->equipment_name ?: 'N/A' }}</strong><br>
                            <small class="text-muted">{{ collect([$log->equipment_model, $log->equipment_serial ? 'S/N '.$log->equipment_serial : null])->filter()->join(' · ') }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $log->type == 'checkup' ? 'info' : ($log->type == 'repair' ? 'warning' : 'secondary') }}">
                                {{ $log->type_name }}
                            </span>
                        </td>
                        <td>
                            @if($log->employee)
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <span class="text-white fw-bold">{{ substr($log->employee->first_name, 0, 1) }}{{ substr($log->employee->last_name, 0, 1) }}</span>
                                </div>
                                <div>
                                    <strong>{{ $log->employee->full_name }}</strong>
                                </div>
                            </div>
                            @else
                            <span class="text-muted">Not assigned</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $log->status == 'completed' ? 'success' : ($log->status == 'in_progress' ? 'warning' : 'secondary') }}">
                                {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                            </span>
                        </td>
                        <td>
                            {{ $log->repair_cost !== null ? '₱'.number_format($log->repair_cost, 2) : '—' }}
                        </td>
                        <td>
                            <small>{{ $log->created_at->format('M d, Y') }}</small>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($maintenanceLogs->count() > 10)
        <div class="text-center mt-3">
            <small class="text-muted">Showing first 10 maintenance logs. Total: {{ $maintenanceLogs->count() }} logs</small>
        </div>
        @endif

        @else
        <div class="text-center py-5">
            <i class="bi bi-tools h1 text-muted"></i>
            <h4 class="text-muted">No Maintenance Logs Found</h4>
            <p class="text-muted">No maintenance logs found for the selected date range.</p>
        </div>
        @endif
    </div>
</div>

<script>
// Maintenance Type Chart
const maintenanceTypeCtx = document.getElementById('maintenanceTypeChart').getContext('2d');
const maintenanceTypeChart = new Chart(maintenanceTypeCtx, {
    type: 'doughnut',
    data: {
        // * Keys match the maintenance_logs.type enum
        labels: ['Repair', 'Installation', 'Re-installation', 'Dismantling', 'Check-up', 'Maintenance Logs'],
        datasets: [{
            data: [
                @foreach(['repair', 'installation', 'reinstallation', 'dismantling', 'checkup', 'maintenance_logs'] as $type)
                {{ $maintenanceLogs->where('type', $type)->count() }},
                @endforeach
            ],
            backgroundColor: ['#ffc107', '#198754', '#20c997', '#dc3545', '#0dcaf0', '#6c757d'],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            },
            title: {
                display: true,
                text: 'Maintenance Type Distribution'
            }
        }
    }
});

// Maintenance Status Chart
const maintenanceStatusCtx = document.getElementById('maintenanceStatusChart').getContext('2d');
const maintenanceStatusChart = new Chart(maintenanceStatusCtx, {
    type: 'bar',
    data: {
        labels: ['Pending', 'In Progress', 'Completed'],
        datasets: [{
            label: 'Number of Logs',
            data: [
                {{ $maintenanceLogs->where('status', 'pending')->count() }},
                {{ $maintenanceLogs->where('status', 'in_progress')->count() }},
                {{ $maintenanceLogs->where('status', 'completed')->count() }}
            ],
            backgroundColor: ['#6c757d', '#ffc107', '#198754'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Maintenance Status Distribution'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

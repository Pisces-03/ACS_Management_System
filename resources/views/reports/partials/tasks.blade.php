<!-- Tasks Report Section -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-list-task"></i> Tasks Report</h6>
    </div>
    <div class="card-body">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $tasks->count() }}</h4>
                                <p class="card-text">Total Tasks</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-list-task fa-2x"></i>
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
                                <h4 class="card-title">{{ $tasks->where('status', 'completed')->count() }}</h4>
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
                                <h4 class="card-title">{{ $tasks->where('status', 'in_progress')->count() }}</h4>
                                <p class="card-text">In Progress</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $tasks->where('due_date', '<', now())->where('status', '!=', 'completed')->count() }}</h4>
                                <p class="card-text">Overdue</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-exclamation-triangle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Status Chart -->
        <div class="row mb-4">
            <div class="col-md-6">
                <canvas id="taskStatusChart" width="400" height="200"></canvas>
            </div>
            <div class="col-md-6">
                <canvas id="taskPriorityChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Tasks Table -->
        @if($tasks->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Task</th>
                        <th>Assigned To</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks->take(10) as $task)
                    <tr>
                        <td>
                            <strong>{{ $task->title }}</strong><br>
                            <small class="text-muted">{{ Str::limit($task->description, 50) }}</small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <span class="text-white fw-bold">{{ substr($task->assignedTo->first_name, 0, 1) }}{{ substr($task->assignedTo->last_name, 0, 1) }}</span>
                                </div>
                                <div>
                                    <strong>{{ $task->assignedTo->full_name }}</strong><br>
                                    <small class="text-muted">{{ $task->assignedTo->position ?? 'Employee' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $task->priority == 'urgent' ? 'danger' : ($task->priority == 'high' ? 'warning' : ($task->priority == 'medium' ? 'info' : 'success')) }}">
                                {{ ucfirst($task->priority) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $task->status == 'completed' ? 'success' : ($task->status == 'in_progress' ? 'warning' : 'secondary') }}">
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                            </span>
                        </td>
                        <td>
                            @if($task->due_date)
                                @if($task->due_date->isPast() && $task->status != 'completed')
                                    <span class="text-danger">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        {{ $task->due_date->format('M d, Y') }}
                                    </span>
                                @else
                                    {{ $task->due_date->format('M d, Y') }}
                                @endif
                            @else
                                <span class="text-muted">No due date</span>
                            @endif
                        </td>
                        <td>
                            <small>{{ $task->created_at->diffForHumans() }}</small>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($tasks->count() > 10)
        <div class="text-center mt-3">
            <small class="text-muted">Showing first 10 tasks. Total: {{ $tasks->count() }} tasks</small>
        </div>
        @endif

        @else
        <div class="text-center py-5">
            <i class="bi bi-list-task h1 text-muted"></i>
            <h4 class="text-muted">No Tasks Found</h4>
            <p class="text-muted">No tasks found for the selected date range.</p>
        </div>
        @endif
    </div>
</div>

<script>
// Task Status Chart
const taskStatusCtx = document.getElementById('taskStatusChart').getContext('2d');
const taskStatusChart = new Chart(taskStatusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Completed', 'In Progress', 'Pending'],
        datasets: [{
            data: [
                {{ $tasks->where('status', 'completed')->count() }},
                {{ $tasks->where('status', 'in_progress')->count() }},
                {{ $tasks->where('status', 'pending')->count() }}
            ],
            backgroundColor: ['#198754', '#ffc107', '#6c757d'],
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
                text: 'Task Status Distribution'
            }
        }
    }
});

// Task Priority Chart
const taskPriorityCtx = document.getElementById('taskPriorityChart').getContext('2d');
const taskPriorityChart = new Chart(taskPriorityCtx, {
    type: 'bar',
    data: {
        labels: ['Low', 'Medium', 'High', 'Urgent'],
        datasets: [{
            label: 'Number of Tasks',
            data: [
                {{ $tasks->where('priority', 'low')->count() }},
                {{ $tasks->where('priority', 'medium')->count() }},
                {{ $tasks->where('priority', 'high')->count() }},
                {{ $tasks->where('priority', 'urgent')->count() }}
            ],
            backgroundColor: ['#198754', '#0dcaf0', '#ffc107', '#dc3545'],
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
                text: 'Task Priority Distribution'
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

@extends('layouts.app')

@section('title', 'Employee Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Welcome, {{ Auth::user()->first_name }}!</h1>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">{{ $stats['assigned_tasks'] }}</h4>
                            <p class="card-text">Assigned Tasks</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-tasks fa-2x"></i>
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
                            <h4 class="card-title">{{ $stats['pending_tasks'] }}</h4>
                            <p class="card-text">Pending Tasks</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
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
                            <h4 class="card-title">{{ $stats['completed_tasks'] }}</h4>
                            <p class="card-text">Completed Tasks</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check fa-2x"></i>
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
                            <h4 class="card-title">{{ $stats['overdue_tasks'] }}</h4>
                            <p class="card-text">Overdue Tasks</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success btn-lg" id="quickTimeInBtn">
                                    <i class="bi bi-clock-history"></i> Time In
                                </button>
                                <button type="button" class="btn btn-light btn-lg" id="quickTimeOutBtn" disabled>
                                    <i class="bi bi-clock text-muted"></i> <span class="text-muted">Time Out (Time In First)</span>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="quickAttendanceStatus">
                                <h6 class="text-muted">Today's Status</h6>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <small class="text-muted">Time In</small>
                                        <div id="quickTimeInDisplay" class="badge bg-light text-dark">--:--</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Time Out</small>
                                        <div id="quickTimeOutDisplay" class="badge bg-light text-dark">--:--</div>
                                    </div>
                                </div>
                                <div class="text-center mt-2">
                                    <small class="text-muted">Total Hours</small>
                                    <div id="quickTotalHoursDisplay" class="badge bg-primary">--</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- My Tasks -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">My Tasks</h5>
                </div>
                <div class="card-body">
                    @if($myTasks->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($myTasks as $task)
                                    <tr>
                                        <td>
                                            <strong>{{ $task->title }}</strong>
                                            <br>
                                            <small class="text-muted">{{ Str::limit($task->description, 50) }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $task->priority === 'urgent' ? 'danger' : ($task->priority === 'high' ? 'warning' : 'info') }} text-white">
                                                {{ $task->priority_name }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $task->status === 'completed' ? 'success' : ($task->status === 'pending' ? 'warning' : 'info') }} text-white">
                                                {{ $task->status_name }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $task->due_date->format('M d, Y') }}
                                            @if($task->is_overdue)
                                                <br><small class="text-danger">Overdue</small>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('tasks.show', $task) }}" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No tasks assigned to you</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Maintenance</h5>
                </div>
                <div class="card-body">
                    @if($recentMaintenance->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentMaintenance as $log)
                            <div class="list-group-item">
                                <h6 class="mb-1">{{ $log->equipment_name }}</h6>
                                <p class="mb-1">{{ $log->type_name }}</p>
                                <small class="text-muted">{{ $log->created_at->format('M d, Y') }}</small>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No recent maintenance logs</p>
                    @endif
                </div>
            </div>

            @if(isset($upcomingDeadlines) && $upcomingDeadlines->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Upcoming Deadlines</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($upcomingDeadlines as $task)
                        <div class="list-group-item">
                            <h6 class="mb-1">{{ $task->title }}</h6>
                            <small class="text-muted">Due: {{ $task->due_date->format('M d, Y') }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Performance Summary -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-star"></i> Performance Summary</h5>
                </div>
                <div class="card-body">
                    @if(isset($performanceData) && $performanceData['total_evaluations'] > 0)
                        <!-- Overall Rating -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Average Rating</span>
                                <strong class="h5 mb-0">
                                    {{ number_format($performanceData['average_rating'], 1) }}/5.0
                                </strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" role="progressbar" 
                                     style="width: {{ ($performanceData['average_rating'] / 5) * 100 }}%"
                                     aria-valuenow="{{ $performanceData['average_rating'] }}" 
                                     aria-valuemin="0" aria-valuemax="5">
                                </div>
                            </div>
                        </div>

                        <!-- Total Evaluations -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Total Evaluations</span>
                                <strong>{{ $performanceData['total_evaluations'] }}</strong>
                            </div>
                        </div>

                        <!-- Recommendation Rate -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Recommendation Rate</span>
                                <strong>
                                    {{ $performanceData['total_evaluations'] > 0 ? number_format(($performanceData['recommendation_rate'] / $performanceData['total_evaluations']) * 100, 1) : 0 }}%
                                </strong>
                            </div>
                        </div>

                        <!-- Service Aspects -->
                        @if($performanceData['service_aspects']['quality'] > 0 || 
                            $performanceData['service_aspects']['timeliness'] > 0 || 
                            $performanceData['service_aspects']['professionalism'] > 0)
                        <hr>
                        <h6 class="mb-3">Service Aspects</h6>
                        
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Quality</small>
                                <small><strong>{{ number_format($performanceData['service_aspects']['quality'], 1) }}/5.0</strong></small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: {{ ($performanceData['service_aspects']['quality'] / 5) * 100 }}%">
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Timeliness</small>
                                <small><strong>{{ number_format($performanceData['service_aspects']['timeliness'], 1) }}/5.0</strong></small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: {{ ($performanceData['service_aspects']['timeliness'] / 5) * 100 }}%">
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Professionalism</small>
                                <small><strong>{{ number_format($performanceData['service_aspects']['professionalism'], 1) }}/5.0</strong></small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" 
                                     style="width: {{ ($performanceData['service_aspects']['professionalism'] / 5) * 100 }}%">
                                </div>
                            </div>
                        </div>
                        @endif
                    @else
                        <p class="text-muted mb-0">
                            <i class="bi bi-info-circle"></i> No customer evaluations yet. 
                            Your performance summary will appear here once customers submit evaluations.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load today's status for quick display
    loadQuickAttendanceStatus();

    // Quick Time In button
    document.getElementById('quickTimeInBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'Time In',
            text: 'Are you ready to start your work day?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Time In!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    text: 'Recording your time in',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading()
                    }
                });

                fetch('{{ route("attendance.time-in") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Time in recorded successfully at ' + data.time_in,
                            icon: 'success',
                            confirmButtonColor: '#198754'
                        });
                        loadQuickAttendanceStatus();
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: data.message || 'Failed to record time in',
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while recording time in',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                });
            }
        });
    });

    // Quick Time Out button
    document.getElementById('quickTimeOutBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'Time Out',
            text: 'Are you finishing your work day?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Time Out!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    text: 'Recording your time out',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading()
                    }
                });

                fetch('{{ route("attendance.time-out") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Time out recorded successfully at ' + data.time_out + '. Total hours: ' + data.total_hours,
                            icon: 'success',
                            confirmButtonColor: '#198754'
                        });
                        loadQuickAttendanceStatus();
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: data.message || 'Failed to record time out',
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while recording time out',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                });
            }
        });
    });

    function loadQuickAttendanceStatus() {
        fetch('{{ route("attendance.today-status") }}', { headers: { 'Accept': 'application/json' } })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('quickTimeInDisplay').textContent = data.time_in || '--:--';
                    document.getElementById('quickTimeOutDisplay').textContent = data.time_out || '--:--';
                    document.getElementById('quickTotalHoursDisplay').textContent = data.total_hours || '--';

                    // Update button states based on attendance status
                    const timeInBtn = document.getElementById('quickTimeInBtn');
                    const timeOutBtn = document.getElementById('quickTimeOutBtn');

                    if (data.time_in && !data.time_out) {
                        // User has timed in but not out
                        timeInBtn.disabled = true;
                        timeInBtn.className = 'btn btn-secondary btn-lg';
                        timeInBtn.innerHTML = '<i class="bi bi-check-circle"></i> Already Timed In';
                        timeOutBtn.disabled = false;
                        timeOutBtn.className = 'btn btn-danger btn-lg';
                        timeOutBtn.innerHTML = '<i class="bi bi-clock"></i> Time Out';
                    } else if (data.time_in && data.time_out) {
                        // User has completed the day
                        timeInBtn.disabled = true;
                        timeInBtn.className = 'btn btn-success btn-lg';
                        timeInBtn.innerHTML = '<i class="bi bi-check-circle"></i> Day Completed';
                        timeOutBtn.disabled = true;
                        timeOutBtn.className = 'btn btn-success btn-lg';
                        timeOutBtn.innerHTML = '<i class="bi bi-check-circle"></i> Day Completed';
                    } else {
                        // User hasn't timed in yet
                        timeInBtn.disabled = false;
                        timeInBtn.className = 'btn btn-success btn-lg';
                        timeInBtn.innerHTML = '<i class="bi bi-clock-history"></i> Time In';
                        timeOutBtn.disabled = true;
                        timeOutBtn.className = 'btn btn-light btn-lg';
                        timeOutBtn.innerHTML = '<i class="bi bi-clock text-muted"></i> <span class="text-muted">Time Out (Time In First)</span>';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading attendance status:', error);
            });
    }
});
</script>
@endpush

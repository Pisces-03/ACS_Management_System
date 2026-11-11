@extends('layouts.app')

@section('title', 'Task Details - ' . $task->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Task Details</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Tasks</a></li>
                            <li class="breadcrumb-item active">{{ $task->task_number }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="btn-group">
                    @if((auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()) && $task->status !== 'cancelled' && $task->status !== 'completed')
                    <button type="button" class="btn btn-outline-danger" onclick="cancelTask('{{ $task->id }}')">
                        <i class="bi bi-x-circle"></i> Cancel Task
                    </button>
                    @endif
                    @if((auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()) && $task->status === 'cancelled')
                    <button type="button" class="btn btn-outline-danger" onclick="deleteTask('{{ $task->id }}', '{{ $task->title }}')">
                        <i class="bi bi-trash"></i> Delete Task
                    </button>
                    @endif
                    @if((auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()) && $task->status !== 'cancelled')
                    <a href="{{ route('tasks.edit', $task) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    @endif
                    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Tasks
                    </a>
                </div>
            </div>

            <!-- Task Information Card -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ $task->title }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Task Number:</strong></div>
                                <div class="col-sm-9">{{ $task->task_number }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Type:</strong></div>
                                <div class="col-sm-9">
                                    <span class="badge bg-info">{{ ucfirst($task->type) }}</span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Priority:</strong></div>
                                <div class="col-sm-9">
                                    @switch($task->priority)
                                        @case('urgent')
                                            <span class="badge bg-danger">Urgent</span>
                                            @break
                                        @case('high')
                                            <span class="badge bg-warning">High</span>
                                            @break
                                        @case('medium')
                                            <span class="badge bg-primary">Medium</span>
                                            @break
                                        @case('low')
                                            <span class="badge bg-secondary">Low</span>
                                            @break
                                    @endswitch
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Status:</strong></div>
                                <div class="col-sm-9">
                                    @switch($task->status)
                                        @case('pending')
                                            <span class="badge bg-warning">Pending</span>
                                            @break
                                        @case('assigned')
                                            <span class="badge bg-primary">Assigned</span>
                                            @break
                                        @case('in_progress')
                                            <span class="badge bg-info">In Progress</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-success">Completed</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                            @if($task->cancelled_at)
                                                <br><small class="text-muted">Cancelled on {{ $task->cancelled_at->format('M d, Y g:i A') }}</small>
                                            @endif
                                            @break
                                    @endswitch
                                </div>
                            </div>
                            @if($task->status === 'cancelled' && $task->cancellation_reason)
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Cancellation Reason:</strong></div>
                                <div class="col-sm-9">
                                    <div class="alert alert-danger mb-0">
                                        <i class="bi bi-info-circle"></i> {{ $task->cancellation_reason }}
                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Branch:</strong></div>
                                <div class="col-sm-9">
                                    <span class="badge bg-info">{{ $task->branch_name ?? branch_label($task->branch) }}</span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Due Date:</strong></div>
                                <div class="col-sm-9">
                                    @if($task->due_date)
                                        {{ $task->due_date->format('M d, Y g:i A') }}
                                        @if($task->due_date->isPast() && $task->status !== 'completed')
                                            <span class="badge bg-danger ms-2">Overdue</span>
                                        @endif
                                    @else
                                        <span class="text-muted">No due date set</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Assigned To:</strong></div>
                                <div class="col-sm-9">
                                    @if($task->assignedEmployee)
                                        {{ $task->assignedEmployee->full_name ?? 'Unknown User' }}
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Created By:</strong></div>
                                <div class="col-sm-9">{{ $task->creator->full_name ?? 'Unknown User' }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Estimated Time:</strong></div>
                                <div class="col-sm-9">
                                    @if($task->estimated_hours)
                                        @php
                                            // * Handle both array (from model cast) and JSON string cases
                                            $estimated = is_array($task->estimated_hours) 
                                                ? $task->estimated_hours 
                                                : json_decode($task->estimated_hours, true);
                                        @endphp
                                        {{ $estimated['hours'] ?? 0 }}h {{ $estimated['minutes'] ?? 0 }}m
                                    @else
                                        <span class="text-muted">Not specified</span>
                                    @endif
                                </div>
                            </div>
                            @if($task->actual_hours)
                                <div class="row mb-3">
                                    <div class="col-sm-3"><strong>Actual Time:</strong></div>
                                    <div class="col-sm-9">
                                        @php
                                            // * Handle both array (from model cast) and JSON string cases
                                            $actual = is_array($task->actual_hours) 
                                                ? $task->actual_hours 
                                                : json_decode($task->actual_hours, true);
                                        @endphp
                                        {{ $actual['hours'] ?? 0 }}h {{ $actual['minutes'] ?? 0 }}m
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Description -->
                    @if($task->description)
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Description</h5>
                        </div>
                        <div class="card-body">
                            <div class="task-description">
                                {!! nl2br(e($task->description)) !!}
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Completion Notes -->
                    @if($task->completion_notes)
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Completion Notes</h5>
                        </div>
                        <div class="card-body">
                            <div class="completion-notes">
                                {!! nl2br(e($task->completion_notes)) !!}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Timeline -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Timeline</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Task Created</h6>
                                        <p class="timeline-time">{{ $task->created_at->format('M d, Y g:i A') }}</p>
                                        <p class="timeline-desc">by {{ $task->creator->full_name ?? 'Unknown User' }}</p>
                                    </div>
                                </div>

                                @if($task->started_at)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Task Started</h6>
                                        <p class="timeline-time">{{ $task->started_at->format('M d, Y g:i A') }}</p>
                                    </div>
                                </div>
                                @endif

                                @if($task->completed_at)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Task Completed</h6>
                                        <p class="timeline-time">{{ $task->completed_at->format('M d, Y g:i A') }}</p>
                                    </div>
                                </div>
                                @endif

                                @if($task->cancelled_at)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-danger"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Task Cancelled</h6>
                                        <p class="timeline-time">{{ $task->cancelled_at->format('M d, Y g:i A') }}</p>
                                        @if($task->cancellation_reason)
                                        <p class="timeline-desc">Reason: {{ $task->cancellation_reason }}</p>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Task Actions (Admin/Superadmin) -->
                    @if((auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()) && $task->status !== 'cancelled' && $task->status !== 'completed')
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Task Actions</h5>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-danger btn-sm w-100" onclick="cancelTask('{{ $task->id }}')">
                                <i class="bi bi-x-circle"></i> Cancel Task
                            </button>
                        </div>
                    </div>
                    @endif

                    <!-- Delete Action (Only for cancelled tasks) -->
                    @if((auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()) && $task->status === 'cancelled')
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Task Actions</h5>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-danger btn-sm w-100" onclick="deleteTask('{{ $task->id }}', '{{ $task->title }}')">
                                <i class="bi bi-trash"></i> Delete Task Permanently
                            </button>
                            <p class="text-muted small mt-2 mb-0">
                                <i class="bi bi-exclamation-triangle"></i> This action cannot be undone. The task will be permanently deleted.
                            </p>
                        </div>
                    </div>
                    @endif

                    <!-- Maintenance Schedule (Only for completed tasks) -->
                    @if($task->status === 'completed' && (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()))
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="bi bi-calendar-check"></i> Maintenance Schedule</h5>
                        </div>
                        <div class="card-body">
                            @php
                                $schedule = $task->maintenanceSchedule;
                            @endphp

                            @if($schedule && $schedule->is_active)
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>Frequency:</strong>
                                        <span class="badge bg-info">{{ $schedule->frequency_description }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>Next Due Date:</strong>
                                        <span class="badge {{ $schedule->isDue() ? 'bg-danger' : 'bg-warning' }}">
                                            {{ $schedule->next_due_date->format('M d, Y') }}
                                        </span>
                                    </div>
                                    @if($schedule->last_maintenance_date)
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>Last Maintenance:</strong>
                                        <span class="text-muted">{{ $schedule->last_maintenance_date->format('M d, Y') }}</span>
                                    </div>
                                    @endif
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>Advance Notice:</strong>
                                        <span class="text-muted">{{ $schedule->advance_notice_days }} days</span>
                                    </div>
                                    @if($schedule->notes)
                                    <div class="mt-2">
                                        <strong>Notes:</strong>
                                        <p class="text-muted small mb-0">{{ $schedule->notes }}</p>
                                    </div>
                                    @endif
                                </div>

                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary btn-sm" onclick="openMaintenanceScheduleModal({{ $task->id }}, true)">
                                        <i class="bi bi-pencil"></i> Edit Schedule
                                    </button>
                                    @if($schedule->isDue())
                                    <button class="btn btn-success btn-sm" onclick="completeMaintenance('{{ $task->id }}')">
                                        <i class="bi bi-check-circle"></i> Mark Maintenance Complete
                                    </button>
                                    @endif
                                    <button class="btn btn-danger btn-sm" onclick="deleteMaintenanceSchedule('{{ $task->id }}')">
                                        <i class="bi bi-trash"></i> Delete Schedule
                                    </button>
                                </div>
                            @else
                                <p class="text-muted mb-3">No maintenance schedule set for this task.</p>
                                <button class="btn btn-primary btn-sm w-100" onclick="openMaintenanceScheduleModal({{ $task->id }}, false)">
                                    <i class="bi bi-plus-circle"></i> Set Maintenance Schedule
                                </button>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Task Status Actions (Employee Only) -->
                    @if(auth()->user()->role === 'employee' && $task->isAssignedTo(auth()->id()) && in_array($task->status, ['pending', 'assigned', 'in_progress']))
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Task Status</h5>
                        </div>
                        <div class="card-body">
                            @if($task->status === 'in_progress')
                                <button class="btn btn-success btn-sm w-100" onclick="updateTaskStatus('{{ $task->id }}', 'completed')">
                                    <i class="bi bi-check-circle"></i> Mark as Completed
                                </button>
                            @else
                                <button class="btn btn-info text-white btn-sm w-100" onclick="updateTaskStatus('{{ $task->id }}', 'in_progress')">
                                    <i class="bi bi-play-circle"></i> In Progress
                                </button>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(auth()->user()->role === 'employee' && $task->isAssignedTo(auth()->id()))
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Customer Evaluation</h5>
                        </div>
                        <div class="card-body">
                            @php
                                // * One customer evaluation per task
                                $existingEvaluation = \App\Models\CustomerFeedback::where('task_id', $task->id)->latest('id')->first();
                            @endphp
                            @if($existingEvaluation)
                                <div class="alert alert-success mb-0">
                                    <i class="bi bi-check-circle"></i> <strong>Evaluation submitted</strong><br>
                                    <small>
                                        {{ $existingEvaluation->customer_name }} rated
                                        <strong>{{ $existingEvaluation->rating }}/5</strong> ({{ $existingEvaluation->rating_text }})
                                        on {{ $existingEvaluation->submitted_at?->format('M d, Y h:i A') }}.
                                    </small>
                                </div>
                            @else
                                <a href="{{ route('feedback.create', ['task' => $task->id]) }}" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-emoji-smile"></i> Submit Customer Evaluation
                                </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Schedule Modal -->
@if($task->status === 'completed' && (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()))
<div class="modal fade" id="maintenanceScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Maintenance Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="maintenanceScheduleForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="frequency_type" class="form-label">Frequency Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="frequency_type" name="frequency_type" required>
                            <option value="days">Days</option>
                            <option value="weeks">Weeks</option>
                            <option value="months" selected>Months</option>
                            <option value="years">Years</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="frequency_value" class="form-label">Frequency Value <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="frequency_value" name="frequency_value" 
                               value="2" min="1" required>
                        <div class="form-text">e.g., 2 for "every 2 months"</div>
                    </div>
                    <div class="mb-3">
                        <label for="advance_notice_days" class="form-label">Advance Notice (Days) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="advance_notice_days" name="advance_notice_days" 
                               value="10" min="1" max="365" required>
                        <div class="form-text">Number of days before maintenance to send advance notification (default: 10)</div>
                    </div>
                    <div class="mb-3">
                        <label for="maintenance_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="maintenance_notes" name="notes" rows="3" 
                                  placeholder="Optional notes about the maintenance schedule..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" 
                            onclick="saveMaintenanceSchedule({{ $task->id }}, {{ $task->maintenanceSchedule && $task->maintenanceSchedule->is_active ? 'true' : 'false' }})">
                        Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@section('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -22px;
    top: 20px;
    width: 2px;
    height: calc(100% - 10px);
    background-color: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: -26px;
    top: 4px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.timeline-time {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.timeline-desc {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0;
}

.task-description, .completion-notes {
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>
@endsection

<script>
function cancelTask(taskId) {
    Swal.fire({
        title: 'Cancel Task?',
        html: `
            <p>Are you sure you want to cancel this task?</p>
            <p class="text-danger"><strong>This action cannot be undone!</strong></p>
            <div class="mt-3">
                <label for="cancellation_reason" class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                <textarea id="cancellation_reason" class="form-control" rows="4" 
                          placeholder="Please provide a reason for cancelling this task (minimum 10 characters)..."
                          required></textarea>
                <div class="form-text">This reason will be visible to all users.</div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Cancel Task!',
        cancelButtonText: 'No, Keep It',
        preConfirm: () => {
            const reason = document.getElementById('cancellation_reason').value.trim();
            if (!reason || reason.length < 10) {
                Swal.showValidationMessage('Please provide a cancellation reason (minimum 10 characters).');
                return false;
            }
            if (reason.length > 1000) {
                Swal.showValidationMessage('Cancellation reason must not exceed 1000 characters.');
                return false;
            }
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // Show loading
            Swal.fire({
                title: 'Cancelling...',
                text: 'Please wait while we cancel the task',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`/tasks/${taskId}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    cancellation_reason: result.value
                })
            })
            .then(async (response) => {
                const data = await response.json().catch(() => null);

                if (!response.ok || !(data?.success)) {
                    const message = data?.message || 'Unable to cancel task at this time.';
                    throw new Error(message);
                }

                Swal.fire({
                    title: 'Cancelled!',
                    text: 'Task has been cancelled successfully',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Error cancelling task: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

function deleteTask(taskId, taskTitle) {
    Swal.fire({
        title: 'Delete Task Permanently?',
        html: `
            <p>Are you sure you want to permanently delete this cancelled task?</p>
            <p class="text-muted"><strong>${taskTitle || 'This task'}</strong></p>
            <p class="text-danger"><strong>This action cannot be undone!</strong></p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete Permanently!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the task',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`/tasks/${taskId}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(async (response) => {
                const data = await response.json().catch(() => null);

                if (!response.ok || !(data?.success)) {
                    const message = data?.message || 'Unable to delete task at this time.';
                    throw new Error(message);
                }

                Swal.fire({
                    title: 'Deleted!',
                    text: 'Task has been permanently deleted',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '/tasks';
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Error deleting task: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

function updateTaskStatus(taskId, status) {
    const statusTexts = {
        'in_progress': 'start this task (In Progress)',
        'completed': 'mark this task as completed'
    };

    Swal.fire({
        title: 'Confirm Action',
        text: `Are you sure you want to ${statusTexts[status]}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: status === 'completed' ? '#28a745' : '#0dcaf0',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, proceed!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Processing...',
                text: 'Updating task status',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            fetch(`/tasks/${taskId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message || 'Task status updated successfully',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error updating task status: ' + (data.message || 'Unknown error'),
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Network error or server issue. Please check the console for details.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

function openMaintenanceScheduleModal(taskId, isEdit) {
    // Fetch current schedule if editing
    if (isEdit) {
        fetch(`/tasks/${taskId}/maintenance-schedule`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.schedule) {
                document.getElementById('frequency_type').value = data.schedule.frequency_type;
                document.getElementById('frequency_value').value = data.schedule.frequency_value;
                document.getElementById('advance_notice_days').value = data.schedule.advance_notice_days;
                document.getElementById('maintenance_notes').value = data.schedule.notes || '';
            }
        })
        .catch(error => {
            console.error('Error fetching schedule:', error);
        });
    } else {
        // Reset form for new schedule
        document.getElementById('frequency_type').value = 'months';
        document.getElementById('frequency_value').value = '2';
        document.getElementById('advance_notice_days').value = '10';
        document.getElementById('maintenance_notes').value = '';
    }

    const modal = new bootstrap.Modal(document.getElementById('maintenanceScheduleModal'));
    modal.show();
}

function saveMaintenanceSchedule(taskId, isEdit) {
    const form = document.getElementById('maintenanceScheduleForm');
    const formData = new FormData(form);
    
    const data = {
        frequency_type: formData.get('frequency_type'),
        frequency_value: parseInt(formData.get('frequency_value')),
        advance_notice_days: parseInt(formData.get('advance_notice_days')),
        notes: formData.get('notes') || null
    };

    const url = isEdit 
        ? `/tasks/${taskId}/maintenance-schedule/update`
        : `/tasks/${taskId}/maintenance-schedule`;
    const method = 'POST';

    Swal.fire({
        title: 'Saving...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(async (response) => {
        const result = await response.json().catch(() => null);

        if (!response.ok || !(result?.success)) {
            const message = result?.message || 'Unable to save maintenance schedule.';
            throw new Error(message);
        }

        Swal.fire({
            title: 'Success!',
            text: result.message || 'Maintenance schedule saved successfully',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        }).then(() => {
            location.reload();
        });
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error!',
            text: 'Error saving maintenance schedule: ' + error.message,
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    });
}

function completeMaintenance(taskId) {
    Swal.fire({
        title: 'Mark Maintenance Complete?',
        text: 'This will update the last maintenance date and calculate the next due date.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Mark Complete!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`/tasks/${taskId}/maintenance-schedule/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(async (response) => {
                const data = await response.json().catch(() => null);

                if (!response.ok || !(data?.success)) {
                    const message = data?.message || 'Unable to mark maintenance as complete.';
                    throw new Error(message);
                }

                Swal.fire({
                    title: 'Success!',
                    text: data.message || 'Maintenance marked as complete',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Error marking maintenance as complete: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

function deleteMaintenanceSchedule(taskId) {
    Swal.fire({
        title: 'Delete Maintenance Schedule?',
        text: 'This action cannot be undone. The maintenance schedule will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`/tasks/${taskId}/maintenance-schedule/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(async (response) => {
                const data = await response.json().catch(() => null);

                if (!response.ok || !(data?.success)) {
                    const message = data?.message || 'Unable to delete maintenance schedule.';
                    throw new Error(message);
                }

                Swal.fire({
                    title: 'Deleted!',
                    text: 'Maintenance schedule deleted successfully',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Error deleting maintenance schedule: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}
</script>

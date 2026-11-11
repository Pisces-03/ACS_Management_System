@extends('layouts.app')

@section('title', 'Task Management')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <h2 class="mb-0"><i class="bi bi-list-task"></i> Task Management</h2>
                @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Create Task
                </a>
                @endif
            </div>

            <!-- Task Statistics Cards -->
            <div class="row mb-4 g-3">
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card bg-primary text-dark h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">Total Tasks</h5>
                                    <h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-list-task h2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card bg-warning text-dark h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">Pending</h5>
                                    <h3 class="mb-0">{{ $stats['pending'] ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-clock h2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card bg-info text-dark h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">In Progress</h5>
                                    <h3 class="mb-0">{{ $stats['in_progress'] ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-gear h2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card bg-success text-dark h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">Completed</h5>
                                    <h3 class="mb-0">{{ $stats['completed'] ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-check-circle h2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Tasks</h5>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="row g-3 mb-3">
                        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                            <label class="form-label small">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>Assigned</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                            <label class="form-label small">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="">All Priority</option>
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12 col-12">
                            <label class="form-label small">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search tasks..." value="{{ request('search') }}">
                        </div>
                        <div class="col-lg-2 col-md-6 col-sm-6 col-12">
                            <label class="form-label small">&nbsp;</label>
                            <button type="submit" class="btn btn-outline-primary d-block w-100">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th class="d-none d-sm-table-cell">Task ID</th>
                                    <th>Title</th>
                                    <th class="d-none d-md-table-cell">Type</th>
                                    <th class="d-none d-lg-table-cell">Priority</th>
                                    <th>Status</th>
                                    <th class="d-none d-lg-table-cell">Assigned To</th>
                                    <th class="d-none d-md-table-cell">Due Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks ?? [] as $task)
                                <tr>
                                    <td class="d-none d-sm-table-cell">{{ $task->task_number ?? 'N/A' }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">{{ $task->title ?? 'No Title' }}</span>
                                            <small class="text-muted d-sm-none">
                                                ID: {{ $task->task_number ?? 'N/A' }} | 
                                                Type: {{ ucfirst($task->type ?? 'general') }}
                                            </small>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge bg-secondary">{{ ucfirst($task->type ?? 'general') }}</span>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        @switch($task->priority ?? 'medium')
                                            @case('urgent')
                                                <span class="badge bg-danger">Urgent</span>
                                                @break
                                            @case('high')
                                                <span class="badge bg-warning">High</span>
                                                @break
                                            @case('medium')
                                                <span class="badge bg-info">Medium</span>
                                                @break
                                            @case('low')
                                                <span class="badge bg-light text-dark">Low</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($task->priority ?? 'medium') }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @switch($task->status ?? 'pending')
                                            @case('completed')
                                                <span class="badge bg-success">Completed</span>
                                                @break
                                            @case('in_progress')
                                                <span class="badge bg-primary">In Progress</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning">Pending</span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge bg-danger">Cancelled</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($task->status ?? 'pending') }}</span>
                                        @endswitch
                                        <div class="d-sm-none mt-1">
                                            <small class="text-muted">
                                                Priority: {{ ucfirst($task->priority ?? 'medium') }}<br>
                                                Assigned: {{ $task->assignedEmployee->full_name ?? 'Unassigned' }}<br>
                                                Due: {{ $task->due_date ? $task->due_date->format('M d, Y') : 'No due date' }}
                                            </small>
                                        </div>
                                    </td>
                                    <td class="d-none d-lg-table-cell">{{ $task->assignedEmployee->full_name ?? 'Unassigned' }}</td>
                                    <td class="d-none d-md-table-cell">{{ $task->due_date ? $task->due_date->format('M d, Y') : 'No due date' }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('tasks.show', $task) }}" class="btn btn-outline-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                                            @if($task->status !== 'cancelled')
                                            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            @endif
                                            @if($task->status !== 'cancelled' && $task->status !== 'completed')
                                            <button type="button" class="btn btn-outline-danger" title="Cancel" onclick="cancelTask('{{ $task->id }}', '{{ $task->title }}')">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                            @endif
                                            @if($task->status === 'cancelled')
                                            <button type="button" class="btn btn-outline-danger delete-task-btn" title="Delete" data-task-id="{{ $task->id }}" data-task-title="{{ $task->title }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox h2 d-block"></i>
                                        No tasks found.
                                        @if(auth()->user()->isAdmin())
                                        <a href="{{ route('tasks.create') }}">Create your first task</a>
                                        @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(isset($tasks) && method_exists($tasks, 'links'))
                    {{ $tasks->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// * Define deleteTask function first (before event listeners)
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
                    location.reload();
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

// * Event delegation for delete task buttons
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-task-btn')) {
            const btn = e.target.closest('.delete-task-btn');
            const taskId = btn.getAttribute('data-task-id');
            const taskTitle = btn.getAttribute('data-task-title');
            deleteTask(taskId, taskTitle);
        }
    });
});

function cancelTask(taskId, taskTitle) {
    Swal.fire({
        title: 'Cancel Task?',
        html: `
            <p>Are you sure you want to cancel this task?</p>
            <p class="text-muted"><strong>${taskTitle || 'This task'}</strong></p>
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

// Show success/error messages if they exist
@if(session('success'))
Swal.fire({
    title: 'Success!',
    text: '{{ session('success') }}',
    icon: 'success',
    confirmButtonColor: '#198754'
});
@endif

@if(session('error'))
Swal.fire({
    title: 'Error!',
    text: '{{ session('error') }}',
    icon: 'error',
    confirmButtonColor: '#dc3545'
});
@endif

// Auto-refresh every 30 seconds for real-time updates
setInterval(function() {
    if (document.hidden) return; // Don't refresh if tab is not active

    // Only refresh if no filters are applied
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.toString() === '') {
        window.location.reload();
    }
}, 30000);
</script>
@endpush

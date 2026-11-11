@extends('layouts.app')

@section('title', 'Edit Task - ' . $task->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Tasks</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('tasks.show', $task) }}">{{ $task->task_number }}</a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                    <h2><i class="bi bi-pencil"></i> Edit Task</h2>
                    <p class="text-muted mb-0">Update task details and assignment</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('tasks.show', $task) }}" class="btn btn-outline-primary">
                        <i class="bi bi-eye"></i> View Task
                    </a>
                    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Tasks
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <form action="{{ route('tasks.update', $task) }}" method="POST" id="taskForm">
                        @csrf
                        @method('PUT')

                        <!-- Basic Information -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Task Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Task Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                                           id="title" name="title" value="{{ old('title', $task->title) }}"
                                           placeholder="Enter a clear, descriptive task title" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              id="description" name="description" rows="4"
                                              placeholder="Provide detailed instructions and requirements"
                                              required>{{ old('description', $task->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="type" class="form-label">Task Type <span class="text-danger">*</span></label>
                                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                                <option value="">Select Type</option>
                                                <option value="installation" {{ old('type', $task->type) == 'installation' ? 'selected' : '' }}>Installation</option>
                                                <option value="reinstallation" {{ old('type', $task->type) == 'reinstallation' ? 'selected' : '' }}>Re-installation</option>
                                                <option value="repair" {{ old('type', $task->type) == 'repair' ? 'selected' : '' }}>Repair</option>
                                                <option value="dismantling" {{ old('type', $task->type) == 'dismantling' ? 'selected' : '' }}>Dismantling</option>
                                                <option value="checkup" {{ old('type', $task->type) == 'checkup' ? 'selected' : '' }}>Check Up</option>
                                            </select>
                                            @error('type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                            <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                                <option value="">Select Priority</option>
                                                <option value="low" {{ old('priority', $task->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                                <option value="medium" {{ old('priority', $task->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                                                <option value="high" {{ old('priority', $task->priority) == 'high' ? 'selected' : '' }}>High</option>
                                                <option value="urgent" {{ old('priority', $task->priority) == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                            </select>
                                            @error('priority')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                                <option value="pending" {{ old('status', $task->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="assigned" {{ old('status', $task->status) == 'assigned' ? 'selected' : '' }}>Assigned</option>
                                                <option value="in_progress" {{ old('status', $task->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                                <option value="completed" {{ old('status', $task->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                                <option value="cancelled" {{ old('status', $task->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="branch" class="form-label">Branch <span class="text-danger">*</span></label>
                                            @if(auth()->user()->isSuperAdmin())
                                            <select class="form-select @error('branch') is-invalid @enderror" id="branch" name="branch" required>
                                                <option value="">Select Branch</option>
                                                <option value="las_pinas" {{ old('branch', $task->branch) == 'las_pinas' ? 'selected' : '' }}>Las Piñas</option>
                                                <option value="cebu" {{ old('branch', $task->branch) == 'cebu' ? 'selected' : '' }}>Cebu</option>
                                                <option value="davao" {{ old('branch', $task->branch) == 'davao' ? 'selected' : '' }}>Davao</option>
                                            </select>
                                            @else
                                            {{-- * Admins keep tasks in their own branch (the controller enforces it too) --}}
                                            <input type="hidden" id="branch" name="branch" value="{{ auth()->user()->branch }}">
                                            <input type="text" class="form-control-plaintext fw-semibold" readonly value="{{ auth()->user()->branch_name }}">
                                            @endif
                                            @error('branch')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="assigned_to" class="form-label">Assign To</label>
                                            <select class="form-select @error('assigned_to') is-invalid @enderror" id="assigned_to" name="assigned_to">
                                                <option value="">Unassigned</option>
                                                @foreach($employees as $employee)
                                                    <option value="{{ $employee->id }}"
                                                            {{ old('assigned_to', $task->assigned_to) == $employee->id ? 'selected' : '' }}
                                                            data-branch="{{ $employee->branch }}">
                                                        {{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->branch_name ?? branch_label($employee->branch) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('assigned_to')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="due_date" class="form-label">Due Date</label>
                                            <input type="datetime-local" class="form-control @error('due_date') is-invalid @enderror"
                                                   id="due_date" name="due_date"
                                                   value="{{ old('due_date', $task->due_date ? $task->due_date->format('Y-m-d\TH:i') : '') }}">
                                            @error('due_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                @php
                                    // * Handle both array (from model cast) and JSON string cases
                                    $estimatedHoursData = $task->estimated_hours 
                                        ? (is_array($task->estimated_hours) 
                                            ? $task->estimated_hours 
                                            : json_decode($task->estimated_hours, true))
                                        : null;
                                @endphp
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estimated_hours" class="form-label">Estimated Hours</label>
                                            <input type="number" class="form-control @error('estimated_hours') is-invalid @enderror"
                                                   id="estimated_hours" name="estimated_hours" min="0" step="0.5"
                                                   value="{{ old('estimated_hours', $estimatedHoursData['hours'] ?? 0) }}"
                                                   placeholder="0">
                                            @error('estimated_hours')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estimated_minutes" class="form-label">Estimated Minutes</label>
                                            <select class="form-select @error('estimated_minutes') is-invalid @enderror"
                                                    id="estimated_minutes" name="estimated_minutes">
                                                @for($i = 0; $i < 60; $i += 15)
                                                    <option value="{{ $i }}"
                                                            {{ old('estimated_minutes', $estimatedHoursData['minutes'] ?? 0) == $i ? 'selected' : '' }}>
                                                        {{ $i }} minutes
                                                    </option>
                                                @endfor
                                            </select>
                                            @error('estimated_minutes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                @if($task->status === 'completed')
                                <div class="mb-3">
                                    <label for="completion_notes" class="form-label">Completion Notes</label>
                                    <textarea class="form-control @error('completion_notes') is-invalid @enderror"
                                              id="completion_notes" name="completion_notes" rows="3"
                                              placeholder="Add any notes about task completion">{{ old('completion_notes', $task->completion_notes) }}</textarea>
                                    @error('completion_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg"></i> Update Task
                                        </button>
                                        <a href="{{ route('tasks.show', $task) }}" class="btn btn-outline-secondary ms-2">
                                            <i class="bi bi-x-lg"></i> Cancel
                                        </a>
                                    </div>
                                    <div>
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle"></i>
                                            Task Number: {{ $task->task_number }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Task Information -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Task Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted">Task Number</small>
                                <div class="fw-bold">{{ $task->task_number }}</div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Created By</small>
                                <div>{{ $task->creator->full_name ?? 'Unknown User' }}</div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Created At</small>
                                <div>{{ $task->created_at->format('M d, Y g:i A') }}</div>
                            </div>
                            @if($task->started_at)
                            <div class="mb-3">
                                <small class="text-muted">Started At</small>
                                <div>{{ $task->started_at->format('M d, Y g:i A') }}</div>
                            </div>
                            @endif
                            @if($task->completed_at)
                            <div class="mb-3">
                                <small class="text-muted">Completed At</small>
                                <div>{{ $task->completed_at->format('M d, Y g:i A') }}</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Help -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">Help & Tips</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled small">
                                <li class="mb-2">
                                    <i class="bi bi-lightbulb text-warning"></i>
                                    Changing status to "Completed" will automatically set completion time
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-lightbulb text-warning"></i>
                                    Employees can only be assigned to tasks in their branch
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-lightbulb text-warning"></i>
                                    Use clear, descriptive titles for better task management
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const branchSelect = document.getElementById('branch');
    const assignedToSelect = document.getElementById('assigned_to');

    function filterEmployees() {
        const selectedBranch = branchSelect.value;
        const employees = assignedToSelect.querySelectorAll('option[data-branch]');

        employees.forEach(option => {
            if (selectedBranch === '' || option.dataset.branch === selectedBranch) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
                if (option.selected) {
                    option.selected = false;
                    assignedToSelect.value = '';
                }
            }
        });
    }

    branchSelect.addEventListener('change', filterEmployees);

    // Filter on page load
    filterEmployees();

    // Form validation
    const form = document.getElementById('taskForm');
    form.addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const type = document.getElementById('type').value;
        const priority = document.getElementById('priority').value;
        const branch = document.getElementById('branch').value;

        if (!title || !description || !type || !priority || !branch) {
            e.preventDefault();
            Swal.fire({
                title: 'Validation Error',
                text: 'Please fill in all required fields.',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
    });
});
</script>

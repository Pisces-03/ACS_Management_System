@extends('layouts.app')

@section('title', 'Create Task')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Tasks</a></li>
                            <li class="breadcrumb-item active">Create Task</li>
                        </ol>
                    </nav>
                    <h2><i class="bi bi-plus-circle"></i> Create New Task</h2>
                    <p class="text-muted mb-0">Assign a new task to team members</p>
                </div>
                <div>
                    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Tasks
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <form action="{{ route('tasks.store') }}" method="POST" id="taskForm">
                        @csrf

                        <!-- Basic Information -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Task Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Task Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                                           id="title" name="title" value="{{ old('title') }}"
                                           placeholder="Enter a clear, descriptive task title" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              id="description" name="description" rows="4"
                                              placeholder="Provide detailed instructions and requirements..." required>{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="assigned_to" class="form-label">Assign To <span class="text-danger">*</span></label>
                                            <div class="employee-select-container">
                                                <div class="employee-select-header">
                                                    <input type="text" class="form-control employee-search" 
                                                           id="employeeSearch" 
                                                           placeholder="Search employees..." 
                                                           autocomplete="off">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary employee-select-toggle" id="selectToggle">
                                                        <span id="selectToggleText">Select All</span>
                                                    </button>
                                                </div>
                                                <div class="employee-select-list @error('assigned_to') is-invalid @enderror" id="employeeSelectList">
                                                    @if(auth()->user()->role === 'superadmin')
                                                        @foreach($employees as $branch => $branchEmployees)
                                                            <div class="employee-branch-group" data-branch="{{ $branch }}">
                                                                <div class="employee-branch-header">
                                                                    <strong>{{ ucfirst(str_replace('_', ' ', $branch)) }}</strong>
                                                                </div>
                                                                @foreach($branchEmployees as $employee)
                                                                <div class="employee-option" data-employee-id="{{ $employee->id }}" data-branch="{{ $employee->branch }}">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input employee-checkbox" 
                                                                               type="checkbox" 
                                                                               name="assigned_to[]" 
                                                                               value="{{ $employee->id }}" 
                                                                               id="employee_{{ $employee->id }}"
                                                                               data-branch="{{ $employee->branch }}"
                                                                               {{ in_array($employee->id, old('assigned_to', [])) ? 'checked' : '' }}>
                                                                        <label class="form-check-label" for="employee_{{ $employee->id }}">
                                                                            <span class="employee-name">{{ $employee->full_name }}</span>
                                                                            <span class="employee-position">{{ $employee->position ?? 'Employee' }}</span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                @endforeach
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        @foreach($employees as $employee)
                                                        <div class="employee-option" data-employee-id="{{ $employee->id }}" data-branch="{{ $employee->branch }}">
                                                            <div class="form-check">
                                                                <input class="form-check-input employee-checkbox" 
                                                                       type="checkbox" 
                                                                       name="assigned_to[]" 
                                                                       value="{{ $employee->id }}" 
                                                                       id="employee_{{ $employee->id }}"
                                                                       data-branch="{{ $employee->branch }}"
                                                                       {{ in_array($employee->id, old('assigned_to', [])) ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="employee_{{ $employee->id }}">
                                                                    <span class="employee-name">{{ $employee->full_name }}</span>
                                                                    <span class="employee-position">{{ $employee->position ?? 'Employee' }}</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                                <div class="employee-select-footer">
                                                    <small class="text-muted">
                                                        <span id="selectedCount">0</span> employee(s) selected
                                                    </small>
                                                </div>
                                            </div>
                                            @error('assigned_to')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                            <select class="form-select @error('priority') is-invalid @enderror"
                                                    id="priority" name="priority" required>
                                                <option value="">Select Priority</option>
                                                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                                <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                                                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                                <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
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
                                            <label for="due_date" class="form-label">Due Date</label>
                                            <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                                   id="due_date" name="due_date" value="{{ old('due_date') }}"
                                                   min="{{ date('Y-m-d') }}">
                                            @error('due_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estimated_hours" class="form-label">Estimated Hours</label>
                                            <input type="number" class="form-control @error('estimated_hours') is-invalid @enderror"
                                                   id="estimated_hours" name="estimated_hours"
                                                   value="{{ old('estimated_hours') }}" min="0.5" step="0.5"
                                                   placeholder="e.g., 4.5">
                                            @error('estimated_hours')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                            <select class="form-select @error('type') is-invalid @enderror"
                                                    id="type" name="type" required>
                                                <option value="">Select Type</option>
                                                <option value="installation" {{ old('type') == 'installation' ? 'selected' : '' }}>Installation</option>
                                                <option value="reinstallation" {{ old('type') == 'reinstallation' ? 'selected' : '' }}>Re-installation</option>
                                                <option value="repair" {{ old('type') == 'repair' ? 'selected' : '' }}>Repair</option>
                                                <option value="dismantling" {{ old('type') == 'dismantling' ? 'selected' : '' }}>Dismantling</option>
                                                <option value="checkup" {{ old('type') == 'checkup' ? 'selected' : '' }}>Check Up</option>
                                            </select>
                                            @error('type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Initial Status</label>
                                            <select class="form-select @error('status') is-invalid @enderror"
                                                    id="status" name="status" disabled>
                                                <option value="pending" selected>Pending (until the employee starts it)</option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                 @if(auth()->user()->role === 'superadmin')
                                 <div class="row">
                                     <div class="col-md-6">
                                         <div class="mb-3">
                                             <label for="branch" class="form-label">Branch <span class="text-danger">*</span> <small class="text-muted">(Auto-set based on selected employees)</small></label>
                                             <select class="form-select @error('branch') is-invalid @enderror"
                                                     id="branch" name="branch" required>
                                                 <option value="">Select Branch (or select employees to auto-set)</option>
                                                 <option value="las_pinas" {{ old('branch') == 'las_pinas' ? 'selected' : '' }}>Las Piñas</option>
                                                 <option value="cebu" {{ old('branch') == 'cebu' ? 'selected' : '' }}>Cebu</option>
                                                 <option value="davao" {{ old('branch') == 'davao' ? 'selected' : '' }}>Davao</option>
                                             </select>
                                             <small class="form-text text-muted">Branch will automatically update when you select employees</small>
                                             @error('branch')
                                                 <div class="invalid-feedback">{{ $message }}</div>
                                             @enderror
                                         </div>
                                     </div>
                                 </div>
                                 @else
                                 <input type="hidden" name="branch" value="{{ auth()->user()->branch }}">
                                 @endif

                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('tasks.index') }}" class="btn btn-secondary me-2">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Create Task
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Help Sidebar -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Task Guidelines</h6>
                        </div>
                        <div class="card-body">
                            <h6>Priority Levels:</h6>
                            <ul class="list-unstyled">
                                <li><span class="badge bg-success">Low:</span> Can be completed when convenient</li>
                                <li><span class="badge bg-info">Medium:</span> Regular priority, standard deadline</li>
                                <li><span class="badge bg-warning">High:</span> Important, needs attention soon</li>
                                <li><span class="badge bg-danger">Urgent:</span> Critical, immediate action required</li>
                            </ul>

                            <h6 class="mt-3">Best Practices:</h6>
                            <ul class="small">
                                <li>Write clear, specific task titles</li>
                                <li>Include detailed descriptions with requirements</li>
                                <li>Set realistic due dates and time estimates</li>
                                <li>Choose appropriate priority levels</li>
                                <li>Attach relevant documents or references</li>
                                <li>Consider the assignee's current workload</li>
                            </ul>

                            <h6 class="mt-3">Categories:</h6>
                            <ul class="small">
                                <li><strong>Maintenance:</strong> Equipment and facility upkeep</li>
                                <li><strong>Cleaning:</strong> Sanitation and cleanliness tasks</li>
                                <li><strong>Inspection:</strong> Quality and safety checks</li>
                                <li><strong>Delivery:</strong> Transportation and logistics</li>
                                <li><strong>Inventory:</strong> Stock management and counting</li>
                                <li><strong>Documentation:</strong> Reports and record keeping</li>
                                <li><strong>Training:</strong> Learning and development</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Recent Tasks -->
                    @if(isset($recentTasks) && $recentTasks->count() > 0)
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent Tasks</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                @foreach($recentTasks->take(5) as $task)
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $task->title }}</h6>
                                            <p class="mb-1 text-muted small">{{ Str::limit($task->description, 50) }}</p>
                                            <small class="text-muted">{{ $task->created_at->diffForHumans() }}</small>
                                        </div>
                                        <span class="badge bg-{{ $task->priority == 'urgent' ? 'danger' : ($task->priority == 'high' ? 'warning' : ($task->priority == 'medium' ? 'info' : 'success')) }}">
                                            {{ ucfirst($task->priority) }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* * Employee Select Container Styles */
.employee-select-container {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background: #fff;
    overflow: hidden;
}

.employee-select-header {
    display: flex;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.employee-search {
    flex: 1;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}

.employee-select-toggle {
    white-space: nowrap;
    flex-shrink: 0;
}

.employee-select-list {
    max-height: 300px;
    overflow-y: auto;
    padding: 0.5rem;
    background: #fff;
}

.employee-branch-group {
    margin-bottom: 0.75rem;
}

.employee-branch-group:last-child {
    margin-bottom: 0;
}

.employee-branch-header {
    padding: 0.5rem 0.75rem;
    background: #e9ecef;
    border-radius: 0.25rem;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: #495057;
}

.employee-option {
    padding: 0.5rem 0.75rem;
    transition: background-color 0.15s ease;
    border-radius: 0.25rem;
    margin-bottom: 0.25rem;
}

.employee-option:hover {
    background-color: #f8f9fa;
}

.employee-option .form-check {
    margin: 0;
    display: flex;
    align-items: center;
}

.employee-option .form-check-input {
    margin-top: 0;
    margin-right: 0.75rem;
    cursor: pointer;
}

.employee-option .form-check-label {
    cursor: pointer;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.employee-name {
    font-weight: 500;
    color: #212529;
    font-size: 0.9375rem;
}

.employee-position {
    font-size: 0.8125rem;
    color: #6c757d;
    margin-top: 0.125rem;
}

.employee-select-footer {
    padding: 0.5rem 0.75rem;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    text-align: center;
}

/* * Scrollbar styling for employee list */
.employee-select-list::-webkit-scrollbar {
    width: 8px;
}

.employee-select-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.employee-select-list::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.employee-select-list::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* * Invalid state */
.employee-select-list.is-invalid {
    border-color: #dc3545;
}

/* * Responsive adjustments */
@media (max-width: 768px) {
    .employee-select-header {
        flex-direction: column;
    }
    
    .employee-select-toggle {
        width: 100%;
    }
}
</style>
@endpush

@push('scripts')
<script>
// * Form submission with SweetAlert confirmation
document.getElementById('taskForm')?.addEventListener('submit', function(e) {
    // * Validate at least one employee is selected
    const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
    if (checkboxes.length === 0) {
        e.preventDefault();
        Swal.fire({
            title: 'No Employee Selected',
            text: 'Please select at least one employee to assign this task to.',
            icon: 'warning',
            confirmButtonColor: '#198754'
        });
        return false;
    }

    e.preventDefault();

    Swal.fire({
        title: 'Create Task?',
        text: 'Are you sure you want to create this task?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Create!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Creating...',
                text: 'Please wait while we create the task',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading()
                }
            });

            this.submit();
        }
    });
});

// * Employee selection functionality
let isUpdatingBranch = false; // * Flag to prevent recursive updates

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;
    // * Auto-update branch based on selected employees
    @if(auth()->user()->role === 'superadmin')
    const branchSelect = document.getElementById('branch');
    if (branchSelect && count > 0 && !isUpdatingBranch) {
        isUpdatingBranch = true;
        const selectedBranches = new Set();
        checkboxes.forEach(checkbox => {
            const branch = checkbox.dataset.branch;
            if (branch) {
                selectedBranches.add(branch);
            }
        });
        
        // * If all selected employees are from the same branch, set that branch
        if (selectedBranches.size === 1) {
            const branchValue = Array.from(selectedBranches)[0];
            if (branchSelect.value !== branchValue) {
                branchSelect.value = branchValue;
                // * Don't trigger branch change event to avoid unchecking employees
                // * Just update the display to show only employees from this branch
                const employeeOptions = employeeSelectList.querySelectorAll('.employee-option');
                const branchGroups = employeeSelectList.querySelectorAll('.employee-branch-group');
                
                employeeOptions.forEach(option => {
                    const employeeBranch = option.dataset.branch;
                    if (employeeBranch !== branchValue) {
                        option.style.display = 'none';
                        const checkbox = option.querySelector('.employee-checkbox');
                        checkbox.disabled = true;
                    } else {
                        option.style.display = '';
                        const checkbox = option.querySelector('.employee-checkbox');
                        checkbox.disabled = false;
                    }
                });
                
                branchGroups.forEach(group => {
                    const groupBranch = group.dataset.branch;
                    if (groupBranch !== branchValue) {
                        group.style.display = 'none';
                    } else {
                        group.style.display = '';
                    }
                });
            }
        } else if (selectedBranches.size > 1) {
            // * Multiple branches selected - set to first branch and show warning
            const firstBranch = Array.from(selectedBranches)[0];
            if (branchSelect.value !== firstBranch) {
                branchSelect.value = firstBranch;
                
                // * Show all selected employees regardless of branch
                const employeeOptions = employeeSelectList.querySelectorAll('.employee-option');
                const branchGroups = employeeSelectList.querySelectorAll('.employee-branch-group');
                
                employeeOptions.forEach(option => {
                    const checkbox = option.querySelector('.employee-checkbox');
                    if (checkbox.checked) {
                        // * Keep checked employees visible
                        option.style.display = '';
                        checkbox.disabled = false;
                    } else {
                        // * Hide unchecked employees not in selected branch
                        const employeeBranch = option.dataset.branch;
                        if (employeeBranch !== firstBranch) {
                            option.style.display = 'none';
                            checkbox.disabled = true;
                        } else {
                            option.style.display = '';
                            checkbox.disabled = false;
                        }
                    }
                });
                
                branchGroups.forEach(group => {
                    const groupBranch = group.dataset.branch;
                    const hasCheckedEmployee = Array.from(group.querySelectorAll('.employee-checkbox')).some(cb => cb.checked);
                    if (hasCheckedEmployee || groupBranch === firstBranch) {
                        group.style.display = '';
                    } else {
                        group.style.display = 'none';
                    }
                });
                
                // * Show warning if multiple branches are selected (only once)
                setTimeout(() => {
                    Swal.fire({
                        title: 'Multiple Branches Selected',
                        html: `You have selected employees from different branches. The branch has been set to <strong>${firstBranch.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</strong>.<br><br>Please ensure all selected employees belong to the same branch.`,
                        icon: 'warning',
                        confirmButtonColor: '#ffc107',
                        confirmButtonText: 'OK'
                    });
                }, 100);
            }
        }
        isUpdatingBranch = false;
    } else if (branchSelect && count === 0 && !isUpdatingBranch) {
        // * Reset branch when no employees are selected (only if not already empty)
        if (branchSelect.value !== '') {
            isUpdatingBranch = true;
            branchSelect.value = '';
            const event = new Event('change', { bubbles: true });
            branchSelect.dispatchEvent(event);
            isUpdatingBranch = false;
        }
    }
    @endif
}

// * Search functionality
document.getElementById('employeeSearch')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const options = document.querySelectorAll('.employee-option');
    
    options.forEach(option => {
        const name = option.querySelector('.employee-name')?.textContent.toLowerCase() || '';
        const position = option.querySelector('.employee-position')?.textContent.toLowerCase() || '';
        const matches = name.includes(searchTerm) || position.includes(searchTerm);
        
        option.style.display = matches ? '' : 'none';
        
        // * Hide branch groups if all employees are hidden
        const branchGroup = option.closest('.employee-branch-group');
        if (branchGroup) {
            const visibleOptions = branchGroup.querySelectorAll('.employee-option[style=""]').length;
            if (visibleOptions === 0 && searchTerm !== '') {
                branchGroup.style.display = 'none';
            } else {
                branchGroup.style.display = '';
            }
        }
    });
});

// * Select All / Deselect All toggle
document.getElementById('selectToggle')?.addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('.employee-checkbox:not([disabled])');
    const visibleCheckboxes = Array.from(checkboxes).filter(cb => {
        const option = cb.closest('.employee-option');
        return option && option.style.display !== 'none';
    });
    
    const allChecked = visibleCheckboxes.every(cb => cb.checked);
    
    visibleCheckboxes.forEach(cb => {
        cb.checked = !allChecked;
    });
    
    this.querySelector('#selectToggleText').textContent = allChecked ? 'Select All' : 'Deselect All';
    updateSelectedCount();
});

// * Update count when checkboxes change
document.querySelectorAll('.employee-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedCount);
});

// * Initial count update
updateSelectedCount();

@if(auth()->user()->role === 'superadmin')
// * Filter employees by selected branch for superadmin
const branchSelect = document.getElementById('branch');
const employeeSelectList = document.getElementById('employeeSelectList');

if (branchSelect && employeeSelectList) {
    branchSelect.addEventListener('change', function() {
        // * Skip if we're in the middle of auto-updating branch
        if (isUpdatingBranch) {
            return;
        }
        
        const selectedBranch = this.value;
        const employeeOptions = employeeSelectList.querySelectorAll('.employee-option');
        const branchGroups = employeeSelectList.querySelectorAll('.employee-branch-group');
        
        // * If branch is cleared, show all employees
        if (!selectedBranch) {
            employeeOptions.forEach(option => {
                option.style.display = '';
                const checkbox = option.querySelector('.employee-checkbox');
                checkbox.disabled = false;
            });
            
            branchGroups.forEach(group => {
                group.style.display = '';
            });
            
            updateSelectedCount();
            return;
        }
        
        // * Filter employees by branch
        employeeOptions.forEach(option => {
            const employeeBranch = option.dataset.branch;
            const checkbox = option.querySelector('.employee-checkbox');
            
            if (employeeBranch !== selectedBranch) {
                option.style.display = 'none';
                checkbox.disabled = true;
                // * Uncheck employees not in selected branch
                if (checkbox.checked) {
                    checkbox.checked = false;
                }
            } else {
                option.style.display = '';
                checkbox.disabled = false;
            }
        });
        
        // * Hide/show branch groups
        branchGroups.forEach(group => {
            const groupBranch = group.dataset.branch;
            if (groupBranch !== selectedBranch) {
                group.style.display = 'none';
            } else {
                group.style.display = '';
            }
        });
        
        updateSelectedCount();
    });
}
@endif

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
</script>
@endpush

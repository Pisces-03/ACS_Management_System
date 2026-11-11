@extends('layouts.app')

@section('title', 'Employee Management')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <h2 class="mb-0"><i class="bi bi-people"></i> Employee Management</h2>
                @if(auth()->user()->role === 'superadmin' || (auth()->user()->role === 'admin'))
                <a href="{{ route('employees.create') }}" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Add Employee
                </a>
                @endif
            </div>

            <!-- Employee Statistics -->
            <div class="row mb-4 g-3">
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">Total Employees</h5>
                                    <h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">Active</h5>
                                    <h3 class="mb-0">{{ $stats['active'] ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-person-check fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">Admins</h5>
                                    <h3 class="mb-0">{{ $stats['admins'] ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-shield-check fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">Employees</h5>
                                    <h3 class="mb-0">{{ $stats['employees'] ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-person fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employee List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Employee List</h5>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="row g-3 mb-3">
                        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                            <label class="form-label small">Role</label>
                            <select name="role" class="form-select">
                                <option value="">All Roles</option>
                                <option value="superadmin" {{ request('role') == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="employee" {{ request('role') == 'employee' ? 'selected' : '' }}>Employee</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                            <label class="form-label small">Branch</label>
                            @if(auth()->user()->isSuperAdmin())
                            <select name="branch" class="form-select">
                                <option value="">All Branches</option>
                                <option value="cebu" {{ request('branch') == 'cebu' ? 'selected' : '' }}>Cebu</option>
                                <option value="las_pinas" {{ request('branch') == 'las_pinas' ? 'selected' : '' }}>Las Piñas</option>
                                <option value="davao" {{ request('branch') == 'davao' ? 'selected' : '' }}>Davao</option>
                            </select>
                            @else
                            {{-- * Admins only see their own branch --}}
                            <input type="text" class="form-control-plaintext fw-semibold" readonly value="{{ auth()->user()->branch_name }}">
                            @endif
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                            <label class="form-label small">Status</label>
                            <select name="status" class="form-select">
                                <option value="all" {{ request('status') === 'all' || request('status') === null ? 'selected' : '' }}>All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12 col-12">
                            <label class="form-label small">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search employees..." value="{{ request('search') }}">
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <label class="form-label small">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-outline-primary flex-fill">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Employee</th>
                                    <th style="width: 18%;">Email</th>
                                    <th style="width: 10%;">Role</th>
                                    <th style="width: 12%;">Branch</th>
                                    <th style="width: 12%;">Phone</th>
                                    <th style="width: 8%;">Status</th>
                                    <th style="width: 10%;">Last Login</th>
                                    <th style="width: 15%; min-width: 180px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees ?? [] as $employee)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="employee-avatar me-3">
                                                {{ strtoupper(substr($employee->first_name ?? 'U', 0, 1) . substr($employee->last_name ?? 'N', 0, 1)) }}
                                            </div>
                                            <div>
                                                <strong>{{ $employee->full_name ?? 'Unknown User' }}</strong>
                                                <br><small class="text-muted">ID: {{ $employee->employee_id ?? $employee->id }}</small>
                                                @if($employee->position ?? null)
                                                <br><small class="text-info">{{ $employee->position }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $employee->email ?? 'No email' }}</td>
                                    <td>
                                        @switch($employee->role ?? 'employee')
                                            @case('superadmin')
                                                <span class="badge bg-danger">Super Admin</span>
                                                @break
                                            @case('admin')
                                                <span class="badge bg-warning">Admin</span>
                                                @break
                                            @case('employee')
                                                <span class="badge bg-info">Employee</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($employee->role ?? 'employee') }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-white">{{ $employee->branch_name ?? branch_label($employee->branch) }}</span>
                                        {{-- * Only the superadmin can move employees between branches --}}
                                        @if(auth()->user()->isSuperAdmin())
                                        <button class="btn btn-sm btn-outline-primary ms-1" title="Change branch" onclick="changeBranch({{ $employee->id }}, '{{ $employee->branch ?? 'main' }}')">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @endif
                                    </td>
                                    <td>{{ $employee->phone ?? 'Not provided' }}</td>
                                    <td>
                                        @if($employee->is_active ?? true)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($employee->last_login_at)
                                            {{ $employee->last_login_at->diffForHumans() }}
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if(auth()->user()->role === 'superadmin' || (auth()->user()->role === 'admin' && auth()->user()->branch === $employee->branch))
                                            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            @if($employee->id !== auth()->id())
                                            <button class="btn btn-sm btn-outline-warning" onclick="toggleStatus('{{ $employee->id }}', '{{ $employee->is_active ? 'false' : 'true' }}')">
                                                <i class="bi bi-{{ $employee->is_active ? 'pause' : 'play' }}-circle"></i>
                                            </button>
                                            @endif

                                            {{-- Delete button: superadmin can delete any user; admin can delete users only in their branch and not superadmins or themselves --}}
                                            @if($employee->id !== auth()->id())
                                                @if(auth()->user()->role === 'superadmin' || (auth()->user()->role === 'admin' && auth()->user()->branch === $employee->branch && $employee->role !== 'superadmin'))
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteEmployeeRow('{{ $employee->id }}')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                @endif
                                            @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-people h2 d-block"></i>
                                        No employees found.
                                        @if(auth()->user()->role === 'superadmin' || auth()->user()->role === 'admin')
                                        <a href="{{ route('employees.create') }}">Add your first employee</a>
                                        @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(isset($employees) && method_exists($employees, 'links'))
                    {{ $employees->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Branch Change Modal (superadmin only) -->
@if(auth()->user()->isSuperAdmin())
<div class="modal fade" id="branchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Employee Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="branchForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_branch" class="form-label">Select New Branch</label>
                        <select class="form-select" id="new_branch" name="branch" required>
                            <option value="cebu">Cebu</option>
                            <option value="las_pinas">Las Piñas</option>
                            <option value="davao">Davao</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="change_reason" class="form-label">Reason for Change</label>
                        <textarea class="form-control" id="change_reason" name="reason" rows="3" placeholder="Enter reason for branch change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Branch</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

<script>
// Global functions for employee management
// * Ensure function is globally accessible
window.toggleStatus = function(employeeId, newStatus) {
    // * Handle both boolean and string values
    const shouldActivate = newStatus === true || newStatus === 'true';
    const action = shouldActivate ? 'activate' : 'deactivate';
    const actionTitle = shouldActivate ? 'Activate Employee' : 'Deactivate Employee';
    const actionText = `Are you sure you want to ${action} this employee?`;

    Swal.fire({
        title: actionTitle,
        text: actionText,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: newStatus === 'true' ? '#28a745' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, ${action}!`,
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Processing...',
                text: `${shouldActivate ? 'Activating' : 'Deactivating'} employee`,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`/employees/${employeeId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    is_active: newStatus === true || newStatus === 'true'
                })
            })
            .then(async (response) => {
                const data = await response.json().catch(() => null);

                if (!response.ok || !(data?.success)) {
                    const message = data?.message || 'Unable to update employee status at this time.';
                    throw new Error(message);
                }

                Swal.fire({
                    title: 'Success!',
                    text: `Employee ${action}d successfully`,
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
                    text: 'Error updating status: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
};

function changeBranch(employeeId, currentBranch) {
    currentEmployeeId = employeeId;

    const modal = new bootstrap.Modal(document.getElementById('branchModal'));
    const branchSelect = document.getElementById('new_branch');

    // Set current branch as selected
    branchSelect.value = currentBranch;

    modal.show();
}

let currentEmployeeId = null;

document.addEventListener('DOMContentLoaded', function() {
    const branchForm = document.getElementById('branchForm');
    if (branchForm) {
        branchForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const branch = document.getElementById('new_branch').value;
            const reason = document.getElementById('change_reason').value;

            fetch(`/employees/${currentEmployeeId}/branch`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    branch: branch,
                    reason: reason || null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('branchModal'));
                    modal.hide();

                    Swal.fire({
                        title: 'Success!',
                        text: 'Employee branch updated successfully',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error updating branch: ' + (data.message || 'Unknown error'),
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Error updating branch',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        });
    }
});

function deleteEmployeeRow(employeeId) {
    Swal.fire({
        title: 'Delete Employee?',
        html: '<p>Are you sure you want to delete this employee?</p><p class="text-danger"><strong>This action cannot be undone!</strong></p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the employee',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`/employees/${employeeId}/delete`, {
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
                    const message = data?.message || 'Unable to delete employee at this time.';
                    throw new Error(message);
                }

                const successTitle = data.data?.action === 'deactivated' ? 'Deactivated' : 'Deleted!';
                const successMessage = data.message || (data.data?.action === 'deactivated'
                    ? 'Employee deactivated successfully due to existing records.'
                    : 'Employee deleted successfully');

                Swal.fire({
                    title: successTitle,
                    text: successMessage,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            })
            .catch(err => {
                console.error(err);
                Swal.fire({
                    title: 'Error!',
                    text: 'Error deleting employee: ' + (err.message || 'Unknown error'),
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}
</script>

@section('styles')
<style>
.employee-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 16px;
    flex-shrink: 0;
}

/* Ensure Actions column doesn't shrink and buttons are fully visible */
.table td:last-child, .table th:last-child {
    min-width: 200px;
    white-space: nowrap;
    width: 15% !important;
}

/* Ensure action buttons container has proper layout */
.d-flex.gap-1 {
    min-width: 180px;
    flex-wrap: nowrap;
}

/* Ensure action buttons have proper spacing and don't shrink */
.d-flex.gap-1 .btn {
    flex-shrink: 0;
    min-width: 32px;
}

/* Make table responsive with proper overflow */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* Ensure table has minimum width to prevent cramping */
.table {
    min-width: 1200px;
}
</style>
@endsection



@extends('layouts.app')

@section('title', 'Employee Details - ' . $employee->full_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
                            <li class="breadcrumb-item active">{{ $employee->full_name }}</li>
                        </ol>
                    </nav>
                    <h2><i class="bi bi-person-circle"></i> Employee Details</h2>
                </div>
                <div>
                    @if(auth()->user()->isSuperAdmin() || (auth()->user()->isAdmin() && $employee->role === 'employee' && auth()->user()->branch === $employee->branch))
                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit Employee
                    </a>
                    @endif
                    <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Employee Profile Card -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="employee-avatar-large mb-3 mx-auto">
                                {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                            </div>
                            <h4 class="mb-1">{{ $employee->full_name }}</h4>
                            @if($employee->position)
                            <p class="text-muted mb-2">{{ $employee->position }}</p>
                            @endif
                            <div class="mb-3">
                                @switch($employee->role)
                                    @case('superadmin')
                                        <span class="badge bg-danger fs-6">Super Admin</span>
                                        @break
                                    @case('admin')
                                        <span class="badge bg-warning fs-6">Admin</span>
                                        @break
                                    @case('employee')
                                        <span class="badge bg-primary fs-6">Employee</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary fs-6">{{ ucfirst($employee->role) }}</span>
                                @endswitch
                            </div>
                            <div class="mb-3">
                                @if($employee->is_active)
                                    <span class="badge bg-success fs-6">Active</span>
                                @else
                                    <span class="badge bg-danger fs-6">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    @if(auth()->user()->isSuperAdmin() || (auth()->user()->isAdmin() && auth()->user()->branch === $employee->branch))
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            @if(auth()->user()->isSuperAdmin() && $employee->id !== auth()->id())
                            <button type="button" class="btn btn-{{ $employee->is_active ? 'danger' : 'success' }} btn-sm w-100 mb-2"
                                    onclick="toggleStatus({{ $employee->id }}, {{ $employee->is_active ? 'false' : 'true' }})">
                                <i class="bi bi-{{ $employee->is_active ? 'pause' : 'play' }}"></i>
                                {{ $employee->is_active ? 'Deactivate' : 'Activate' }} Employee
                            </button>
                            @endif
                            <button type="button" class="btn btn-primary btn-sm w-100 mb-2" onclick="resetPassword({{ $employee->id }})">
                                <i class="bi bi-key"></i> Reset Password
                            </button>
                            <a href="{{ route('attendance.index', ['user_id' => $employee->id]) }}" class="btn btn-info btn-sm w-100">
                                <i class="bi bi-clock-history"></i> View Attendance
                            </a>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Employee Information -->
                <div class="col-lg-8">
                    <!-- Basic Information -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Basic Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Employee ID</label>
                                        <p class="fw-bold">{{ $employee->employee_id ?? $employee->id }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">First Name</label>
                                        <p class="fw-bold">{{ $employee->first_name }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Last Name</label>
                                        <p class="fw-bold">{{ $employee->last_name }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Email</label>
                                        <p class="fw-bold">{{ $employee->email }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Phone</label>
                                        <p class="fw-bold">{{ $employee->phone ?? 'Not provided' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Branch</label>
                                        <p><span class="badge bg-info text-white">{{ $employee->branch_name ?? branch_label($employee->branch) }}</span></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Position</label>
                                        <p class="fw-bold">{{ $employee->position ?? 'Not specified' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Department</label>
                                        <p class="fw-bold">{{ $employee->department ?? 'Not specified' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">Account Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Account Created</label>
                                        <p class="fw-bold">{{ $employee->created_at->format('M d, Y g:i A') }}</p>
                                        <small class="text-muted">{{ $employee->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Last Updated</label>
                                        <p class="fw-bold">{{ $employee->updated_at->format('M d, Y g:i A') }}</p>
                                        <small class="text-muted">{{ $employee->updated_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Last Login</label>
                                        @if($employee->last_login_at)
                                        <p class="fw-bold">{{ $employee->last_login_at->format('M d, Y g:i A') }}</p>
                                        <small class="text-muted">{{ $employee->last_login_at->diffForHumans() }}</small>
                                        @else
                                        <p class="text-muted">Never logged in</p>
                                        @endif
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Email Verified</label>
                                        @if($employee->email_verified_at)
                                        <p><span class="badge bg-success">Verified</span></p>
                                        <small class="text-muted">{{ $employee->email_verified_at->format('M d, Y') }}</small>
                                        @else
                                        <p><span class="badge bg-warning">Not Verified</span></p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    @if(isset($recentActivity) && $recentActivity->count() > 0)
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">Recent Activity</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                @foreach($recentActivity as $activity)
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $activity->action }}</h6>
                                            <p class="mb-1 text-muted">{{ $activity->description }}</p>
                                            <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                        </div>
                                        <small class="text-muted">{{ $activity->created_at->format('M d, Y g:i A') }}</small>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="text-center mt-3">
                                <a href="{{ route('audit-logs.index', ['user_id' => $employee->id]) }}" class="btn btn-outline-primary btn-sm">
                                    View All Activity
                                </a>
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
.employee-avatar-large {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 32px;
}
</style>
@endpush

@push('scripts')
<script>
// * Ensure function is globally accessible
window.toggleStatus = function(employeeId, newStatus) {
    // * Handle both boolean and string values
    const shouldActivate = newStatus === true || newStatus === 'true';
    const action = shouldActivate ? 'activate' : 'deactivate';
    const actionText = shouldActivate ? 'Activate' : 'Deactivate';

    Swal.fire({
        title: `${actionText} Employee?`,
        text: `Are you sure you want to ${action} this employee?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: newStatus === 'true' ? '#198754' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, ${actionText}!`,
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Processing...',
                text: `${actionText}ing employee`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading()
                }
            });

            fetch(`/employees/${employeeId}/status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    is_active: newStatus === true || newStatus === 'true'
                })
            })
            .then(async (response) => {
                const data = await response.json().catch(() => null);

                if (!response.ok || !(data?.success)) {
                    const message = data?.message || `Failed to ${action} employee`;
                    throw new Error(message);
                }

                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#198754'
                }).then(() => {
                    window.location.reload();
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Network error. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
};

// * Ensure function is globally accessible
window.resetPassword = function(employeeId) {
    Swal.fire({
        title: 'Reset Password?',
        text: 'This will generate a new temporary password for the employee.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Reset Password!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Processing...',
                text: 'Resetting password',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading()
                }
            });

            fetch(`/employees/${employeeId}/reset-password`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                }
            })
            .then(async (response) => {
                const data = await response.json().catch(() => null);

                if (!response.ok || !(data?.success)) {
                    const message = data?.message || 'Failed to reset password';
                    throw new Error(message);
                }

                Swal.fire({
                    title: 'Password Reset!',
                    html: `<p>${data.message}</p><p><strong>New Password:</strong> <code>${data.password}</code></p><p class="text-warning">Please save this password and share it securely with the employee.</p>`,
                    icon: 'success',
                    confirmButtonColor: '#198754',
                    confirmButtonText: 'Got it!'
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Network error. Please try again.',
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
</script>
@endpush

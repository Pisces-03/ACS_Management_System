@extends('layouts.app')

@section('title', 'Create Employee')

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
                            <li class="breadcrumb-item active">Create Employee</li>
                        </ol>
                    </nav>
                    <h2><i class="bi bi-person-plus"></i> Create New Employee</h2>
                    <p class="text-muted mb-0">Add a new employee to the system</p>
                </div>
                <div>
                    <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Employee Preview -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Preview</h6>
                        </div>
                        <div class="card-body text-center">
                            <div class="employee-avatar-large mb-3 mx-auto" id="avatar-preview">
                                ??
                            </div>
                            <h5 id="name-preview">Employee Name</h5>
                            <p class="text-muted" id="position-preview">No position</p>
                            <span class="badge bg-info text-white" id="branch-preview">Main</span>
                        </div>
                    </div>
                </div>

                <!-- Create Form -->
                <div class="col-lg-8">
                    <form action="{{ route('employees.store') }}" method="POST" id="employeeForm">
                        @csrf

                        <!-- Basic Information -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Basic Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                                   id="first_name" name="first_name" value="{{ old('first_name') }}"
                                                   required onInput="updatePreview()">
                                            @error('first_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                                   id="last_name" name="last_name" value="{{ old('last_name') }}"
                                                   required onInput="updatePreview()">
                                            @error('last_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                                   id="email" name="email" value="{{ old('email') }}" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone</label>
                                            <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                                   id="phone" name="phone" value="{{ old('phone') }}" 
                                                   pattern="^(\+63)?[0-9]{11}$" maxlength="14" 
                                                   placeholder="09123456789 or +639123456789"
                                                   oninput="validatePhoneNumber(this)">
                                            <small class="form-text text-muted">Enter exactly 11 digits or +63 followed by 11 digits</small>
                                            @error('phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="employee_id" class="form-label">Employee ID</label>
                                            <input type="text" class="form-control @error('employee_id') is-invalid @enderror"
                                                   id="employee_id" name="employee_id" value="{{ old('employee_id') }}">
                                            @error('employee_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Leave empty to auto-generate (SA#### for Super Admin, AD#### for Admin, EM#### for Employee)</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="position" class="form-label">Position</label>
                                            <select class="form-select @error('position') is-invalid @enderror"
                                                    id="position" name="position" onInput="updatePreview()">
                                                <option value="">Select Position</option>
                                                <option value="Project Manager" {{ old('position') == 'Project Manager' ? 'selected' : '' }}>Project Manager</option>
                                                <option value="Technician" {{ old('position') == 'Technician' ? 'selected' : '' }}>Technician</option>
                                                <option value="Admin Staff" {{ old('position') == 'Admin Staff' ? 'selected' : '' }}>Admin Staff</option>
                                                <option value="Project Supervisor" {{ old('position') == 'Project Supervisor' ? 'selected' : '' }}>Project Supervisor</option>
                                                <option value="Technical Coordinator" {{ old('position') == 'Technical Coordinator' ? 'selected' : '' }}>Technical Coordinator</option>
                                                <option value="Operation Manager" {{ old('position') == 'Operation Manager' ? 'selected' : '' }}>Operation Manager</option>
                                            </select>
                                            @error('position')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="department" class="form-label">Department</label>
                                            <select class="form-select @error('department') is-invalid @enderror" id="department" name="department">
                                                <option value="">Select Department</option>
                                                <option value="INSTALLATION TEAM" {{ old('department') == 'INSTALLATION TEAM' ? 'selected' : '' }}>INSTALLATION TEAM</option>
                                                <option value="REPAIR TEAM" {{ old('department') == 'REPAIR TEAM' ? 'selected' : '' }}>REPAIR TEAM</option>
                                                <option value="DISMANTLING TEAM" {{ old('department') == 'DISMANTLING TEAM' ? 'selected' : '' }}>DISMANTLING TEAM</option>
                                                <option value="CHECK UP TEAM" {{ old('department') == 'CHECK UP TEAM' ? 'selected' : '' }}>CHECK UP TEAM</option>
                                                <option value="OFFICE TEAM" {{ old('department') == 'OFFICE TEAM' ? 'selected' : '' }}>OFFICE TEAM</option>
                                            </select>
                                            @error('department')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="branch" class="form-label">Branch</label>
                                            @if(auth()->user()->isSuperAdmin())
                                            <select class="form-select @error('branch') is-invalid @enderror"
                                                    id="branch" name="branch" onchange="updatePreview()">
                                                <option value="cebu" {{ old('branch') == 'cebu' ? 'selected' : '' }}>Cebu</option>
                                                <option value="las_pinas" {{ old('branch') == 'las_pinas' ? 'selected' : '' }}>Las Piñas</option>
                                                <option value="davao" {{ old('branch') == 'davao' ? 'selected' : '' }}>Davao</option>
                                            </select>
                                            @else
                                            <input type="hidden" id="branch" name="branch" value="{{ auth()->user()->branch }}">
                                            <input type="text" class="form-control" id="branch_display" value="{{ auth()->user()->branch_name }}" readonly>
                                            @endif
                                            @error('branch')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Account Settings -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">Account Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Role</label>
                                            @if(auth()->user()->isSuperAdmin())
                                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role">
                                                <option value="superadmin" {{ old('role') == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                                <option value="employee" {{ old('role', 'employee') == 'employee' ? 'selected' : '' }}>Employee</option>
                                            </select>
                                            @else
                                            <input type="text" class="form-control" id="role" name="role" value="employee" readonly>
                                            @endif
                                            @error('role')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="is_active" class="form-label">Status</label>
                                            <select class="form-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active">
                                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            @error('is_active')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                                   id="password" name="password" required oninput="validatePasswordStrength(this)">
                                            <div id="password-strength" class="mt-2" style="display: none;">
                                                <div class="password-requirements">
                                                    <small class="text-muted">Password must contain:</small>
                                                    <ul class="list-unstyled mb-0">
                                                        <li id="req-length" class="text-danger"><i class="bi bi-x-circle"></i> At least 8 characters</li>
                                                        <li id="req-upper" class="text-danger"><i class="bi bi-x-circle"></i> One uppercase letter</li>
                                                        <li id="req-lower" class="text-danger"><i class="bi bi-x-circle"></i> One lowercase letter</li>
                                                        <li id="req-number" class="text-danger"><i class="bi bi-x-circle"></i> One number</li>
                                                        <li id="req-special" class="text-danger"><i class="bi bi-x-circle"></i> One special character (!@#$%^&*(),.?":{}|<>)</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control"
                                                   id="password_confirmation" name="password_confirmation" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('employees.index') }}" class="btn btn-secondary me-2">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Create Employee
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.employee-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 24px;
}
</style>
@endpush

@push('scripts')
<script>
function updatePreview() {
    const firstName = document.getElementById('first_name').value;
    const lastName = document.getElementById('last_name').value;
    const position = document.getElementById('position').value;
    const branch = document.getElementById('branch').value;

    // Update avatar
    const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
    document.getElementById('avatar-preview').textContent = initials || '??';

    // Update name
    document.getElementById('name-preview').textContent = `${firstName} ${lastName}`.trim() || 'Employee Name';

    // Update position
    document.getElementById('position-preview').textContent = position || 'No position';

    // Update branch
    const branchNames = {
        'main': 'Main',
        'cebu': 'Cebu',
        'las_pinas': 'Las Piñas',
        'davao': 'Davao'
    };
    document.getElementById('branch-preview').textContent = branchNames[branch] || 'Main';
}

// Form submission with SweetAlert confirmation
document.getElementById('employeeForm').addEventListener('submit', function(e) {
    e.preventDefault();

    Swal.fire({
        title: 'Create Employee?',
        text: 'Are you sure you want to create this employee account?',
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
                text: 'Please wait while we create the employee account',
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

// Password strength validation
function validatePasswordStrength(input) {
    const password = input.value;
    const strengthDiv = document.getElementById('password-strength');
    
    if (password.length > 0) {
        strengthDiv.style.display = 'block';
        
        // Check each requirement
        const length = password.length >= 8;
        const upper = /[A-Z]/.test(password);
        const lower = /[a-z]/.test(password);
        const number = /\d/.test(password);
        const special = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        
        // Update visual indicators
        updateRequirement('req-length', length);
        updateRequirement('req-upper', upper);
        updateRequirement('req-lower', lower);
        updateRequirement('req-number', number);
        updateRequirement('req-special', special);
    } else {
        strengthDiv.style.display = 'none';
    }
}

function updateRequirement(elementId, isValid) {
    const element = document.getElementById(elementId);
    const icon = element.querySelector('i');
    
    if (isValid) {
        element.className = 'text-success';
        icon.className = 'bi bi-check-circle';
    } else {
        element.className = 'text-danger';
        icon.className = 'bi bi-x-circle';
    }
}

// Phone number validation
function validatePhoneNumber(input) {
    const phone = input.value;
    
    // Remove any non-numeric characters except + at the beginning
    let cleaned = phone.replace(/[^\d+]/g, '');
    
    // If it starts with +63, keep it, otherwise remove +
    if (cleaned.startsWith('+63')) {
        cleaned = cleaned.substring(0, 14); // +63 + 11 digits = 14 chars max
    } else {
        cleaned = cleaned.replace(/^\+/, ''); // Remove + if not +63
        cleaned = cleaned.substring(0, 11); // 11 digits max
    }
    
    input.value = cleaned;
}
</script>
@endpush

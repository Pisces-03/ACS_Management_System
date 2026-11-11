@extends('layouts.app')

@section('title', 'Edit Employee - ' . $employee->full_name)

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
                            <li class="breadcrumb-item"><a href="{{ route('employees.show', $employee) }}">{{ $employee->full_name }}</a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                    <h2><i class="bi bi-pencil-square"></i> Edit Employee</h2>
                </div>
                <div>
                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Details
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Employee Profile Preview -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Preview</h6>
                        </div>
                        <div class="card-body text-center">
                            <div class="employee-avatar-large mb-3 mx-auto" id="avatar-preview">
                                {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                            </div>
                            <h5 id="name-preview">{{ $employee->full_name }}</h5>
                            <p class="text-muted" id="position-preview">{{ $employee->position ?? 'No position' }}</p>
                            <span class="badge bg-info text-white" id="branch-preview">{{ $employee->branch_name ?? branch_label($employee->branch) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="col-lg-8">
                    <form action="{{ route('employees.update', $employee) }}" method="POST" id="employeeForm">
                        @csrf
                        @method('PUT')

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
                                                   id="first_name" name="first_name" value="{{ old('first_name', $employee->first_name) }}"
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
                                                   id="last_name" name="last_name" value="{{ old('last_name', $employee->last_name) }}"
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
                                                   id="email" name="email" value="{{ old('email', $employee->email) }}" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone</label>
                                            <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                                   id="phone" name="phone" value="{{ old('phone', $employee->phone) }}"
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
                                                   id="employee_id" name="employee_id" value="{{ old('employee_id', $employee->employee_id) }}">
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
                                                <option value="Project Manager" {{ old('position', $employee->position) == 'Project Manager' ? 'selected' : '' }}>Project Manager</option>
                                                <option value="Technician" {{ old('position', $employee->position) == 'Technician' ? 'selected' : '' }}>Technician</option>
                                                <option value="Admin Staff" {{ old('position', $employee->position) == 'Admin Staff' ? 'selected' : '' }}>Admin Staff</option>
                                                <option value="Project Supervisor" {{ old('position', $employee->position) == 'Project Supervisor' ? 'selected' : '' }}>Project Supervisor</option>
                                                <option value="Technical Coordinator" {{ old('position', $employee->position) == 'Technical Coordinator' ? 'selected' : '' }}>Technical Coordinator</option>
                                                <option value="Operation Manager" {{ old('position', $employee->position) == 'Operation Manager' ? 'selected' : '' }}>Operation Manager</option>
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
                                                <option value="INSTALLATION TEAM" {{ old('department', $employee->department) == 'INSTALLATION TEAM' ? 'selected' : '' }}>INSTALLATION TEAM</option>
                                                <option value="REPAIR TEAM" {{ old('department', $employee->department) == 'REPAIR TEAM' ? 'selected' : '' }}>REPAIR TEAM</option>
                                                <option value="DISMANTLING TEAM" {{ old('department', $employee->department) == 'DISMANTLING TEAM' ? 'selected' : '' }}>DISMANTLING TEAM</option>
                                                <option value="CHECK UP TEAM" {{ old('department', $employee->department) == 'CHECK UP TEAM' ? 'selected' : '' }}>CHECK UP TEAM</option>
                                                <option value="OFFICE TEAM" {{ old('department', $employee->department) == 'OFFICE TEAM' ? 'selected' : '' }}>OFFICE TEAM</option>
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
                                                <option value="cebu" {{ old('branch', $employee->branch) == 'cebu' ? 'selected' : '' }}>Cebu</option>
                                                <option value="las_pinas" {{ old('branch', $employee->branch) == 'las_pinas' ? 'selected' : '' }}>Las Piñas</option>
                                                <option value="davao" {{ old('branch', $employee->branch) == 'davao' ? 'selected' : '' }}>Davao</option>
                                            </select>
                                            @else
                                            <input type="hidden" id="branch" name="branch" value="{{ $employee->branch }}">
                                            <input type="text" class="form-control" id="branch_display" value="{{ $employee->branch_name ?? branch_label($employee->branch) }}" readonly>
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
                                    @if(auth()->user()->isSuperAdmin())
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Role</label>
                                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role">
                                                <option value="superadmin" {{ old('role', $employee->role) == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                                                <option value="admin" {{ old('role', $employee->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                                                <option value="employee" {{ old('role', $employee->role) == 'employee' ? 'selected' : '' }}>Employee</option>
                                            </select>
                                            @error('role')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    @else
                                    {{-- Admins cannot change role or elevate users; show readonly role for context --}}
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Role</label>
                                            <input type="text" class="form-control" id="role" name="role" value="{{ $employee->role }}" readonly>
                                        </div>
                                    </div>
                                    @endif

                                    @if(auth()->user()->isSuperAdmin() && $employee->id !== auth()->id())
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="is_active" class="form-label">Status</label>
                                            <select class="form-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active">
                                                <option value="1" {{ old('is_active', $employee->is_active) ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ !old('is_active', $employee->is_active) ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            @error('is_active')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                <!-- Password Change Section -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="border-top pt-3">
                                            <h6 class="text-muted">Change Password (Optional)</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="password" class="form-label">New Password</label>
                                                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                                                               id="password" name="password" oninput="validatePasswordStrength(this)">
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
                                                        <div class="form-text">Leave empty to keep current password</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                                        <input type="password" class="form-control"
                                                               id="password_confirmation" name="password_confirmation">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        @if(auth()->user()->isSuperAdmin() && $employee->id !== auth()->id())
                                        <button type="button" class="btn btn-danger" onclick="deleteEmployee()">
                                            <i class="bi bi-trash"></i> Delete Employee
                                        </button>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary me-2">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Update Employee
                                        </button>
                                    </div>
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
// Role change warning
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const originalRole = '{{ $employee->role }}';
    
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            if (this.value !== originalRole) {
                Swal.fire({
                    icon: 'info',
                    title: 'Role Change Detected',
                    text: 'Changing the role will automatically generate a new Employee ID with the appropriate prefix.',
                    confirmButtonText: 'Understood'
                });
            }
        });
    }
});

function updatePreview() {
    const firstName = document.getElementById('first_name').value;
    const lastName = document.getElementById('last_name').value;
    const position = document.getElementById('position').value;
    const branch = document.getElementById('branch').value;

    // Update avatar
    const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
    document.getElementById('avatar-preview').textContent = initials;

    // Update name
    document.getElementById('name-preview').textContent = `${firstName} ${lastName}`.trim() || 'Employee Name';

    // Update position
    document.getElementById('position-preview').textContent = position || 'No position';

    // Update branch: if select exists use selected option text, otherwise use display/input value or mapping
    var branchPreview = document.getElementById('branch-preview');
    var branchSelect = document.getElementById('branch');
    if (branchSelect && branchSelect.tagName.toLowerCase() === 'select') {
        var opt = branchSelect.options[branchSelect.selectedIndex];
        branchPreview.textContent = opt ? opt.text : (document.getElementById('branch_display') ? document.getElementById('branch_display').value : branch);
    } else {
        // Hidden input case for admins; prefer branch_display if present
        branchPreview.textContent = document.getElementById('branch_display') ? document.getElementById('branch_display').value : branch;
    }
}

function deleteEmployee() {
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
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading()
                }
            });

            // Create a form to submit the delete request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("employees.destroy", $employee) }}';

            // Add CSRF token
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrfToken);

            // Add method override
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            form.appendChild(methodField);

            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Form submission with SweetAlert confirmation
document.getElementById('employeeForm').addEventListener('submit', function(e) {
    e.preventDefault();

    Swal.fire({
        title: 'Update Employee?',
        text: 'Are you sure you want to save these changes?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Update!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Updating...',
                text: 'Please wait while we save the changes',
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

@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="bi bi-person-circle"></i> My Profile</h4>
                </div>
                <div class="card-body">
                    <!-- Profile Information Form -->
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="employee_id" class="form-label">Employee ID</label>
                                    <input type="text" class="form-control" id="employee_id" value="{{ $user->employee_id }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" class="form-control" id="role" value="{{ ucfirst($user->role) }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                           id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                           id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                           id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
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
                                    <label for="branch" class="form-label">Branch</label>
                                    <input type="text" class="form-control" id="branch" value="{{ $user->branch_name }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="position" class="form-label">Position</label>
                                    <input type="text" class="form-control" id="position" value="{{ $user->position }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Profile
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <!-- Change Password Form -->
                    <h5><i class="bi bi-shield-lock"></i> Change Password</h5>

                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                   id="current_password" name="current_password" required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
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
                                    <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control"
                                           id="password_confirmation" name="password_confirmation" required>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-outline-warning">
                                <i class="bi bi-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Account Information Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Account Created:</strong><br>{{ $user->created_at->format('F d, Y \a\t h:i A') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Last Updated:</strong><br>{{ $user->updated_at->format('F d, Y \a\t h:i A') }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Account Status:</strong><br>
                                @if($user->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Email Verified:</strong><br>
                                @if($user->email_verified_at)
                                    <span class="badge bg-success">Verified</span>
                                @else
                                    <span class="badge bg-warning">Not Verified</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
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

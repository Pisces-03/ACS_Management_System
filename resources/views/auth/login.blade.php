<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Cold Storage Management System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .login-header {
            background: linear-gradient(45deg, #0d6efd, #0b5ed7);
            color: white;
            border-radius: 15px 15px 0 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card">
                    <div class="login-header text-center py-4">
                        <i class="bi bi-snow2 fs-1 mb-2"></i>
                        <h4 class="mb-0">Cold Storage Management</h4>
                        <p class="mb-0 opacity-75">Asia Cold Storage Corporation</p>
                    </div>

                    <div class="card-body p-4">
                        @if ($errors->any())
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            {{ $errors->first() }}
                        </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope"></i> Email Address
                                </label>
                                <input
                                    type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                >
                                @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i> Password
                                </label>
                                <input
                                    type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    id="password"
                                    name="password"
                                    required
                                >
                                @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right"></i> Login
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <a href="#" class="text-decoration-none" onclick="showForgotPasswordModal()">
                                    <i class="bi bi-question-circle"></i> Forgot Password?
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="card-footer text-center text-muted">
                        <small>
                            © {{ date('Y') }} Asia Cold Storage Corporation
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">
                        <i class="bi bi-key"></i> Forgot Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="bi bi-person-badge text-primary" style="font-size: 3rem;"></i>
                        <h6 class="mt-3 mb-3">Password Recovery</h6>
                        <p class="text-muted">
                            For security reasons, password reset must be handled by the system administrator.
                        </p>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Please contact your administrator:</strong><br>
                            • Email: shyleralemania022402@gmail.com<br>
                            • Provide your employee ID and email address
                        </div>
                        <p class="small text-muted">
                            The administrator will verify your identity and provide you with a temporary password.
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function showForgotPasswordModal() {
            const modal = new bootstrap.Modal(document.getElementById('forgotPasswordModal'));
            modal.show();
        }
    </script>
</body>
</html>

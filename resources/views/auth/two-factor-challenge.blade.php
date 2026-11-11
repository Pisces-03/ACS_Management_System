<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Two-Factor Authentication - ACS Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link href="{{ asset('css/main.css') }}" rel="stylesheet">
    
    <style>
        .auth-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #e8f5e9 0%, #f0f9ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .auth-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 450px;
            width: 100%;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }
        
        .auth-title {
            color: #1e3c72;
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        
        .auth-subtitle {
            color: #6c757d;
            font-size: 1rem;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #1e3c72;
            box-shadow: 0 0 0 0.2rem rgba(30, 60, 114, 0.25);
        }
        
        .btn-verify {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 60, 114, 0.3);
        }
        
        .btn-resend {
            background: transparent;
            border: 2px solid #6c757d;
            color: #6c757d;
            border-radius: 0.75rem;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .btn-resend:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-logout {
            background: transparent;
            border: 2px solid #dc3545;
            color: #dc3545;
            border-radius: 0.75rem;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-logout:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-1px);
        }
        
        .alert {
            border-radius: 0.75rem;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        .help-text {
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        
        .security-note {
            background: #f8f9fa;
            border-left: 4px solid #1e3c72;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #495057;
        }
        
        .code-input {
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 0.5rem;
            font-weight: 600;
        }
        
        @media (max-width: 576px) {
            .auth-card {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .auth-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h1 class="auth-title">Two-Factor Authentication</h1>
                <p class="auth-subtitle">Enter the 6-digit code sent to your email</p>
            </div>

            @if(session('status'))
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('2fa.verify') }}">
                @csrf
                <div class="form-floating">
                    <input 
                        id="code" 
                        name="code" 
                        type="text" 
                        inputmode="numeric" 
                        pattern="[0-9]*" 
                        maxlength="6" 
                        class="form-control code-input" 
                        placeholder="000000"
                        required 
                        autocomplete="one-time-code"
                        autofocus
                    />
                    <label for="code">Enter 6-digit code</label>
                </div>

                <button type="submit" class="btn btn-verify">
                    <i class="bi bi-shield-check me-2"></i>Verify Code
                </button>
            </form>

            <form method="POST" action="{{ route('2fa.resend') }}">
                @csrf
                <button type="submit" class="btn btn-resend">
                    <i class="bi bi-arrow-clockwise me-2"></i>Resend Code
                </button>
            </form>

            <div class="security-note">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Security Note:</strong> This code expires in {{ $expiresMinutes ?? 2 }} minutes. If you didn't receive an email, check your spam folder.
            </div>

            <div class="help-text">
                <p>If you did not receive an email, check your spam folder.</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-logout">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-focus on code input
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('code');
            if (codeInput) {
                codeInput.focus();
            }
        });
        
        // Auto-format code input (add spaces for better readability)
        document.getElementById('code').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length > 6) {
                value = value.substring(0, 6);
            }
            e.target.value = value;
        });
    </script>
</body>
</html>








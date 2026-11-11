<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Expired - Cold Storage Management System</title>
    
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
        .error-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card error-card">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="bi bi-exclamation-triangle text-warning" style="font-size: 5rem;"></i>
                        </div>
                        
                        <h1 class="display-4 text-warning">401</h1>
                        <h3 class="mb-3">Session Expired</h3>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-clock"></i>
                            <strong>Authentication Required</strong><br>
                            Your session has expired or you are not logged in.
                        </div>
                        
                        <p class="text-muted mb-4">
                            For security reasons, you need to log in again to access the system.
                        </p>
                        
                        <div class="d-grid gap-2">
                            <a href="/login" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> Login Again
                            </a>
                        </div>
                        
                        <div class="mt-4">
                            <small class="text-muted">
                                <i class="bi bi-shield-check"></i>
                                Your security is important to us. Sessions expire after a period of inactivity.
                            </small>
                        </div>
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
</body>
</html>

@extends('layouts.app')

@section('title', 'Access Denied')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="bi bi-shield-exclamation text-danger" style="font-size: 5rem;"></i>
                    </div>
                    
                    <h1 class="display-4 text-danger">403</h1>
                    <h3 class="mb-3">Access Denied</h3>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Insufficient Privileges</strong><br>
                        You do not have permission to access this page.
                    </div>
                    
                    <p class="text-muted mb-4">
                        This page is restricted to users with specific roles. 
                        If you believe you should have access to this page, please contact your administrator.
                    </p>
                    
                    <div class="row text-start">
                        <div class="col-md-6">
                            <h6><i class="bi bi-person-badge"></i> Your Current Role:</h6>
                            @auth
                            <span class="badge bg-secondary fs-6">{{ ucfirst(optional(auth()->user())->role ?? "guest") }}</span>
                            @else
                            <span class="badge bg-secondary fs-6">guest</span>
                            @endauth
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-building"></i> Your Branch:</h6>
                            @auth
                            <span class="badge bg-primary fs-6">{{ optional(auth()->user())->branch_name ?? "N/A" }}</span>
                            @else
                            <span class="badge bg-primary fs-6">N/A</span>
                            @endauth
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            <i class="bi bi-house"></i> Go to Dashboard
                        </a>
                        <button onclick="history.back()" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Go Back
                        </button>
                    </div>
                    
                    <div class="mt-4">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i>
                            Need help? Contact your system administrator or IT support.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

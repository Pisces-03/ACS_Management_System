@extends('layouts.app')

@section('title', 'Audit Log Details')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-shield-lock"></i> Audit Log Details</h2>
                <a href="{{ route('audit-logs.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Logs
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Log Information</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <th style="width: 220px;">Date / Time</th>
                                    <td>{{ $auditLog->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>User</th>
                                    <td>{{ $auditLog->user ? $auditLog->user->full_name : 'Unknown' }}</td>
                                </tr>
                                <tr>
                                    <th>Action</th>
                                    <td>{{ $auditLog->action }}</td>
                                </tr>
                                <tr>
                                    <th>Model Type</th>
                                    <td>{{ $auditLog->model_type ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Model ID</th>
                                    <td>{{ $auditLog->model_id ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>IP Address</th>
                                    <td>{{ $auditLog->ip_address ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td><pre class="mb-0" style="white-space: pre-wrap;">{{ $auditLog->description }}</pre></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



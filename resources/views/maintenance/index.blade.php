@extends('layouts.app')

@section('title', 'Maintenance Management')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <h2 class="mb-0"><i class="bi bi-tools"></i> Maintenance Management</h2>
                @if(auth()->user()->role === 'employee')
                <a href="{{ route('maintenance.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Submit Maintenance Findings
                </a>
                @endif
            </div>

            <!-- Maintenance Statistics -->
            <div class="row mb-4 g-3">
                <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">Total Logs</h5>
                                    <h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-clipboard-data fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">This Month</h5>
                                    <h3 class="mb-0">{{ $stats['this_month'] ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-calendar-month fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">Completed</h5>
                                    <h3 class="mb-0">{{ $stats['completed'] ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-check-circle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance Logs -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Maintenance Logs</h5>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="row g-3 mb-3">
                        <div class="col-md-2">
                            <select name="type" class="form-select">
                                <option value="">All Types</option>
                                <option value="installation" {{ request('type') == 'installation' ? 'selected' : '' }}>Installation</option>
                                <option value="reinstallation" {{ request('type') == 'reinstallation' ? 'selected' : '' }}>Re-installation</option>
                                <option value="repair" {{ request('type') == 'repair' ? 'selected' : '' }}>Repair</option>
                                <option value="dismantling" {{ request('type') == 'dismantling' ? 'selected' : '' }}>Dismantling</option>
                                <option value="checkup" {{ request('type') == 'checkup' ? 'selected' : '' }}>Check Up</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Search equipment..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="{{ route('maintenance.index') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Equipment</th>
                                    <th>Type</th>
                                    <th>Technician</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($maintenanceLogs ?? [] as $log)
                                <tr>
                                    <td>{{ $log->performed_at ? $log->performed_at->format('M d, Y') : ($log->created_at ? $log->created_at->format('M d, Y') : 'N/A') }}</td>
                                    <td>{{ $log->equipment_name ?: 'Unknown Equipment' }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ ucfirst($log->type ?? 'general') }}</span>
                                    </td>
                                    <td>{{ $log->employee->full_name ?? 'Unassigned' }}</td>
                                    <td>
                                        @switch($log->status ?? 'pending')
                                            @case('completed')
                                                <span class="badge bg-success">Completed</span>
                                                @break
                                            @case('in_progress')
                                                <span class="badge bg-primary">In Progress</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning">Pending</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($log->status ?? 'pending') }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('maintenance.show', $log) }}" class="btn btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                                            <button type="button" class="btn btn-outline-danger" onclick="deleteMaintenanceLog({{ $log->id }})" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-tools h2 d-block"></i>
                                        No maintenance logs found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(isset($maintenanceLogs) && method_exists($maintenanceLogs, 'links'))
                    {{ $maintenanceLogs->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deleteMaintenanceLog(id) {
    Swal.fire({
        title: 'Delete Maintenance Log?',
        text: 'This action cannot be undone. Are you sure you want to delete this maintenance log?',
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
                text: 'Please wait while we delete the maintenance log',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading()
                }
            });

            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/maintenance/${id}`;

            // Add CSRF token
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrfToken);

            // Add method spoofing for DELETE
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            document.body.appendChild(form);
            form.submit();
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

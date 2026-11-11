@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="bi bi-clipboard-data"></i> Audit Logs</h2>
                    <p class="text-muted mb-0">Track all system activities and user actions</p>
                </div>
                @if(auth()->user()->isSuperAdmin())
                <div>
                    <button type="button" class="btn btn-outline-danger" onclick="confirmClearLogs()">
                        <i class="bi bi-trash"></i> Clear Old Logs
                    </button>
                    <a href="{{ route('audit-logs.export') }}" class="btn btn-outline-success">
                        <i class="bi bi-download"></i> Export
                    </a>
                </div>
                @endif
            </div>

            <!-- Filter Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">User</label>
                            <select name="user_id" class="form-select">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->full_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Action</label>
                            <select name="action" class="form-select">
                                <option value="">All Actions</option>
                                @foreach($actions as $action)
                                <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $action)) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Model Type</label>
                            <select name="model_type" class="form-select">
                                <option value="">All Types</option>
                                @foreach($modelTypes as $type)
                                <option value="{{ $type }}" {{ request('model_type') == $type ? 'selected' : '' }}>
                                    {{ ucfirst(class_basename($type)) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">From Date</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block w-100">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Audit Logs Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Activity Log</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Model</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditLogs as $log)
                                <tr>
                                    <td>
                                        <small class="text-muted">{{ $log->created_at->format('M d, Y') }}</small>
                                        <br>{{ $log->created_at->format('H:i:s') }}
                                        <br><small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @if($log->user)
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2">
                                                {{ strtoupper(substr($log->user->first_name, 0, 1) . substr($log->user->last_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <strong>{{ $log->user->full_name }}</strong>
                                                <br><small class="text-muted">{{ $log->user->email }}</small>
                                                <br><span class="badge bg-{{ $log->user->role == 'superadmin' ? 'danger' : ($log->user->role == 'admin' ? 'warning' : 'primary') }}">
                                                    {{ ucfirst($log->user->role) }}
                                                </span>
                                            </div>
                                        </div>
                                        @else
                                        <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($log->action)
                                            @case('created')
                                                <span class="badge bg-success">Created</span>
                                                @break
                                            @case('updated')
                                                <span class="badge bg-info">Updated</span>
                                                @break
                                            @case('deleted')
                                                <span class="badge bg-danger">Deleted</span>
                                                @break
                                            @case('login')
                                                <span class="badge bg-primary">Login</span>
                                                @break
                                            @case('logout')
                                                <span class="badge bg-secondary">Logout</span>
                                                @break
                                            @case('time_in')
                                                <span class="badge bg-success">Time In</span>
                                                @break
                                            @case('time_out')
                                                <span class="badge bg-warning">Time Out</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @if($log->model_type)
                                        <span class="badge bg-light text-dark">{{ class_basename($log->model_type) }}</span>
                                        @if($log->model_id)
                                        <br><small class="text-muted">ID: {{ $log->model_id }}</small>
                                        @endif
                                        @else
                                        <span class="text-muted">--</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;" title="{{ $log->description }}">
                                            {{ $log->description }}
                                        </div>
                                        @if($log->changes)
                                        <br><small class="text-muted">
                                            <i class="bi bi-info-circle"></i> Has change details
                                        </small>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $log->ip_address ?? 'N/A' }}</small>
                                        @if($log->user_agent)
                                        <br><small class="text-muted" title="{{ $log->user_agent }}">
                                            @if(str_contains($log->user_agent, 'Chrome'))
                                                <i class="bi bi-browser-chrome"></i> Chrome
                                            @elseif(str_contains($log->user_agent, 'Firefox'))
                                                <i class="bi bi-browser-firefox"></i> Firefox
                                            @elseif(str_contains($log->user_agent, 'Safari'))
                                                <i class="bi bi-browser-safari"></i> Safari
                                            @elseif(str_contains($log->user_agent, 'Edge'))
                                                <i class="bi bi-browser-edge"></i> Edge
                                            @else
                                                <i class="bi bi-globe"></i> Browser
                                            @endif
                                        </small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" onclick="viewDetails({{ $log->id }})" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            @if($log->changes)
                                            <button type="button" class="btn btn-outline-info" onclick="viewChanges({{ $log->id }})" title="View Changes">
                                                <i class="bi bi-list-ul"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-clipboard-data h2"></i>
                                        <p>No audit logs found.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    {{ $auditLogs->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audit Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 14px;
    flex-shrink: 0;
}
</style>
@endpush

@push('scripts')
<script>
function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value === null || value === undefined ? '' : String(value);
    return div.innerHTML;
}

// * Show saved dates in Philippine time, e.g. "Oct 28, 2026 11:32:33 PM"
function formatAuditDate(value) {
    const dateOnly = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (dateOnly) {
        return new Date(Date.UTC(dateOnly[1], dateOnly[2] - 1, dateOnly[3]))
            .toLocaleDateString('en-US', { timeZone: 'UTC', month: 'short', day: 'numeric', year: 'numeric' });
    }
    if (!/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/.test(value)) return null;

    const date = new Date(value);
    if (isNaN(date)) return null;
    const options = { timeZone: 'Asia/Manila', month: 'short', day: 'numeric', year: 'numeric' };
    const time = date.toLocaleTimeString('en-US', { timeZone: 'Asia/Manila', hour12: false });
    // * Midnight in Manila means the value was a plain date
    if (time === '00:00:00' || time === '24:00:00') return date.toLocaleDateString('en-US', options);
    return date.toLocaleString('en-US', { ...options, hour: 'numeric', minute: '2-digit', second: '2-digit' });
}

function formatAuditValue(value) {
    if (value === null || value === undefined || value === '') return '<span class="text-muted">—</span>';
    if (typeof value === 'object') return `<code>${escapeHtml(JSON.stringify(value))}</code>`;
    if (typeof value === 'string') {
        const date = formatAuditDate(value);
        if (date) return escapeHtml(date);
    }
    return escapeHtml(value);
}

// * Before/After table of the fields that were saved with the log
function buildChangesTable(oldValues, newValues) {
    const before = oldValues || {};
    const after = newValues || {};
    const fields = [...new Set([...Object.keys(before), ...Object.keys(after)])];
    if (fields.length === 0) return '';

    const rows = fields.map(field => {
        const changed = JSON.stringify(before[field]) !== JSON.stringify(after[field]);
        return `<tr class="${changed && oldValues ? 'table-warning' : ''}">
            <td><strong>${escapeHtml(field.replace(/_/g, ' '))}</strong></td>
            ${oldValues ? `<td>${formatAuditValue(before[field])}</td>` : ''}
            <td>${formatAuditValue(after[field])}</td>
        </tr>`;
    }).join('');

    return `
        <div class="row">
            <div class="col-12">
                <h6>Changes</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Field</th>${oldValues ? '<th>Before</th>' : ''}<th>${oldValues ? 'After' : 'Value'}</th></tr></thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
}

function viewDetails(logId) {
    fetch(`/audit-logs/${logId}`, { headers: { 'Accept': 'application/json' } })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            document.getElementById('detailsContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Basic Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>ID:</strong></td><td>${escapeHtml(data.id)}</td></tr>
                            <tr><td><strong>User:</strong></td><td>${data.user ? `${escapeHtml(data.user.full_name)}<br><small class="text-muted">${escapeHtml(data.user.email)}</small>` : 'System'}</td></tr>
                            <tr><td><strong>Action:</strong></td><td>${escapeHtml(data.action)}</td></tr>
                            <tr><td><strong>Record:</strong></td><td>${data.model_type ? `${escapeHtml(data.model_type)} #${escapeHtml(data.model_id)}` : 'N/A'}</td></tr>
                            <tr><td><strong>Timestamp:</strong></td><td>${escapeHtml(data.created_at || 'N/A')}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Technical Details</h6>
                        <table class="table table-sm">
                            <tr><td><strong>IP Address:</strong></td><td>${escapeHtml(data.ip_address || 'N/A')}</td></tr>
                            <tr><td><strong>User Agent:</strong></td><td><small>${escapeHtml(data.user_agent || 'N/A')}</small></td></tr>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6>Description</h6>
                        <p class="border p-3 bg-light">${escapeHtml(data.description)}</p>
                    </div>
                </div>
                ${buildChangesTable(data.old_values, data.new_values)}
            `;
            new bootstrap.Modal(document.getElementById('detailsModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error!',
                text: 'Failed to load audit log details',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        });
}

function viewChanges(logId) {
    viewDetails(logId); // Same as view details for now
}

function confirmClearLogs() {
    Swal.fire({
        title: 'Clear Old Audit Logs?',
        text: 'This will permanently delete audit logs older than 90 days. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Clear Old Logs!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Clearing Logs...',
                text: 'Please wait while we clear old audit logs',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading()
                }
            });

            fetch('/audit-logs/clear-old', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: `Cleared ${data.count} old audit logs`,
                        icon: 'success',
                        confirmButtonColor: '#198754'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to clear old logs',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Network error. Please try again.',
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

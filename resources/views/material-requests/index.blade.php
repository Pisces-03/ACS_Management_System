@extends('layouts.app')

@section('title', 'Material Requests')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-box-seam"></i> Material Requests</h2>
                <a href="{{ route('material-requests.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New Request
                </a>
            </div>

            <!-- Request Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5>Total Requests</h5>
                            <h3>{{ $stats['total'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5>Pending</h5>
                            <h3>{{ $stats['pending'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5>Approved</h5>
                            <h3>{{ $stats['approved'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h5>Rejected</h5>
                            <h3>{{ $stats['rejected'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Material Requests -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Material Requests</h5>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="row g-3 mb-3">
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Search materials..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="{{ route('material-requests.index') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Material</th>
                                    <th>Quantity</th>
                                    <th>Requested By</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materialRequests ?? [] as $request)
                                <tr>
                                    <td>{{ $request->created_at ? $request->created_at->format('M d, Y') : 'N/A' }}</td>
                                    <td>
                                        @php
                                            $materials = $request->materials ?? [];
                                            $firstMaterial = !empty($materials) ? $materials[0] : null;
                                        @endphp
                                        <strong>{{ $firstMaterial['name'] ?? 'Unknown Material' }}</strong>
                                        @if(!empty($firstMaterial['description']))
                                        <br><small class="text-muted">{{ Str::limit($firstMaterial['description'], 50) }}</small>
                                        @endif
                                        @if(count($materials) > 1)
                                        <br><small class="text-muted">+{{ count($materials) - 1 }} more item(s)</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($firstMaterial)
                                            {{ $firstMaterial['quantity'] ?? 0 }} {{ $firstMaterial['unit'] ?? 'pcs' }}
                                        @else
                                            0 pcs
                                        @endif
                                    </td>
                                    <td>{{ $request->requestedBy->full_name ?? 'Unknown' }}</td>
                                    <td>
                                        @switch($request->status ?? 'pending')
                                            @case('approved')
                                                <span class="badge bg-success">Approved</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                                @break
                                            @case('completed')
                                                <span class="badge bg-primary">Completed</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning">Pending</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($request->status ?? 'pending') }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @switch($request->priority ?? 'medium')
                                            @case('high')
                                                <span class="badge bg-danger">High</span>
                                                @break
                                            @case('medium')
                                                <span class="badge bg-warning">Medium</span>
                                                @break
                                            @case('low')
                                                <span class="badge bg-info">Low</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($request->priority ?? 'medium') }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('material-requests.show', $request) }}" class="btn btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if($request->status === 'pending' && (auth()->user()->role === 'superadmin' || (auth()->user()->role === 'admin' && auth()->user()->branch === $request->branch)))
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-success" onclick="updateStatus({{ $request->id }}, 'approved')">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="updateStatus({{ $request->id }}, 'rejected')">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-box-seam h2 d-block"></i>
                                        No material requests found.
                                        <a href="{{ route('material-requests.create') }}">Create your first request</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(isset($materialRequests) && method_exists($materialRequests, 'links'))
                    {{ $materialRequests->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Request Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="statusForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Reason (Optional for approval, Required for rejection)</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" placeholder="Enter reason for approval/rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentRequestId = null;
let currentStatus = null;

function updateStatus(requestId, status) {
    currentRequestId = requestId;
    currentStatus = status;

    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    const title = document.querySelector('#statusModal .modal-title');
    const reasonField = document.getElementById('rejection_reason');

    if (status === 'approved') {
        title.textContent = 'Approve Request';
        reasonField.placeholder = 'Enter approval notes (optional)...';
        reasonField.required = false;
    } else {
        title.textContent = 'Reject Request';
        reasonField.placeholder = 'Enter rejection reason (required)...';
        reasonField.required = true;
    }

    modal.show();
}

document.getElementById('statusForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const reason = document.getElementById('rejection_reason').value;

    if (currentStatus === 'rejected' && !reason.trim()) {
        alert('Rejection reason is required');
        return;
    }

    fetch(`/material-requests/${currentRequestId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            status: currentStatus,
            rejection_reason: reason || null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating status: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating status');
    });
});
</script>
@endsection

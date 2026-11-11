@extends('layouts.app')

@section('title', 'Pending Approvals')

@push('head')
<script>
// * Define functions IMMEDIATELY in head section to ensure they're available before page loads
// * These will be redefined later with full implementation, but this prevents "not defined" errors
(function() {
    'use strict';
    
    // * Queue for pending calls if openApprovalModal isn't ready yet
    var pendingCalls = [];
    
    window.approveRequest = function(requestId) {
        if (typeof openApprovalModal === 'function') {
            openApprovalModal(requestId, 'approve');
        } else {
            // * Queue the call for when openApprovalModal is ready
            pendingCalls.push({requestId: requestId, action: 'approve'});
            // * Try again after a short delay
            setTimeout(function() {
                if (typeof openApprovalModal === 'function') {
                    openApprovalModal(requestId, 'approve');
                }
            }, 100);
        }
    };

    window.rejectRequest = function(requestId) {
        if (typeof openApprovalModal === 'function') {
            openApprovalModal(requestId, 'reject');
        } else {
            // * Queue the call for when openApprovalModal is ready
            pendingCalls.push({requestId: requestId, action: 'reject'});
            // * Try again after a short delay
            setTimeout(function() {
                if (typeof openApprovalModal === 'function') {
                    openApprovalModal(requestId, 'reject');
                }
            }, 100);
        }
    };
    
    // * Store pending calls for processing later
    window._pendingApprovalCalls = pendingCalls;
})();
</script>
@endpush

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="mb-4">
                <h2><i class="bi bi-clipboard-check"></i> Pending Approvals</h2>
            </div>

            <!-- Request Statistics (click a box to list those requests) -->
            @php
                $statusBoxes = [
                    'all' => ['Total Requests', 'bg-info', $stats['total'] ?? 0],
                    'pending' => ['Pending Approval', 'bg-warning', $stats['pending'] ?? 0],
                    'approved' => ['Approved', 'bg-success', $stats['approved'] ?? 0],
                    'rejected' => ['Rejected', 'bg-danger', $stats['rejected'] ?? 0],
                    'ordered' => ['Ordered', 'bg-primary', $stats['ordered'] ?? 0],
                    'received' => ['Received', 'bg-secondary', $stats['received'] ?? 0],
                ];
                $statusLabels = ['all' => 'All Status', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'ordered' => 'Ordered', 'received' => 'Received'];
            @endphp
            <div class="row mb-4 g-3">
                @foreach($statusBoxes as $key => [$label, $color, $count])
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="{{ route('pending-approvals.index', ['status' => $key]) }}" class="text-decoration-none">
                            <div class="card {{ $color }} text-white h-100 {{ $status === $key ? 'border border-3 border-dark' : '' }}">
                                <div class="card-body">
                                    <h6>{{ $label }}</h6>
                                    <h3 class="mb-0">{{ $count }}</h3>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <!-- Material Requests -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Material Requests
                        <span class="badge {{ $statusBoxes[$status][1] ?? 'bg-secondary' }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="row g-3 mb-3">
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                @foreach($statusLabels as $value => $label)
                                    <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="priority" class="form-select">
                                <option value="">All Priorities</option>
                                <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                                <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search by request number or employee..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Request #</th>
                                    <th>Date</th>
                                    <th>Requested By</th>
                                    <th>Materials</th>
                                    <th>Priority</th>
                                    <th>Estimated Cost</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materialRequests ?? [] as $request)
                                <tr>
                                    <td>
                                        <strong>{{ $request->request_number }}</strong>
                                        @if($request->task)
                                            <br><small class="text-muted">Task: {{ $request->task->title ?? 'N/A' }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $request->created_at ? $request->created_at->format('M d, Y') : 'N/A' }}
                                        @if($request->needed_by)
                                            <br><small class="text-muted">Needed: {{ \Carbon\Carbon::parse($request->needed_by)->format('M d, Y') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $request->requestedBy->full_name ?? 'Unknown' }}</strong>
                                        <br><small class="text-muted">{{ $request->branch_name ?? $request->branch }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $materials = $request->materials ?? [];
                                            $firstMaterial = !empty($materials) ? $materials[0] : null;
                                        @endphp
                                        @if($firstMaterial)
                                            <strong>{{ $firstMaterial['name'] ?? 'Unknown Material' }}</strong>
                                            <br><small class="text-muted">Qty: {{ $firstMaterial['quantity'] ?? 0 }} {{ $firstMaterial['unit'] ?? 'pcs' }}</small>
                                            @if(count($materials) > 1)
                                                <br><small class="text-info">+{{ count($materials) - 1 }} more item(s)</small>
                                            @endif
                                        @else
                                            <span class="text-muted">No materials</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($request->priority ?? 'medium')
                                            @case('urgent')
                                                <span class="badge bg-danger">Urgent</span>
                                                @break
                                            @case('high')
                                                <span class="badge bg-warning">High</span>
                                                @break
                                            @case('medium')
                                                <span class="badge bg-info">Medium</span>
                                                @break
                                            @case('low')
                                                <span class="badge bg-secondary">Low</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($request->priority ?? 'medium') }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @if($request->total_estimated_cost)
                                            <strong>₱{{ number_format($request->total_estimated_cost, 2) }}</strong>
                                        @else
                                            <span class="text-muted">Not specified</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($request->status ?? 'pending')
                                            @case('approved')
                                                <span class="badge bg-success">Approved</span>
                                                @if($request->approved_at)
                                                    <br><small class="text-muted">{{ \Carbon\Carbon::parse($request->approved_at)->format('M d, Y') }}</small>
                                                @endif
                                                @break
                                            @case('rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                                @if($request->approved_at)
                                                    <br><small class="text-muted">{{ \Carbon\Carbon::parse($request->approved_at)->format('M d, Y') }}</small>
                                                @endif
                                                @break
                                            @case('ordered')
                                                <span class="badge bg-primary">Ordered</span>
                                                @break
                                            @case('received')
                                                <span class="badge bg-info">Received</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning">Pending</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($request->status ?? 'pending') }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('material-requests.show', $request) }}" class="btn btn-outline-primary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if($request->status === 'pending' && (auth()->user()->role === 'superadmin' || (auth()->user()->role === 'admin' && auth()->user()->branch === $request->branch)))
                                                <button type="button" class="btn btn-outline-success approve-btn" data-request-id="{{ $request->id }}" title="Approve">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger reject-btn" data-request-id="{{ $request->id }}" title="Reject">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-clipboard-check h2 d-block"></i>
                                        No material requests found.
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

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalTitle">Approve Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approvalForm">
                <div class="modal-body">
                    <input type="hidden" id="requestId" name="request_id">
                    <input type="hidden" id="actionType" name="action_type">
                    
                    <div class="mb-3">
                        <label for="approval_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="approval_notes" name="approval_notes" rows="3" placeholder="Enter approval notes (optional)..."></textarea>
                    </div>
                    
                    <div class="mb-3" id="rejectionReasonGroup" style="display: none;">
                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" placeholder="Enter reason for rejection..." required></textarea>
                    </div>
                    
                    <div class="mb-3" id="actualCostGroup">
                        <label for="actual_cost" class="form-label">Actual Cost (₱)</label>
                        <input type="number" class="form-control" id="actual_cost" name="actual_cost" min="0" step="0.01" placeholder="0.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="submitBtn">
                        <span id="submitBtnText">Approve</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// * Define functions IMMEDIATELY in global scope (before DOMContentLoaded)
// * This ensures they're available even if HTML has onclick handlers

// * Main function to open approval modal (override the stub from layout)
window.openApprovalModal = function(requestId, actionType) {
    const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
    const form = document.getElementById('approvalForm');
    
    if (actionType === 'approve') {
        document.getElementById('approvalModalTitle').textContent = 'Approve Request';
        document.getElementById('actionType').value = 'approve';
        document.getElementById('requestId').value = requestId;
        document.getElementById('rejectionReasonGroup').style.display = 'none';
        document.getElementById('actualCostGroup').style.display = 'block';
        document.getElementById('approval_notes').placeholder = 'Enter approval notes (optional)...';
        document.getElementById('approval_notes').required = false;
        document.getElementById('rejection_reason').required = false;
        document.getElementById('submitBtn').className = 'btn btn-success';
        document.getElementById('submitBtnText').textContent = 'Approve';
    } else {
        document.getElementById('approvalModalTitle').textContent = 'Reject Request';
        document.getElementById('actionType').value = 'reject';
        document.getElementById('requestId').value = requestId;
        document.getElementById('rejectionReasonGroup').style.display = 'block';
        document.getElementById('actualCostGroup').style.display = 'none';
        document.getElementById('approval_notes').placeholder = 'Enter notes (optional)...';
        document.getElementById('rejection_reason').required = true;
        document.getElementById('submitBtn').className = 'btn btn-danger';
        document.getElementById('submitBtnText').textContent = 'Reject';
    }
    
    form.reset();
    document.getElementById('requestId').value = requestId;
    document.getElementById('actionType').value = actionType;
    
    modal.show();
}

// * Process any pending calls that were queued before openApprovalModal was fully defined
if (window._pendingApprovalCalls && window._pendingApprovalCalls.length > 0) {
    // * Process all pending calls now that openApprovalModal is defined
    var callsToProcess = window._pendingApprovalCalls.slice(); // Copy array
    window._pendingApprovalCalls = []; // Clear the queue
    callsToProcess.forEach(function(call) {
        window.openApprovalModal(call.requestId, call.action);
    });
}

// * Define functions in global scope for backwards compatibility (redefine with full implementation)
window.approveRequest = function(requestId) {
    window.openApprovalModal(requestId, 'approve');
};

window.rejectRequest = function(requestId) {
    window.openApprovalModal(requestId, 'reject');
};

// * Wait for DOM to be ready before adding event listeners
document.addEventListener('DOMContentLoaded', function() {
    // * Use event delegation for approve/reject buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.approve-btn')) {
            const btn = e.target.closest('.approve-btn');
            const requestId = btn.getAttribute('data-request-id');
            openApprovalModal(requestId, 'approve');
        } else if (e.target.closest('.reject-btn')) {
            const btn = e.target.closest('.reject-btn');
            const requestId = btn.getAttribute('data-request-id');
            openApprovalModal(requestId, 'reject');
        }
    });
    
    const approvalForm = document.getElementById('approvalForm');
    if (!approvalForm) {
        console.error('Approval form not found');
        return;
    }
    
    approvalForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const actionType = document.getElementById('actionType').value;
    const requestId = document.getElementById('requestId').value;
    const approvalNotes = document.getElementById('approval_notes').value;
    const rejectionReason = document.getElementById('rejection_reason').value;
    const actualCost = document.getElementById('actual_cost').value;
    
    if (actionType === 'reject' && !rejectionReason.trim()) {
        alert('Rejection reason is required');
        return;
    }
    
    const url = actionType === 'approve' 
        ? `/material-requests/${requestId}/approve`
        : `/material-requests/${requestId}/reject`;
    
    const data = actionType === 'approve'
        ? {
            approval_notes: approvalNotes,
            actual_cost: actualCost || null
          }
        : {
            rejection_reason: rejectionReason
          };
    
    // * Use POST instead of PUT (some shared hosting providers block PUT requests)
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        // * Handle non-OK responses (403, 404, 500, etc.)
        if (!response.ok) {
            // * Try to parse JSON error response first
            return response.text().then(text => {
                try {
                    const json = JSON.parse(text);
                    return Promise.reject({
                        status: response.status,
                        message: json.message || json.error || 'An error occurred',
                        data: json
                    });
                } catch (e) {
                    // * If not JSON, return HTML error page message
                    return Promise.reject({
                        status: response.status,
                        message: response.status === 403 
                            ? 'You do not have permission to perform this action. Please ensure you have the correct role and branch access.'
                            : response.status === 404
                            ? 'The requested resource was not found.'
                            : 'An error occurred while processing your request.'
                    });
                }
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Success!',
                text: data.message || 'Request updated successfully',
                icon: 'success',
                confirmButtonColor: '#198754'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: data.message || 'An error occurred',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: error.status === 403 ? 'Access Denied' : 'Error!',
            text: error.message || 'An error occurred while processing your request',
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
        });
    });
}); // End DOMContentLoaded
</script>
@endpush


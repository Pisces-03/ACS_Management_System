@extends('layouts.app')

@section('title', 'Material Request Details')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            @if(auth()->user()->role === 'employee')
                                <li class="breadcrumb-item"><a href="{{ route('material-requests.index') }}">Material Requests</a></li>
                            @else
                                <li class="breadcrumb-item"><a href="{{ route('pending-approvals.index') }}">Pending Approvals</a></li>
                            @endif
                            <li class="breadcrumb-item active">Request Details</li>
                        </ol>
                    </nav>
                    <h2><i class="bi bi-file-text"></i> Material Request Details</h2>
                    <p class="text-muted mb-0">Request #{{ $materialRequest->request_number }}</p>
                </div>
                <div>
                    @if(auth()->user()->role === 'employee')
                        <a href="{{ route('material-requests.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    @else
                        <a href="{{ route('pending-approvals.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Approvals
                        </a>
                    @endif
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Request Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Request Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Request Number:</strong><br>
                                    <span class="text-muted">{{ $materialRequest->request_number }}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Status:</strong><br>
                                    @switch($materialRequest->status ?? 'pending')
                                        @case('approved')
                                            <span class="badge bg-success">Approved</span>
                                            @break
                                        @case('rejected')
                                            <span class="badge bg-danger">Rejected</span>
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
                                            <span class="badge bg-secondary">{{ ucfirst($materialRequest->status ?? 'pending') }}</span>
                                    @endswitch
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Requested By:</strong><br>
                                    <span class="text-muted">{{ $materialRequest->requestedBy->full_name ?? 'Unknown' }}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Branch:</strong><br>
                                    <span class="text-muted">{{ $materialRequest->branch_name ?? $materialRequest->branch }}</span>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Priority:</strong><br>
                                    @switch($materialRequest->priority ?? 'medium')
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
                                            <span class="badge bg-secondary">{{ ucfirst($materialRequest->priority ?? 'medium') }}</span>
                                    @endswitch
                                </div>
                                <div class="col-md-6">
                                    <strong>Requested Date:</strong><br>
                                    <span class="text-muted">{{ $materialRequest->created_at ? $materialRequest->created_at->format('M d, Y h:i A') : 'N/A' }}</span>
                                </div>
                            </div>
                            
                            @if($materialRequest->needed_by)
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Needed By:</strong><br>
                                    <span class="text-muted">{{ \Carbon\Carbon::parse($materialRequest->needed_by)->format('M d, Y') }}</span>
                                </div>
                            </div>
                            @endif
                            
                            @if($materialRequest->task)
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <strong>Related Task:</strong><br>
                                    <span class="text-muted">{{ $materialRequest->task->title ?? 'N/A' }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Materials List -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Requested Materials</h5>
                        </div>
                        <div class="card-body">
                            @php
                                $materials = $materialRequest->materials ?? [];
                            @endphp
                            @if(!empty($materials))
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Material Name</th>
                                                <th>Quantity</th>
                                                <th>Unit</th>
                                                <th>Estimated Cost</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($materials as $material)
                                            <tr>
                                                <td><strong>{{ $material['name'] ?? 'N/A' }}</strong></td>
                                                <td>{{ $material['quantity'] ?? 0 }}</td>
                                                <td>{{ $material['unit'] ?? 'pcs' }}</td>
                                                <td>
                                                    @if(isset($material['estimated_cost']) && $material['estimated_cost'])
                                                        ₱{{ number_format($material['estimated_cost'], 2) }}
                                                    @else
                                                        <span class="text-muted">Not specified</span>
                                                    @endif
                                                </td>
                                                <td>{{ $material['description'] ?? '-' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="mt-3">
                                    <strong>Total Estimated Cost:</strong>
                                    <span class="h5 text-primary">₱{{ number_format($materialRequest->total_estimated_cost ?? 0, 2) }}</span>
                                </div>
                                
                                @if($materialRequest->actual_cost)
                                <div class="mt-2">
                                    <strong>Actual Cost:</strong>
                                    <span class="h5 text-success">₱{{ number_format($materialRequest->actual_cost, 2) }}</span>
                                </div>
                                @endif
                            @else
                                <p class="text-muted">No materials specified.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Justification -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Justification</h5>
                        </div>
                        <div class="card-body">
                            <p>{{ $materialRequest->justification ?? 'No justification provided.' }}</p>
                        </div>
                    </div>

                    <!-- Approval Information -->
                    @if($materialRequest->status !== 'pending')
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Approval Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Approved/Rejected By:</strong><br>
                                    <span class="text-muted">{{ $materialRequest->approvedBy->full_name ?? 'Unknown' }}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Date:</strong><br>
                                    <span class="text-muted">{{ $materialRequest->approved_at ? \Carbon\Carbon::parse($materialRequest->approved_at)->format('M d, Y h:i A') : 'N/A' }}</span>
                                </div>
                            </div>
                            
                            @if($materialRequest->approval_notes)
                            <div class="mb-3">
                                <strong>Approval Notes:</strong><br>
                                <span class="text-muted">{{ $materialRequest->approval_notes }}</span>
                            </div>
                            @endif
                            
                            @if($materialRequest->rejection_reason)
                            <div class="mb-3">
                                <strong>Rejection Reason:</strong><br>
                                <span class="text-danger">{{ $materialRequest->rejection_reason }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Action Sidebar -->
                <div class="col-lg-4">
                    @if($materialRequest->status === 'pending' && (auth()->user()->role === 'superadmin' || (auth()->user()->role === 'admin' && auth()->user()->branch === $materialRequest->branch)))
                    <div class="card mb-4">
                        <div class="card-header bg-warning" style="padding: 0.75rem 1rem; overflow: visible; min-height: auto;">
                            <h5 class="mb-0" style="line-height: 1.5; color: #000 !important; font-weight: 600; display: block; visibility: visible;">Actions</h5>
                        </div>
                        <div class="card-body">
                            <button type="button" class="btn btn-success w-100 mb-2" onclick="approveRequest({{ $materialRequest->id }})">
                                <i class="bi bi-check-circle"></i> Approve Request
                            </button>
                            <button type="button" class="btn btn-danger w-100" onclick="rejectRequest({{ $materialRequest->id }})">
                                <i class="bi bi-x-circle"></i> Reject Request
                            </button>
                        </div>
                    </div>
                    @endif

                    @if(in_array($materialRequest->status, ['approved', 'ordered']) && (auth()->user()->role === 'superadmin' || (auth()->user()->role === 'admin' && auth()->user()->branch === $materialRequest->branch)))
                    <!-- Procurement tracking: approved -> ordered -> received (with actual cost) -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Procurement</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('material-requests.status.post', $materialRequest) }}">
                                @csrf
                                @if($materialRequest->status === 'approved')
                                    <input type="hidden" name="status" value="ordered">
                                    <div class="mb-3">
                                        <label for="procurement_notes" class="form-label">Notes (optional)</label>
                                        <input type="text" class="form-control" id="procurement_notes" name="notes" placeholder="Supplier, PO number, etc.">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-cart-check"></i> Mark as Ordered
                                    </button>
                                @else
                                    <input type="hidden" name="status" value="received">
                                    <div class="mb-3">
                                        <label for="received_actual_cost" class="form-label">Actual Cost (₱)</label>
                                        <input type="number" class="form-control" id="received_actual_cost" name="actual_cost" min="0" step="0.01"
                                               value="{{ $materialRequest->actual_cost ?? $materialRequest->total_estimated_cost }}">
                                    </div>
                                    <div class="mb-3">
                                        <label for="procurement_notes" class="form-label">Notes (optional)</label>
                                        <input type="text" class="form-control" id="procurement_notes" name="notes">
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-box-seam"></i> Mark as Received
                                    </button>
                                @endif
                            </form>
                        </div>
                    </div>
                    @endif

                    <!-- Request Summary -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Request Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Total Items:</strong>
                                <span class="float-end">{{ count($materialRequest->materials ?? []) }}</span>
                            </div>
                            <div class="mb-3">
                                <strong>Total Quantity:</strong>
                                <span class="float-end">{{ $materialRequest->total_quantity ?? 0 }}</span>
                            </div>
                            <div class="mb-3">
                                <strong>Estimated Cost:</strong>
                                <span class="float-end">₱{{ number_format($materialRequest->total_estimated_cost ?? 0, 2) }}</span>
                            </div>
                            @if($materialRequest->actual_cost)
                            <div class="mb-3">
                                <strong>Actual Cost:</strong>
                                <span class="float-end text-success">₱{{ number_format($materialRequest->actual_cost, 2) }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
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
// * Override the layout's stub functions with full implementation
window.approveRequest = function(requestId) {
    const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
    const form = document.getElementById('approvalForm');
    
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
    
    form.reset();
    document.getElementById('requestId').value = requestId;
    document.getElementById('actionType').value = 'approve';
    
    modal.show();
}

window.rejectRequest = function(requestId) {
    const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
    const form = document.getElementById('approvalForm');
    
    document.getElementById('approvalModalTitle').textContent = 'Reject Request';
    document.getElementById('actionType').value = 'reject';
    document.getElementById('requestId').value = requestId;
    document.getElementById('rejectionReasonGroup').style.display = 'block';
    document.getElementById('actualCostGroup').style.display = 'none';
    document.getElementById('approval_notes').placeholder = 'Enter notes (optional)...';
    document.getElementById('rejection_reason').required = true;
    document.getElementById('submitBtn').className = 'btn btn-danger';
    document.getElementById('submitBtnText').textContent = 'Reject';
    
    form.reset();
    document.getElementById('requestId').value = requestId;
    document.getElementById('actionType').value = 'reject';
    
    modal.show();
}

// * Wait for DOM to be ready before adding event listeners
document.addEventListener('DOMContentLoaded', function() {
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
        Swal.fire({
            title: 'Error!',
            text: 'Rejection reason is required',
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
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
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Success!',
                text: data.message || 'Request updated successfully',
                icon: 'success',
                confirmButtonColor: '#198754'
            }).then(() => {
                window.location.href = '{{ route("pending-approvals.index") }}';
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
            title: 'Error!',
            text: 'An error occurred while processing your request',
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    });
    }); // End DOMContentLoaded
});
</script>
@endpush


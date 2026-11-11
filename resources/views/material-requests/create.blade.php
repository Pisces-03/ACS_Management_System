@extends('layouts.app')

@section('title', 'Create Material Request')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('material-requests.index') }}">Material Requests</a></li>
                            <li class="breadcrumb-item active">Create Request</li>
                        </ol>
                    </nav>
                    <h2><i class="bi bi-plus-circle"></i> Create Material Request</h2>
                    <p class="text-muted mb-0">Submit a new request for materials and supplies</p>
                </div>
                <div class="d-flex gap-2">
                    @if(auth()->user()->role === 'employee')
                    <a href="{{ route('maintenance.create') }}" class="btn btn-outline-primary">
                        <i class="bi bi-tools"></i> Submit Maintenance Findings
                    </a>
                    @endif
                    <a href="{{ route('material-requests.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Requests
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <form action="{{ route('material-requests.store') }}" method="POST" id="materialRequestForm">
                        @csrf

                        <!-- Request Information -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Request Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="item_name" class="form-label">Item/Material Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('item_name') is-invalid @enderror"
                                           id="item_name" name="item_name" value="{{ old('item_name') }}"
                                           placeholder="Enter the name of the material or item" required>
                                    @error('item_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              id="description" name="description" rows="4"
                                              placeholder="Provide detailed description including specifications, model numbers, etc..." required>{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('quantity') is-invalid @enderror"
                                                   id="quantity" name="quantity" value="{{ old('quantity') }}"
                                                   min="1" placeholder="Enter quantity" required>
                                            @error('quantity')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="unit" class="form-label">Unit of Measurement</label>
                                            <select class="form-select @error('unit') is-invalid @enderror"
                                                    id="unit" name="unit">
                                                <option value="">Select Unit</option>
                                                <option value="pieces" {{ old('unit') == 'pieces' ? 'selected' : '' }}>Pieces</option>
                                                <option value="boxes" {{ old('unit') == 'boxes' ? 'selected' : '' }}>Boxes</option>
                                                <option value="sets" {{ old('unit') == 'sets' ? 'selected' : '' }}>Sets</option>
                                                <option value="kilograms" {{ old('unit') == 'kilograms' ? 'selected' : '' }}>Kilograms</option>
                                                <option value="liters" {{ old('unit') == 'liters' ? 'selected' : '' }}>Liters</option>
                                                <option value="meters" {{ old('unit') == 'meters' ? 'selected' : '' }}>Meters</option>
                                                <option value="rolls" {{ old('unit') == 'rolls' ? 'selected' : '' }}>Rolls</option>
                                                <option value="bottles" {{ old('unit') == 'bottles' ? 'selected' : '' }}>Bottles</option>
                                                <option value="gallons" {{ old('unit') == 'gallons' ? 'selected' : '' }}>Gallons</option>
                                            </select>
                                            @error('unit')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                            <select class="form-select @error('category') is-invalid @enderror"
                                                    id="category" name="category" required>
                                                <option value="">Select Category</option>
                                                <option value="cleaning_supplies" {{ old('category') == 'cleaning_supplies' ? 'selected' : '' }}>Cleaning Supplies</option>
                                                <option value="maintenance_tools" {{ old('category') == 'maintenance_tools' ? 'selected' : '' }}>Maintenance Tools</option>
                                                <option value="office_supplies" {{ old('category') == 'office_supplies' ? 'selected' : '' }}>Office Supplies</option>
                                                <option value="safety_equipment" {{ old('category') == 'safety_equipment' ? 'selected' : '' }}>Safety Equipment</option>
                                                <option value="electrical_supplies" {{ old('category') == 'electrical_supplies' ? 'selected' : '' }}>Electrical Supplies</option>
                                                <option value="plumbing_supplies" {{ old('category') == 'plumbing_supplies' ? 'selected' : '' }}>Plumbing Supplies</option>
                                                <option value="chemicals" {{ old('category') == 'chemicals' ? 'selected' : '' }}>Chemicals</option>
                                                <option value="spare_parts" {{ old('category') == 'spare_parts' ? 'selected' : '' }}>Spare Parts</option>
                                                <option value="uniforms" {{ old('category') == 'uniforms' ? 'selected' : '' }}>Uniforms</option>
                                            </select>
                                            @error('category')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                            <select class="form-select @error('priority') is-invalid @enderror"
                                                    id="priority" name="priority" required>
                                                <option value="">Select Priority</option>
                                                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                                <option value="normal" {{ old('priority', 'normal') == 'normal' ? 'selected' : '' }}>Normal</option>
                                                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                                <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                            </select>
                                            @error('priority')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="needed_by" class="form-label">Needed By</label>
                                            <input type="date" class="form-control @error('needed_by') is-invalid @enderror"
                                                   id="needed_by" name="needed_by" value="{{ old('needed_by') }}"
                                                   min="{{ date('Y-m-d') }}">
                                            @error('needed_by')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estimated_cost" class="form-label">Estimated Cost (₱)</label>
                                            <input type="number" class="form-control @error('estimated_cost') is-invalid @enderror"
                                                   id="estimated_cost" name="estimated_cost"
                                                   value="{{ old('estimated_cost') }}" min="0" step="0.01"
                                                   placeholder="0.00">
                                            @error('estimated_cost')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="purpose" class="form-label">Purpose/Justification <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('purpose') is-invalid @enderror"
                                              id="purpose" name="purpose" rows="3"
                                              placeholder="Explain why this material is needed and how it will be used..." required>{{ old('purpose') }}</textarea>
                                    @error('purpose')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="supplier_preference" class="form-label">Preferred Supplier (Optional)</label>
                                    <input type="text" class="form-control @error('supplier_preference') is-invalid @enderror"
                                           id="supplier_preference" name="supplier_preference"
                                           value="{{ old('supplier_preference') }}"
                                           placeholder="Suggest a preferred supplier if you have one">
                                    @error('supplier_preference')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Additional Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror"
                                              id="notes" name="notes" rows="3"
                                              placeholder="Any additional information, special requirements, or specifications...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Budget Information Alert -->
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Budget Guidelines:</strong> Please ensure your request falls within your department's budget allocation.
                                    Requests over ₱5,000 may require additional approval. Contact your supervisor for budget information.
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('material-requests.index') }}" class="btn btn-secondary me-2">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Submit Request
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Help Sidebar -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-question-circle"></i> Request Guidelines</h6>
                        </div>
                        <div class="card-body">
                            <h6>Priority Levels:</h6>
                            <ul class="list-unstyled">
                                <li><span class="badge bg-success">Low:</span> Stock up items, non-critical</li>
                                <li><span class="badge bg-info">Normal:</span> Regular operations, standard timeline</li>
                                <li><span class="badge bg-warning">High:</span> Important for operations, expedite</li>
                                <li><span class="badge bg-danger">Urgent:</span> Critical shortage, immediate need</li>
                            </ul>

                            <h6 class="mt-3">Request Process:</h6>
                            <ol class="small">
                                <li>Submit your request with complete details</li>
                                <li>Supervisor reviews and approves/rejects</li>
                                <li>Procurement processes approved requests</li>
                                <li>You'll be notified when items arrive</li>
                                <li>Pick up items from designated location</li>
                            </ol>

                            <h6 class="mt-3">Tips for Approval:</h6>
                            <ul class="small">
                                <li>Be specific with item descriptions</li>
                                <li>Include model numbers when applicable</li>
                                <li>Provide clear justification</li>
                                <li>Set reasonable timelines</li>
                                <li>Research estimated costs</li>
                                <li>Consider bulk ordering for savings</li>
                            </ul>

                            <div class="mt-3 p-3 bg-light rounded">
                                <h6 class="text-primary"><i class="bi bi-lightbulb"></i> Quick Categories:</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <ul class="list-unstyled small">
                                            <li>🧽 Cleaning</li>
                                            <li>🔧 Tools</li>
                                            <li>📄 Office</li>
                                            <li>🦺 Safety</li>
                                            <li>⚡ Electrical</li>
                                        </ul>
                                    </div>
                                    <div class="col-6">
                                        <ul class="list-unstyled small">
                                            <li>🚿 Plumbing</li>
                                            <li>🧪 Chemicals</li>
                                            <li>⚙️ Parts</li>
                                            <li>👕 Uniforms</li>
                                            <li>📦 Other</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Budget Status -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-wallet2"></i> Budget Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Department Budget</span>
                                    <span class="fw-bold">₱50,000</span>
                                </div>
                                <div class="progress mt-1">
                                    <div class="progress-bar bg-success" style="width: 60%"></div>
                                </div>
                                <small class="text-muted">₱30,000 used this month</small>
                            </div>

                            <div class="alert alert-warning alert-sm">
                                <i class="bi bi-exclamation-triangle"></i>
                                <small>Budget resets monthly. Plan your requests accordingly.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Requests -->
                    @if(isset($recentRequests) && $recentRequests->count() > 0)
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent Requests</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                @foreach($recentRequests->take(5) as $request)
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $request->item_name }}</h6>
                                            <p class="mb-1 text-muted small">Qty: {{ $request->quantity }} {{ $request->unit }}</p>
                                            <small class="text-muted">{{ $request->created_at->diffForHumans() }}</small>
                                        </div>
                                        <span class="badge bg-{{ $request->status == 'approved' ? 'success' : ($request->status == 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Form submission with SweetAlert confirmation
document.getElementById('materialRequestForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const estimatedCost = document.getElementById('estimated_cost').value;
    let confirmText = 'Are you sure you want to submit this material request?';

    if (estimatedCost && parseFloat(estimatedCost) > 5000) {
        confirmText = 'This request exceeds ₱5,000 and will require additional approval. Continue?';
    }

    Swal.fire({
        title: 'Submit Request?',
        text: confirmText,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Submit!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Submitting...',
                text: 'Please wait while we submit your request',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading()
                }
            });

            this.submit();
        }
    });
});

// Auto-suggest priority based on category
document.getElementById('category').addEventListener('change', function() {
    const priority = document.getElementById('priority');
    if (priority.value === '') {
        switch(this.value) {
            case 'safety_equipment':
            case 'chemicals':
                priority.value = 'high';
                break;
            case 'cleaning_supplies':
            case 'maintenance_tools':
                priority.value = 'normal';
                break;
            default:
                priority.value = 'normal';
        }
    }
});

// Calculate estimated total cost
document.getElementById('quantity').addEventListener('input', function() {
    updateEstimatedTotal();
});

document.getElementById('estimated_cost').addEventListener('input', function() {
    updateEstimatedTotal();
});

function updateEstimatedTotal() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const unitCost = parseFloat(document.getElementById('estimated_cost').value) || 0;
    const total = quantity * unitCost;

    if (total > 0) {
        // Show budget warning if exceeds threshold
        if (total > 5000) {
            if (!document.querySelector('.budget-warning')) {
                const warning = document.createElement('div');
                warning.className = 'alert alert-warning budget-warning mt-2';
                warning.innerHTML = '<i class="bi bi-exclamation-triangle"></i> <strong>High Value Request:</strong> This request may require additional approval.';
                document.getElementById('estimated_cost').parentNode.appendChild(warning);
            }
        } else {
            const warning = document.querySelector('.budget-warning');
            if (warning) {
                warning.remove();
            }
        }
    }
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

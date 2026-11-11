@extends('layouts.app')

@section('title', 'Create Maintenance Log')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('maintenance.index') }}">Maintenance</a></li>
                            <li class="breadcrumb-item active">Create Log</li>
                        </ol>
                    </nav>
                    <h2><i class="bi bi-tools"></i> Create Maintenance Log</h2>
                    <p class="text-muted mb-0">Record new maintenance activity or service request</p>
                </div>
                <div>
                    <a href="{{ route('maintenance.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <form action="{{ route('maintenance.store') }}" method="POST" id="maintenanceForm" enctype="multipart/form-data">
                        @csrf

                        <!-- Basic Information -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Basic Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="equipment_name" class="form-label">Equipment Name <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control @error('equipment_name') is-invalid @enderror" 
                                                   id="equipment_name" 
                                                   name="equipment_name" 
                                                   value="{{ old('equipment_name') }}" 
                                                   placeholder="Enter equipment name (e.g., AC Unit - Room 101)"
                                                   required>
                                            @error('equipment_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Enter the name or identifier of the equipment that needs maintenance</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="type" class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="type" 
                                                   value="Maintenance Logs" 
                                                   readonly 
                                                   style="background-color: #e9ecef; cursor: not-allowed;">
                                            <input type="hidden" name="type" value="maintenance_logs">
                                            @error('type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              id="description" name="description" rows="4"
                                              placeholder="Describe the maintenance work needed or performed..." required>{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Maintenance Details -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">Maintenance Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="issues_found" class="form-label">Issues Found</label>
                                    <textarea class="form-control @error('issues_found') is-invalid @enderror"
                                              id="issues_found" name="issues_found" rows="3"
                                              placeholder="Describe any issues or problems found during maintenance...">{{ old('issues_found') }}</textarea>
                                    @error('issues_found')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="actions_taken" class="form-label">Actions Taken <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('actions_taken') is-invalid @enderror"
                                              id="actions_taken" name="actions_taken" rows="4"
                                              placeholder="Describe the actions taken to resolve the issues or perform maintenance..." required>{{ old('actions_taken') }}</textarea>
                                    @error('actions_taken')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="parts_used" class="form-label">Parts Used</label>
                                    <textarea class="form-control @error('parts_used') is-invalid @enderror"
                                              id="parts_used" name="parts_used" rows="2"
                                              placeholder="List any parts, materials, or supplies used during maintenance...">{{ old('parts_used') }}</textarea>
                                    @error('parts_used')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="repair_cost" class="form-label">Repair Expense (₱)</label>
                                    <input type="number" class="form-control @error('repair_cost') is-invalid @enderror"
                                           id="repair_cost" name="repair_cost" min="0" step="0.01"
                                           value="{{ old('repair_cost') }}" placeholder="0.00">
                                    @error('repair_cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Total cost of this repair (labor, parts bought on site, etc.).</div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">Additional Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="photos" class="form-label">Photos</label>
                                    <input type="file" class="form-control @error('photos.*') is-invalid @enderror"
                                           id="photos" name="photos[]" multiple
                                           accept=".jpg,.jpeg,.png">
                                    @error('photos.*')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Supported formats: JPG, PNG. Max size: 5MB per file.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="videos" class="form-label">Videos</label>
                                    <input type="file" class="form-control @error('videos.*') is-invalid @enderror"
                                           id="videos" name="videos[]" multiple
                                           accept=".mp4,.mov,.webm,.3gp">
                                    @error('videos.*')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Supported formats: MP4, MOV, WEBM, 3GP. Max size: 20MB per file.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('maintenance.index') }}" class="btn btn-secondary me-2">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Create Maintenance Log
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
                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Quick Guide</h6>
                        </div>
                        <div class="card-body">
                            <h6>Maintenance Types:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Installation:</strong> Setting up new equipment</li>
                                <li><strong>Re-installation:</strong> Reinstalling existing equipment</li>
                                <li><strong>Repair:</strong> Fix broken or malfunctioning equipment</li>
                                <li><strong>Dismantling:</strong> Removing or disassembling equipment</li>
                                <li><strong>Check Up:</strong> Routine inspection and maintenance</li>
                            </ul>

                            <h6 class="mt-3">Tips:</h6>
                            <ul class="small">
                                <li>Be specific in your description</li>
                                <li>Include error codes or symptoms</li>
                                <li>Attach photos or documents if helpful</li>
                                <li>Describe issues found clearly</li>
                                <li>Detail all actions taken</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Form submission with SweetAlert confirmation
document.getElementById('maintenanceForm').addEventListener('submit', function(e) {
    e.preventDefault();

    Swal.fire({
        title: 'Create Maintenance Log?',
        text: 'Are you sure you want to create this maintenance log?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Create!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Creating...',
                text: 'Please wait while we create the maintenance log',
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

// Maintenance type is now fixed to "Maintenance Logs" and not editable

// File upload validation
document.getElementById('photos').addEventListener('change', function() {
    const files = this.files;
    const maxSize = 5 * 1024 * 1024; // 5MB
    let totalSize = 0;

    for (let i = 0; i < files.length; i++) {
        if (files[i].size > maxSize) {
            Swal.fire({
                title: 'File Too Large',
                text: `${files[i].name} is larger than 5MB. Please choose a smaller file.`,
                icon: 'warning',
                confirmButtonColor: '#ffc107'
            });
            this.value = '';
            return;
        }
        totalSize += files[i].size;
    }

    if (totalSize > maxSize * 5) { // Max 25MB total
        Swal.fire({
            title: 'Too Many Files',
            text: 'Total file size should not exceed 25MB. Please remove some files.',
            icon: 'warning',
            confirmButtonColor: '#ffc107'
        });
        this.value = '';
    }
});

document.getElementById('videos').addEventListener('change', function() {
    const maxSize = 20 * 1024 * 1024; // 20MB
    for (let i = 0; i < this.files.length; i++) {
        if (this.files[i].size > maxSize) {
            Swal.fire({
                title: 'File Too Large',
                text: this.files[i].name + ' is larger than 20MB. Please choose a shorter clip.',
                icon: 'warning',
                confirmButtonColor: '#ffc107'
            });
            this.value = '';
            return;
        }
    }
});

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

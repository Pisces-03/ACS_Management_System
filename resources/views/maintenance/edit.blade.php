@extends('layouts.app')

@section('title', 'Review Maintenance Log')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('maintenance.index') }}">Maintenance</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('maintenance.show', $maintenance) }}">{{ $maintenance->log_number }}</a></li>
                    <li class="breadcrumb-item active">Review</li>
                </ol>
            </nav>
            <h2 class="mb-1"><i class="bi bi-clipboard-check"></i> Review Maintenance Log</h2>
            <p class="text-muted">
                {{ $maintenance->equipment_name }} &middot; submitted by {{ $maintenance->employee->full_name ?? 'Unknown' }}
                &middot; {{ $maintenance->branch_name }}
            </p>

            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0">Submitted Findings</h6></div>
                <div class="card-body">
                    <p class="mb-2"><strong>Description:</strong><br>{!! nl2br(e($maintenance->description)) !!}</p>
                    <p class="mb-2"><strong>Work Performed:</strong><br>{!! nl2br(e($maintenance->work_performed)) !!}</p>
                    @if($maintenance->materials_used)
                    <p class="mb-0"><strong>Materials Used:</strong><br>{!! nl2br(e($maintenance->materials_used)) !!}</p>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('maintenance.update', $maintenance) }}" class="card">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach(['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'requires_followup' => 'Requires Follow-up'] as $value => $label)
                                <option value="{{ $value }}" {{ old('status', $maintenance->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="repair_cost" class="form-label">Repair Expense (₱)</label>
                            <input type="number" step="0.01" min="0" name="repair_cost" id="repair_cost"
                                   class="form-control @error('repair_cost') is-invalid @enderror"
                                   value="{{ old('repair_cost', $maintenance->repair_cost) }}">
                            @error('repair_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="hours_worked" class="form-label">Hours Worked</label>
                            <input type="number" step="0.25" min="0" max="999" name="hours_worked" id="hours_worked"
                                   class="form-control @error('hours_worked') is-invalid @enderror"
                                   value="{{ old('hours_worked', $maintenance->hours_worked) }}">
                            @error('hours_worked')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="next_maintenance_date" class="form-label">Next Maintenance Date</label>
                            <input type="date" name="next_maintenance_date" id="next_maintenance_date"
                                   class="form-control @error('next_maintenance_date') is-invalid @enderror"
                                   value="{{ old('next_maintenance_date', $maintenance->next_maintenance_date?->format('Y-m-d')) }}">
                            @error('next_maintenance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">Review Notes</label>
                            <textarea name="notes" id="notes" rows="3" maxlength="2000"
                                      class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $maintenance->notes) }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('maintenance.show', $maintenance) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Review</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

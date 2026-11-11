@extends('layouts.app')

@section('title', 'Maintenance Log Details - ' . $maintenance->log_number)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Maintenance Log Details</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('maintenance.index') }}">Maintenance</a></li>
                            <li class="breadcrumb-item active">{{ $maintenance->log_number }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    @if(auth()->user()->role !== 'employee')
                    <a href="{{ route('maintenance.edit', $maintenance) }}" class="btn btn-primary">
                        <i class="bi bi-clipboard-check"></i> Review / Update
                    </a>
                    @endif
                    <a href="{{ route('maintenance.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Maintenance
                    </a>
                </div>
            </div>

            <!-- Maintenance Log Information Card -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ $maintenance->equipment_name }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Log Number:</strong></div>
                                <div class="col-sm-9">{{ $maintenance->log_number }}</div>
                            </div>
                            @if($maintenance->task)
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Related Task:</strong></div>
                                <div class="col-sm-9">
                                    <a href="{{ route('tasks.show', $maintenance->task) }}" class="text-decoration-none">
                                        {{ $maintenance->task->task_number }} - {{ $maintenance->task->title }}
                                    </a>
                                </div>
                            </div>
                            @endif
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Type:</strong></div>
                                <div class="col-sm-9">
                                    <span class="badge bg-info">{{ $maintenance->type_name }}</span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Status:</strong></div>
                                <div class="col-sm-9">
                                    @switch($maintenance->status)
                                        @case('pending')
                                            <span class="badge bg-warning">Pending</span>
                                            @break
                                        @case('in_progress')
                                            <span class="badge bg-primary">In Progress</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-success">Completed</span>
                                            @break
                                        @case('requires_followup')
                                            <span class="badge bg-danger">Requires Follow-up</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($maintenance->status) }}</span>
                                    @endswitch
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Branch:</strong></div>
                                <div class="col-sm-9">
                                    <span class="badge bg-info">{{ $maintenance->branch_name }}</span>
                                </div>
                            </div>
                            @if($maintenance->equipment_name)
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Equipment:</strong></div>
                                <div class="col-sm-9">
                                    {{ $maintenance->equipment_name }}
                                    @if($maintenance->equipment_model)
                                        <br><small class="text-muted">Model: {{ $maintenance->equipment_model }}</small>
                                    @endif
                                    @if($maintenance->equipment_serial)
                                        <br><small class="text-muted">Serial: {{ $maintenance->equipment_serial }}</small>
                                    @endif
                                </div>
                            </div>
                            @endif
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Description:</strong></div>
                                <div class="col-sm-9">
                                    {!! nl2br(e($maintenance->description)) !!}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Work Performed:</strong></div>
                                <div class="col-sm-9">
                                    {!! nl2br(e($maintenance->work_performed)) !!}
                                </div>
                            </div>
                            @if($maintenance->materials_used)
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Materials Used:</strong></div>
                                <div class="col-sm-9">
                                    {!! nl2br(e($maintenance->materials_used)) !!}
                                </div>
                            </div>
                            @endif
                            @if(!is_null($maintenance->repair_cost))
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Repair Expense:</strong></div>
                                <div class="col-sm-9">₱{{ number_format($maintenance->repair_cost, 2) }}</div>
                            </div>
                            @endif
                            @if($maintenance->notes)
                            <div class="row mb-3">
                                <div class="col-sm-3"><strong>Notes:</strong></div>
                                <div class="col-sm-9">
                                    {!! nl2br(e($maintenance->notes)) !!}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Photos and Videos -->
                    @if(($maintenance->photos && is_array($maintenance->photos) && count($maintenance->photos) > 0) || ($maintenance->videos && is_array($maintenance->videos) && count($maintenance->videos) > 0))
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Media</h5>
                        </div>
                        <div class="card-body">
                            @if($maintenance->photos && is_array($maintenance->photos) && count($maintenance->photos) > 0)
                            <div class="mb-3">
                                <h6>Photos ({{ count($maintenance->photos) }})</h6>
                                <div class="row g-2">
                                    @foreach($maintenance->photos as $index => $photoPath)
                                    @php
                                        // * Use direct URL to avoid route cache issues
                                        // * This will work even if routes are cached
                                        $photoUrl = url('/storage/' . $photoPath);
                                        $photoExists = \Storage::disk('public')->exists($photoPath);
                                    @endphp
                                    <div class="col-md-3 col-sm-4 col-6">
                                        <div class="card">
                                            @if($photoExists)
                                            <a href="{{ $photoUrl }}" target="_blank" class="text-decoration-none">
                                                <img src="{{ $photoUrl }}" 
                                                     alt="Maintenance Photo {{ $index + 1 }}" 
                                                     class="card-img-top" 
                                                     style="height: 150px; object-fit: cover; cursor: pointer;"
                                                     onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'150\'%3E%3Crect fill=\'%23ddd\' width=\'200\' height=\'150\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'14\' dy=\'10.5\' font-weight=\'bold\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\'%3EImage not found%3C/text%3E%3C/svg%3E';">
                                            </a>
                                            <div class="card-body p-2">
                                                <a href="{{ $photoUrl }}" 
                                                   download 
                                                   class="btn btn-sm btn-outline-primary w-100">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            </div>
                                            @else
                                            <div class="card-body text-center">
                                                <p class="text-muted small mb-2">File not found on server</p>
                                                <code class="small">{{ $photoPath }}</code>
                                                <div class="alert alert-warning mt-2 mb-0 small">
                                                    <i class="bi bi-exclamation-triangle"></i> 
                                                    Storage link may not be configured. Run: <code>php artisan storage:link</code>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            @if($maintenance->videos)
                            <div>
                                <h6>Videos</h6>
                                <div class="row g-2">
                                    @foreach($maintenance->video_urls as $videoUrl)
                                    <div class="col-md-6">
                                        <video controls preload="metadata" class="w-100" style="max-height: 300px;">
                                            <source src="{{ $videoUrl }}">
                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Assignment Information -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Assignment</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Assigned Technician:</strong><br>
                                {{ $maintenance->employee->full_name ?? 'Unassigned' }}
                            </div>
                            @if($maintenance->hours_worked)
                            <div class="mb-3">
                                <strong>Hours Worked:</strong><br>
                                {{ number_format($maintenance->hours_worked, 2) }} hours
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Timeline</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Log Created</h6>
                                        <p class="timeline-time">{{ $maintenance->created_at->format('M d, Y g:i A') }}</p>
                                    </div>
                                </div>

                                @if($maintenance->started_at)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Started</h6>
                                        <p class="timeline-time">{{ $maintenance->started_at->format('M d, Y g:i A') }}</p>
                                    </div>
                                </div>
                                @endif

                                @if($maintenance->performed_at)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-warning"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Performed</h6>
                                        <p class="timeline-time">{{ $maintenance->performed_at->format('M d, Y g:i A') }}</p>
                                    </div>
                                </div>
                                @endif

                                @if($maintenance->completed_at)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Completed</h6>
                                        <p class="timeline-time">{{ $maintenance->completed_at->format('M d, Y g:i A') }}</p>
                                    </div>
                                </div>
                                @endif

                                @if($maintenance->next_maintenance_date)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-secondary"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Next Maintenance</h6>
                                        <p class="timeline-time">
                                            {{ $maintenance->next_maintenance_date->format('M d, Y') }}
                                            @if($maintenance->next_maintenance_date->isPast())
                                                <span class="badge bg-danger ms-2">Overdue</span>
                                            @elseif($maintenance->next_maintenance_date->isToday())
                                                <span class="badge bg-warning ms-2">Due Today</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -22px;
    top: 20px;
    width: 2px;
    height: calc(100% - 10px);
    background: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px currentColor;
}

.timeline-content {
    padding-left: 10px;
}

.timeline-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.timeline-time {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 0;
}

.timeline-desc {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.25rem;
    margin-bottom: 0;
}
</style>
@endsection


@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Admin Dashboard - {{ Auth::user()->branch_name }}</h1>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4 g-3">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="card-title mb-1">{{ $stats['branch_users'] }}</h4>
                            <p class="card-text mb-0 small">Branch Users</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="card-title mb-1">{{ $stats['branch_tasks'] }}</h4>
                            <p class="card-text mb-0 small">Branch Tasks</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-list-task fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="card-title mb-1">{{ $stats['pending_tasks'] }}</h4>
                            <p class="card-text mb-0 small">Pending Tasks</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-clock fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card text-white bg-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="card-title mb-1">{{ $stats['overdue_tasks'] }}</h4>
                            <p class="card-text mb-0 small">Overdue Tasks</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-exclamation-triangle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Sections -->
    <div class="row g-3">
        <div class="col-lg-6 col-12">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Tasks</h5>
                </div>
                <div class="card-body">
                    @if($recentTasks->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentTasks as $task)
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-1 flex-grow-1">{{ $task->title }}</h6>
                                    <span class="badge bg-{{ $task->status === 'completed' ? 'success' : ($task->status === 'pending' ? 'warning' : 'info') }} ms-2">
                                        {{ $task->status_name }}
                                    </span>
                                </div>
                                <p class="mb-1 small">Assigned to: {{ $task->assignedEmployee->full_name ?? 'Unassigned' }}</p>
                                <small class="text-muted">Due: {{ $task->due_date->format('M d, Y') }}</small>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No recent tasks</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Maintenance</h5>
                </div>
                <div class="card-body">
                    @if($recentMaintenance->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentMaintenance as $log)
                            <div class="list-group-item px-0">
                                <h6 class="mb-1">{{ $log->equipment_name }}</h6>
                                <p class="mb-1 small">{{ $log->type_name }}</p>
                                <small class="text-muted">
                                    By: {{ $log->employee->full_name }} -
                                    {{ $log->created_at->format('M d, Y') }}
                                </small>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No recent maintenance logs</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(isset($pendingApprovals) && $pendingApprovals->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pending Approvals</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @foreach($pendingApprovals as $request)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $request->item_name }}</h6>
                                    <p class="mb-1 small">Requested by: {{ $request->requestedBy->full_name }}</p>
                                    <small class="text-muted">{{ $request->created_at->format('M d, Y') }}</small>
                                </div>
                                <div class="ms-2">
                                    <span class="badge bg-{{ $request->urgency === 'urgent' ? 'danger' : 'warning' }}">
                                        {{ $request->urgency_name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Branch cost analysis, recurring issues, personnel performance -->
    <div class="mt-4">
        @include('reports.partials.branch-analysis')
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Superadmin Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Superadmin Dashboard</h1>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4 g-3">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="card-title mb-1">{{ $stats['total_users'] }}</h4>
                            <p class="card-text mb-0 small">Total Users</p>
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
                            <h4 class="card-title mb-1">{{ $stats['total_tasks'] }}</h4>
                            <p class="card-text mb-0 small">Total Tasks</p>
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

    <!-- Consolidated expenses, recurring issues and personnel performance across all branches -->
    @include('reports.partials.branch-analysis')

    <!-- Branch Stats -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Branch Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($branchStats as $branch => $stats)
                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="card mb-3 h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        {{ ucwords(str_replace('_', ' ', $branch)) }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h4 class="mb-1">{{ $stats['tasks'] }}</h4>
                                            <small class="text-muted">Tasks</small>
                                        </div>
                                        <div class="col-6">
                                            <h4 class="mb-1">{{ $stats['employees'] }}</h4>
                                            <small class="text-muted">Employees</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row g-3">
        <div class="col-lg-6 col-12">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    @if($recentActivity->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentActivity as $activity)
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <span class="d-block">{{ $activity['message'] }}</span>
                                        <small class="text-muted">by {{ $activity['user'] }}</small>
                                    </div>
                                    <small class="text-muted ms-2">
                                        {{ $activity['time']->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No recent activity</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tasks by Priority</h5>
                </div>
                <div class="card-body">
                    @if($tasksByPriority->count() > 0)
                        @foreach($tasksByPriority as $priority => $count)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-{{ $priority === 'urgent' ? 'danger' : ($priority === 'high' ? 'warning' : 'info') }}">
                                {{ ucfirst($priority) }}
                            </span>
                            <span class="fw-bold">{{ $count }} tasks</span>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted">No tasks found</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

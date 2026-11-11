@extends('layouts.app')

@section('title', 'Preventive Maintenance')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-3">
                <h2 class="mb-0"><i class="bi bi-calendar-check"></i> Preventive Maintenance</h2>
                <form method="POST" action="{{ route('preventive-maintenance.generate-tasks') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-lightning-charge"></i> Generate Due Tasks Now
                    </button>
                </form>
            </div>
            <p class="text-muted">
                Schedules are set on completed tasks (open a completed task and use <strong>Maintenance Schedule</strong>).
                The next due date is calculated from the last recorded service plus the interval. Every day at 6:00 AM the
                system generates a preventive check-up task for each schedule that is due and assigns it to the team that
                did the original job. Completing that task records the service and moves the schedule to its next date.
            </p>

            <!-- Statistics -->
            <div class="row mb-4 g-3">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Active Schedules</h5>
                            <h3 class="mb-0">{{ $stats['active'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Due / Overdue</h5>
                            <h3 class="mb-0">{{ $stats['due'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Due in 14 Days</h5>
                            <h3 class="mb-0">{{ $stats['due_soon'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Tasks Generated</h5>
                            <h3 class="mb-0">{{ $stats['generated'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('preventive-maintenance.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All</option>
                                <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                <option value="due_soon" {{ request('status') === 'due_soon' ? 'selected' : '' }}>Due in 14 days</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        @if(auth()->user()->role === 'superadmin')
                        <div class="col-md-4">
                            <label for="branch" class="form-label">Branch</label>
                            <select name="branch" id="branch" class="form-select">
                                <option value="">All Branches</option>
                                <option value="las_pinas" {{ request('branch') === 'las_pinas' ? 'selected' : '' }}>Las Piñas</option>
                                <option value="cebu" {{ request('branch') === 'cebu' ? 'selected' : '' }}>Cebu</option>
                                <option value="davao" {{ request('branch') === 'davao' ? 'selected' : '' }}>Davao</option>
                            </select>
                        </div>
                        @endif
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button>
                            <a href="{{ route('preventive-maintenance.index') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Original Task</th>
                                    <th>Branch</th>
                                    <th>Interval</th>
                                    <th>Last Serviced</th>
                                    <th>Next Due</th>
                                    <th>Status</th>
                                    <th>Generated</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schedules as $schedule)
                                @php
                                    $today = \Carbon\Carbon::now('Asia/Manila')->startOfDay();
                                    $daysLeft = $today->diffInDays($schedule->next_due_date, false);
                                    $openTask = $schedule->openGeneratedTask();
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $schedule->task->title }}</div>
                                        <small class="text-muted">{{ $schedule->task->task_number }}</small>
                                    </td>
                                    <td>{{ $schedule->task->branch_name }}</td>
                                    <td>{{ $schedule->frequency_description }}</td>
                                    <td>{{ ($schedule->last_maintenance_date ?? $schedule->task->completed_at)?->format('M d, Y') ?? '—' }}</td>
                                    <td>{{ $schedule->next_due_date->format('M d, Y') }}</td>
                                    <td>
                                        @if(!$schedule->is_active)
                                            <span class="badge bg-secondary">Inactive</span>
                                        @elseif($openTask)
                                            <a href="{{ route('tasks.show', $openTask) }}" class="badge bg-primary text-decoration-none">Task {{ $openTask->task_number }} open</a>
                                        @elseif($daysLeft < 0)
                                            <span class="badge bg-danger">Overdue {{ (int) abs($daysLeft) }} day(s)</span>
                                        @elseif($daysLeft == 0)
                                            <span class="badge bg-danger">Due today</span>
                                        @elseif($daysLeft <= 14)
                                            <span class="badge bg-warning text-dark">Due in {{ (int) $daysLeft }} day(s)</span>
                                        @else
                                            <span class="badge bg-success">Scheduled</span>
                                        @endif
                                    </td>
                                    <td>{{ $schedule->generated_tasks_count }}</td>
                                    <td>
                                        <a href="{{ route('tasks.show', $schedule->task) }}" class="btn btn-sm btn-outline-primary" title="View original task">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-calendar-x h2 d-block"></i>
                                        No maintenance schedules yet. Open a completed task and set a maintenance schedule to start.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $schedules->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

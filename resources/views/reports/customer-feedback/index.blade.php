@extends('layouts.app')

@section('title', 'Customer Evaluation Reports')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <h2 class="mb-0"><i class="bi bi-emoji-smile"></i> Customer Evaluation Reports</h2>
                <div class="btn-group" role="group">
                    <a class="btn btn-outline-primary" href="{{ route('reports.customer-feedback.export', array_merge(request()->all(), ['export' => 'pdf'])) }}"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
                    <a class="btn btn-outline-success" href="{{ route('reports.customer-feedback.export', array_merge(request()->all(), ['export' => 'excel'])) }}"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0">Filters</h6></div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">From</label>
                            <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To</label>
                            <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                        </div>
                        @if(auth()->user()->isSuperAdmin())
                        <div class="col-md-2">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch">
                                <option value="">All</option>
                                <option value="cebu" {{ request('branch')=='cebu'?'selected':'' }}>Cebu</option>
                                <option value="las_pinas" {{ request('branch')=='las_pinas'?'selected':'' }}>Las Piñas</option>
                                <option value="davao" {{ request('branch')=='davao'?'selected':'' }}>Davao</option>
                            </select>
                        </div>
                        @endif
                        <div class="col-md-2">
                            <label class="form-label">Rating</label>
                            <select class="form-select" name="rating">
                                <option value="">All</option>
                                @for($i=5;$i>=1;$i--)
                                    <option value="{{ $i }}" {{ request('rating')==$i?'selected':'' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Employee</label>
                            <select class="form-select" name="employee_id">
                                <option value="">All</option>
                                @foreach($employees as $e)
                                <option value="{{ $e->id }}" {{ request('employee_id')==$e->id?'selected':'' }}>{{ $e->first_name }} {{ $e->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row mb-3 g-3">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="mb-1">Total Evaluations</h6>
                            <h3 class="mb-0">{{ $stats['count'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="mb-1">Average Rating</h6>
                            <h3 class="mb-0">{{ $stats['avg'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="mb-1">Recommend %</h6>
                            <h3 class="mb-0">{{ $stats['recommend_percent'] ?? 0 }}%</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h6 class="mb-0">Evaluations</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Branch</th>
                                    <th>Task #</th>
                                    <th>Log #</th>
                                    <th>Rating</th>
                                    <th>Recommend</th>
                                    <th>Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($feedback as $row)
                                <tr>
                                    <td>{{ $row->submitted_at ? $row->submitted_at->format('Y-m-d') : 'N/A' }}</td>
                                    <td>{{ $row->employee ? $row->employee->full_name : 'Unknown' }}</td>
                                    <td>{{ ucfirst(str_replace('_',' ', $row->branch)) }}</td>
                                    <td>
                                        @if($row->task)
                                            <a href="{{ route('tasks.show', $row->task) }}">{{ $row->task->task_number }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($row->linked_log)
                                            <a href="{{ route('maintenance.show', $row->linked_log) }}">{{ $row->linked_log->log_number }}</a>
                                        @else
                                            <span class="text-muted">No log yet</span>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-primary">{{ $row->rating }}</span></td>
                                    <td>{!! $row->would_recommend ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
                                    <td>{{ Str::limit($row->feedback ?? '', 60) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No data</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $feedback->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



@extends('layouts.app')

@section('title', 'My Customer Evaluations')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-clipboard-check"></i> My Evaluations</h2>
                <div>
                    <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary"><i class="bi bi-list-task"></i> Tasks</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Submitted Evaluations</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Rating</th>
                                    <th>Recommend</th>
                                    <th>Task #</th>
                                    <th>Log #</th>
                                    <th>Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($feedback as $row)
                                <tr>
                                    <td>{{ $row->submitted_at ? $row->submitted_at->format('Y-m-d') : 'N/A' }}</td>
                                    <td>{{ $row->customer_name }}</td>
                                    <td><span class="badge bg-primary">{{ $row->rating }}</span></td>
                                    <td>{!! $row->would_recommend ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
                                    <td>{{ $row->task->task_number ?? '-' }}</td>
                                    <td>{{ $row->linked_log->log_number ?? '-' }}</td>
                                    <td>{{ Str::limit($row->feedback ?? '', 40) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No evaluations submitted yet.</td>
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



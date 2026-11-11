<!-- Employee Performance Report Section -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-people"></i> Employee Performance Report</h6>
    </div>
    <div class="card-body">
        @if($employees->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                    <tr>
                        <td>{{ $employee->employee_id ?? 'N/A' }}</td>
                        <td>{{ $employee->full_name ?? 'N/A' }}</td>
                        <td>{{ $employee->email ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-info">{{ ucfirst($employee->role ?? 'N/A') }}</span>
                        </td>
                        <td>{{ $employee->branch_name ?? ucfirst(str_replace('_', ' ', $employee->branch ?? 'N/A')) }}</td>
                        <td>{{ $employee->position ?? 'N/A' }}</td>
                        <td>{{ $employee->department ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $employee->is_active ? 'success' : 'danger' }}">
                                {{ $employee->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5">
            <i class="bi bi-people h1 text-muted"></i>
            <h4 class="text-muted">No Employees Found</h4>
            <p class="text-muted">No employees found for the selected filters.</p>
        </div>
        @endif
    </div>
</div>


{{-- Per-branch cost analysis, recurring issues and personnel performance.
     Expects $costSummary, $recurringIssues, $topPerformers (from App\Services\BranchAnalytics). --}}
@if(isset($costSummary))
@php
    $typeLabels = ['repair' => 'Repair', 'installation' => 'Installation', 'reinstallation' => 'Re-installation',
                   'dismantling' => 'Dismantling', 'checkup' => 'Check-up', 'maintenance_logs' => 'Logs'];
    $grandTotal = $costSummary->sum('total_cost');
@endphp
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Maintenance Frequency &amp; Cost Analysis by Branch</h5>
        <span class="fw-bold">Total: ₱{{ number_format($grandTotal, 2) }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th class="text-end">Maintenance Logs</th>
                        <th>By Type</th>
                        <th class="text-end">Repair Expenses</th>
                        <th class="text-end">Material Expenses</th>
                        <th class="text-end">Total Expenses</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($costSummary as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['label'] }}</td>
                        <td class="text-end">{{ $row['maintenance_count'] }}</td>
                        <td>
                            @forelse($row['maintenance_by_type'] as $type => $count)
                                <span class="badge bg-light text-dark border">{{ $typeLabels[$type] ?? ucfirst($type) }}: {{ $count }}</span>
                            @empty
                                <span class="text-muted">—</span>
                            @endforelse
                        </td>
                        <td class="text-end">₱{{ number_format($row['repair_cost'], 2) }}</td>
                        <td class="text-end">₱{{ number_format($row['material_cost'], 2) }}
                            <small class="text-muted d-block">{{ $row['material_requests'] }} approved request(s)</small>
                        </td>
                        <td class="text-end fw-bold">₱{{ number_format($row['total_cost'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <small class="text-muted">Material expenses use the actual cost when recorded, otherwise the approved estimate.</small>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-arrow-repeat"></i> Recurring Issues</h5>
            </div>
            <div class="card-body">
                @if($recurringIssues->isEmpty())
                    <p class="text-muted mb-0">No equipment has needed repeated service.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr><th>Equipment</th><th>Branch</th><th class="text-end">Times Serviced</th><th class="text-end">Cost</th><th>Last</th></tr>
                        </thead>
                        <tbody>
                            @foreach($recurringIssues as $issue)
                            <tr>
                                <td>{{ $issue['equipment_name'] }}</td>
                                <td>{{ $issue['branch'] }}</td>
                                <td class="text-end"><span class="badge bg-danger">{{ $issue['occurrences'] }}</span></td>
                                <td class="text-end">₱{{ number_format($issue['total_cost'], 2) }}</td>
                                <td>{{ $issue['last_seen']->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-trophy"></i> Personnel Performance</h5>
            </div>
            <div class="card-body">
                @if($topPerformers->isEmpty())
                    <p class="text-muted mb-0">No employee data yet.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr><th>Employee</th><th>Branch</th><th class="text-end">Completed</th><th class="text-end">Overdue</th><th class="text-end">Customer Rating</th></tr>
                        </thead>
                        <tbody>
                            @foreach($topPerformers as $person)
                            <tr>
                                <td>{{ $person['name'] }}</td>
                                <td>{{ $person['branch'] }}</td>
                                <td class="text-end">{{ $person['completed_tasks'] }}</td>
                                <td class="text-end">{{ $person['overdue_tasks'] }}</td>
                                <td class="text-end">
                                    @if($person['avg_rating'])
                                        <i class="bi bi-star-fill text-warning"></i> {{ $person['avg_rating'] }}
                                        <small class="text-muted">({{ $person['reviews'] }})</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

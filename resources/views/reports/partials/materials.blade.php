<!-- Materials Report Section -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-box-seam"></i> Material Requests Report</h6>
    </div>
    <div class="card-body">
        @php
            // * Keys match material_requests.status and the categories on the request form
            $statusLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'ordered' => 'Ordered', 'received' => 'Received', 'rejected' => 'Rejected'];
            $statusColors = ['pending' => '#ffc107', 'approved' => '#198754', 'ordered' => '#0d6efd', 'received' => '#0dcaf0', 'rejected' => '#dc3545'];
            $statusBadges = ['pending' => 'warning', 'approved' => 'success', 'ordered' => 'primary', 'received' => 'info', 'rejected' => 'danger'];
            $categoryLabels = [
                'cleaning_supplies' => 'Cleaning Supplies', 'maintenance_tools' => 'Maintenance Tools', 'office_supplies' => 'Office Supplies',
                'safety_equipment' => 'Safety Equipment', 'electrical_supplies' => 'Electrical Supplies', 'plumbing_supplies' => 'Plumbing Supplies',
                'chemicals' => 'Chemicals', 'spare_parts' => 'Spare Parts', 'uniforms' => 'Uniforms', 'other' => 'Other',
            ];
            // * Any category not in the list above still gets its own bar
            foreach ($materialRequests->pluck('category')->filter()->unique() as $extra) {
                $categoryLabels[$extra] ??= ucwords(str_replace('_', ' ', $extra));
            }
            $statusCounts = collect($statusLabels)->map(fn ($label, $key) => $materialRequests->where('status', $key)->count());
            $categoryCounts = collect($categoryLabels)->map(fn ($label, $key) => $materialRequests->where('category', $key)->count());
            $spentRequests = $materialRequests->whereIn('status', ['approved', 'ordered', 'received']);
        @endphp
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $materialRequests->count() }}</h4>
                                <p class="card-text">Total Requests</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-box-seam fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $spentRequests->count() }}</h4>
                                <p class="card-text mb-0">Approved</p>
                                <small>incl. ordered &amp; received</small>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $materialRequests->where('status', 'pending')->count() }}</h4>
                                <p class="card-text">Pending</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                {{-- * Same rule as the cost analysis: approved/ordered/received, actual cost when recorded --}}
                                <h4 class="card-title">₱{{ number_format($spentRequests->sum(fn ($request) => $request->actual_cost ?? $request->total_estimated_cost ?? 0), 2) }}</h4>
                                <p class="card-text mb-0">Total Cost</p>
                                <small>approved requests only</small>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-cash-stack fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Material Request Charts -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div style="height: 300px; position: relative;">
                    <canvas id="materialStatusChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div style="height: 300px; position: relative;">
                    <canvas id="materialCategoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Material Requests Table -->
        @if($materialRequests->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Request #</th>
                        <th>Item</th>
                        <th>Requested By</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Cost</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materialRequests->take(10) as $request)
                    <tr>
                        <td>
                            <strong>{{ $request->request_number }}</strong>
                        </td>
                        <td>
                            @php
                                $materials = $request->materials ?? [];
                                $firstMaterial = !empty($materials) ? $materials[0] : null;
                            @endphp
                            @if($firstMaterial)
                                <strong>{{ $firstMaterial['name'] ?? 'Unknown Material' }}</strong><br>
                                <small class="text-muted">Qty: {{ $firstMaterial['quantity'] ?? 0 }} {{ $firstMaterial['unit'] ?? 'pcs' }}</small>
                                @if(count($materials) > 1)
                                    <br><small class="text-muted">+{{ count($materials) - 1 }} more item(s)</small>
                                @endif
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($request->requestedBy)
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <span class="text-white fw-bold">{{ substr($request->requestedBy->first_name, 0, 1) }}{{ substr($request->requestedBy->last_name, 0, 1) }}</span>
                                </div>
                                <div>
                                    <strong>{{ $request->requestedBy->full_name }}</strong>
                                </div>
                            </div>
                            @else
                            <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                {{ $request->category ? ($categoryLabels[$request->category] ?? ucwords(str_replace('_', ' ', $request->category))) : 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $request->priority == 'urgent' ? 'danger' : ($request->priority == 'high' ? 'warning' : (in_array($request->priority, ['medium', 'normal']) ? 'info' : 'success')) }}">
                                {{ ucfirst($request->priority) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $statusBadges[$request->status] ?? 'secondary' }}">
                                {{ $statusLabels[$request->status] ?? ucfirst($request->status) }}
                            </span>
                        </td>
                        <td>
                            @php
                                $cost = $request->actual_cost ?? $request->total_estimated_cost ?? null;
                            @endphp
                            @if($cost)
                                <strong>₱{{ number_format($cost, 2) }}</strong>
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </td>
                        <td>
                            <small>{{ $request->created_at->format('M d, Y') }}</small>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($materialRequests->count() > 10)
        <div class="text-center mt-3">
            <small class="text-muted">Showing first 10 material requests. Total: {{ $materialRequests->count() }} requests</small>
        </div>
        @endif

        @else
        <div class="text-center py-5">
            <i class="bi bi-box-seam h1 text-muted"></i>
            <h4 class="text-muted">No Material Requests Found</h4>
            <p class="text-muted">No material requests found for the selected date range.</p>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart is available
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return;
    }

// Material Status Chart
    const materialStatusCanvas = document.getElementById('materialStatusChart');
    if (materialStatusCanvas) {
        const materialStatusCtx = materialStatusCanvas.getContext('2d');
        new Chart(materialStatusCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($statusCounts->keys()->map(fn ($key) => $statusLabels[$key])->values()) !!},
        datasets: [{
            data: {!! json_encode($statusCounts->values()) !!},
            backgroundColor: {!! json_encode(array_values($statusColors)) !!},
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
                maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            title: {
                display: true,
                text: 'Request Status Distribution'
            }
        }
    }
});
    }

// Material Category Chart
    const materialCategoryCanvas = document.getElementById('materialCategoryChart');
    if (materialCategoryCanvas) {
        const materialCategoryCtx = materialCategoryCanvas.getContext('2d');
        new Chart(materialCategoryCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode(array_values($categoryLabels)) !!},
        datasets: [{
            label: 'Number of Requests',
            data: {!! json_encode($categoryCounts->values()) !!},
            backgroundColor: '#0d6efd',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
                maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                        text: 'Request Category Distribution'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
            }
        });
    }
});
</script>
@endpush

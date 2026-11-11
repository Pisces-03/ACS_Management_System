@extends('layouts.app')

@section('title', 'Attendance Management')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <div>
                    <h2 class="mb-0"><i class="bi bi-clock"></i> Attendance Management</h2>
                    @if(isset($viewingEmployee) && $viewingEmployee)
                    <p class="text-muted mb-0">
                        <i class="bi bi-person"></i> Viewing attendance for: <strong>{{ $viewingEmployee->full_name }}</strong>
                        @if($viewingEmployee->position)
                            <span class="text-muted">({{ $viewingEmployee->position }})</span>
                        @endif
                    </p>
                    @endif
                </div>

                <!-- Time In/Out Buttons - Only show when viewing own attendance -->
                @if(!isset($viewingEmployee) || !$viewingEmployee || $viewingEmployee->id === auth()->id())
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-success" id="timeInBtn"
                            data-bs-toggle="tooltip" data-bs-placement="bottom" title="Record your arrival time">
                        <i class="bi bi-clock-history"></i> Time In
                    </button>
                    <button type="button" class="btn btn-warning" id="timeOutBtn"
                            data-bs-toggle="tooltip" data-bs-placement="bottom" title="Record your departure time">
                        <i class="bi bi-clock"></i> Time Out
                    </button>
                </div>
                @endif
            </div>

            <!-- Today's Status Card - Only show when viewing own attendance -->
            @if(!isset($viewingEmployee) || !$viewingEmployee || $viewingEmployee->id === auth()->id())
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Today's Status - {{ now()->format('F d, Y') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3" id="todayStatus">
                                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                    <div class="text-center">
                                        <i class="bi bi-clock-history h3 text-success"></i>
                                        <h6>Time In</h6>
                                        <span id="timeInDisplay" class="badge bg-light text-dark">--:--</span>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                    <div class="text-center">
                                        <i class="bi bi-clock h3 text-warning"></i>
                                        <h6>Time Out</h6>
                                        <span id="timeOutDisplay" class="badge bg-light text-dark">--:--</span>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                    <div class="text-center">
                                        <i class="bi bi-stopwatch h3 text-info"></i>
                                        <h6>Total Hours</h6>
                                        <span id="totalHoursDisplay" class="badge bg-light text-dark">--</span>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                    <div class="text-center">
                                        <i class="bi bi-calendar-check h3 text-primary"></i>
                                        <h6>Status</h6>
                                        <span id="statusDisplay" class="badge bg-secondary">Not Started</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Attendance History -->
            <div class="card">
                <div class="card-header">
                    {{-- * Attendance reports live in Reports & Analytics > Attendance Report --}}
                    <h5 class="mb-0">Attendance History</h5>
                </div>
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-striped" id="attendanceHistoryTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Total Hours</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceHistoryBody">
                                @forelse($attendanceLogs as $log)
                                <tr>
                                    <td>{{ $log->date->format('M d, Y') }}</td>
                                    <td>
                                        @if($log->time_in)
                                            @php
                                                try {
                                                    $timeIn = \Carbon\Carbon::createFromFormat('H:i:s', $log->time_in)->format('h:i A');
                                                } catch (\Exception $e) {
                                                    $timeIn = $log->time_in;
                                                }
                                            @endphp
                                            <span class="badge bg-success">{{ $timeIn }}</span>
                                        @else
                                            <span class="text-muted">--:--</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->time_out)
                                            @php
                                                try {
                                                    $timeOut = \Carbon\Carbon::createFromFormat('H:i:s', $log->time_out)->format('h:i A');
                                                } catch (\Exception $e) {
                                                    $timeOut = $log->time_out;
                                                }
                                            @endphp
                                            <span class="badge bg-warning">{{ $timeOut }}</span>
                                        @else
                                            <span class="text-muted">--:--</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->total_hours)
                                            {{ number_format($log->total_hours, 2) }} hrs
                                        @else
                                            <span class="text-muted">--</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($log->status)
                                            @case('present')
                                                <span class="badge bg-success">Present</span>
                                                @break
                                            @case('late')
                                                <span class="badge bg-warning">Late</span>
                                                @break
                                            @case('absent')
                                                <span class="badge bg-danger">Absent</span>
                                                @break
                                            @case('halfday')
                                                <span class="badge bg-info">Half Day</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $log->status }}</span>
                                        @endswitch
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        No attendance records found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    {{ $attendanceLogs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // * Check if viewing own attendance (buttons exist)
    const timeInBtn = document.getElementById('timeInBtn');
    const timeOutBtn = document.getElementById('timeOutBtn');
    const isViewingOwnAttendance = timeInBtn && timeOutBtn;

    // * Only initialize attendance buttons if viewing own attendance
    if (isViewingOwnAttendance) {
        // * Define loadTodayStatus function first
        function loadTodayStatus() {
            fetch('{{ route("attendance.today-status") }}', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.status === 401) {
                    window.location.href = '/login';
                    return;
                }
                if (!response.ok) {
                    // * Handle non-OK responses gracefully
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error('Server error: ' + text.substring(0, 200));
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                // * Check if status display elements exist before updating
                const timeInDisplay = document.getElementById('timeInDisplay');
                const timeOutDisplay = document.getElementById('timeOutDisplay');
                const totalHoursDisplay = document.getElementById('totalHoursDisplay');
                const statusElement = document.getElementById('statusDisplay');

                if (timeInDisplay) {
                    timeInDisplay.textContent = data.time_in || '--:--';
                }
                if (timeOutDisplay) {
                    timeOutDisplay.textContent = data.time_out || '--:--';
                }
                if (totalHoursDisplay) {
                    totalHoursDisplay.textContent = data.total_hours ? parseFloat(data.total_hours).toFixed(2) + ' hrs' : '--';
                }

                // Update status
                if (statusElement) {
                    if (data.has_timed_out) {
                        statusElement.textContent = 'Completed';
                        statusElement.className = 'badge bg-success';
                    } else if (data.has_timed_in) {
                        statusElement.textContent = 'Working';
                        statusElement.className = 'badge bg-primary';
                    } else {
                        statusElement.textContent = 'Not Started';
                        statusElement.className = 'badge bg-secondary';
                    }
                }

                // Update button states - be less aggressive about disabling
                const timeInBtn = document.getElementById('timeInBtn');
                const timeOutBtn = document.getElementById('timeOutBtn');

                if (timeInBtn && timeOutBtn) {
                    if (data.has_timed_in && !data.has_timed_out) {
                        // User has timed in, allow time out
                        timeInBtn.disabled = true;
                        timeInBtn.classList.remove('btn-success');
                        timeInBtn.classList.add('btn-outline-success');
                        timeInBtn.innerHTML = '<i class="bi bi-check-circle"></i> Timed In';
                        timeInBtn.setAttribute('data-bs-original-title', 'You have already timed in today');

                        // Only enable time out if it's not currently processing
                        if (!timeOutBtn.innerHTML.includes('Processing')) {
                            timeOutBtn.disabled = false;
                            timeOutBtn.classList.remove('btn-outline-warning');
                            timeOutBtn.classList.add('btn-warning');
                            timeOutBtn.innerHTML = '<i class="bi bi-clock"></i> Time Out';
                            timeOutBtn.style.cursor = 'pointer';
                            timeOutBtn.style.pointerEvents = 'auto';
                            timeOutBtn.setAttribute('data-bs-original-title', 'Click to record your departure time');
                        }
                    } else if (data.has_timed_out) {
                        // User has completed day
                        timeInBtn.disabled = true;
                        timeInBtn.classList.remove('btn-success');
                        timeInBtn.classList.add('btn-success');
                        timeInBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Day Completed';
                        timeInBtn.setAttribute('data-bs-original-title', 'Your workday is complete');

                        timeOutBtn.disabled = true;
                        timeOutBtn.classList.remove('btn-warning');
                        timeOutBtn.classList.add('btn-success');
                        timeOutBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Day Completed';
                        timeOutBtn.setAttribute('data-bs-original-title', 'Your workday is complete');
                    } else {
                        // User hasn't timed in yet
                        timeInBtn.disabled = false;
                        timeInBtn.classList.remove('btn-outline-success');
                        timeInBtn.classList.add('btn-success');
                        timeInBtn.innerHTML = '<i class="bi bi-clock-history"></i> Time In';
                        timeInBtn.style.cursor = 'pointer';
                        timeInBtn.style.pointerEvents = 'auto';
                        timeInBtn.setAttribute('data-bs-original-title', 'Click to record your arrival time');

                        timeOutBtn.disabled = true;
                        timeOutBtn.classList.remove('btn-warning');
                        timeOutBtn.classList.add('btn-outline-warning');
                        timeOutBtn.innerHTML = '<i class="bi bi-clock"></i> Time Out';
                        timeOutBtn.style.cursor = 'not-allowed';
                        timeOutBtn.style.pointerEvents = 'none';
                        timeOutBtn.setAttribute('data-bs-original-title', 'Please time in first');
                    }
                }
            })
            .catch(error => {
                console.error('Error loading today status:', error);
                // * Only show error if viewing own attendance
                if (isViewingOwnAttendance) {
                    Swal.fire({
                        title: 'Warning!',
                        text: 'Could not load attendance status. Please refresh the page.',
                        icon: 'warning',
                        confirmButtonColor: '#ffc107'
                    });
                }
            });
        }

        // Load today's status with a delay to allow user interaction
        setTimeout(function() {
            loadTodayStatus();
        }, 2000); // 2 second delay

        // Time In button
        timeInBtn.addEventListener('click', function() {
        Swal.fire({
            title: 'Time In',
            text: 'Are you ready to start your work day?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Time In!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    text: 'Recording your time in',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading()
                    }
                });

                fetch('{{ route("attendance.time-in") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (response.status === 401) {
                        window.location.href = '/login';
                        return Promise.reject('Unauthorized');
                    }
                    if (!response.ok) {
                        return response.text().then(text => {
                            try {
                                const json = JSON.parse(text);
                                return json;
                            } catch (e) {
                                throw new Error('Server error: ' + text.substring(0, 200));
                            }
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Time in recorded successfully at ' + (data.time_in || ''),
                            icon: 'success',
                            confirmButtonColor: '#198754'
                        });
                        loadTodayStatus();
                        loadAttendanceHistory(); // Refresh attendance history
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: (data && data.message) || 'Failed to record time in',
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: error.message || 'Network error. Please check your connection and try again.',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                });
            }
        });
        });

        // Time Out button
        timeOutBtn.addEventListener('click', function(e) {
        // Prevent action if button is disabled
        if (this.disabled || this.classList.contains('btn-outline-warning')) {
            e.preventDefault();
            return false;
        }

        // Temporarily disable the button to prevent double clicks
        const originalText = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';

        Swal.fire({
            title: 'Time Out',
            text: 'Are you finished with your work for today?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Time Out!',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    text: 'Recording your time out',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading()
                    }
                });

                fetch('{{ route("attendance.time-out") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {

                    if (response.status === 401) {
                        window.location.href = '/login';
                        return;
                    }

                    // Get response as text first to debug
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            throw new Error('Invalid JSON response: ' + text);
                        }
                    });
                })
                .then(data => {
                    if (data && data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Time out recorded successfully at ' + data.time_out + '. Total hours: ' + data.total_hours,
                            icon: 'success',
                            confirmButtonColor: '#ffc107'
                        });
                        loadTodayStatus();
                        loadAttendanceHistory(); // Refresh attendance history
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: (data && data.message) || 'Failed to record time out',
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                        // Re-enable button on error
                        this.disabled = false;
                        this.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: error.message || 'Network error or invalid response. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                    // Re-enable button on error
                    this.disabled = false;
                    this.innerHTML = originalText;
                });
            } else {
                // User cancelled, re-enable button
                this.disabled = false;
                this.innerHTML = originalText;
            }
        });
        });

        // Auto-refresh today's status every 10 seconds
        setInterval(function() {
            if (!document.hidden) { // Only refresh if tab is visible
                loadTodayStatus();
            }
        }, 10000);
    }

    // Real-time update for attendance history table
    function loadAttendanceHistory() {
        // Build URL with current filters
        const url = new URL('{{ route("attendance.index") }}', window.location.origin);
        
        // Get current filter values from the form if it exists
        const dateFromInput = document.querySelector('input[name="date_from"]');
        const dateToInput = document.querySelector('input[name="date_to"]');
        
        if (dateFromInput && dateFromInput.value) {
            url.searchParams.set('date_from', dateFromInput.value);
        }
        if (dateToInput && dateToInput.value) {
            url.searchParams.set('date_to', dateToInput.value);
        }
        
        url.searchParams.set('ajax', '1'); // Flag for AJAX request

        fetch(url.toString(), {
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const currentTableBody = document.getElementById('attendanceHistoryBody');
            if (currentTableBody) {
                currentTableBody.innerHTML = html;
            }
        })
        .catch(error => {
            console.error('Error loading attendance history:', error);
        });
    }

    // Auto-refresh attendance history every 15 seconds
    setInterval(function() {
        if (!document.hidden) { // Only refresh if tab is visible
            loadAttendanceHistory();
        }
    }, 15000);
});
</script>
@endpush

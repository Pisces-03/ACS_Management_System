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


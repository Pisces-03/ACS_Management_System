<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\AuditLog;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = AttendanceLog::with('user');

        // * Check if viewing a specific employee's attendance (via user_id or employee_id parameter)
        $viewingEmployeeId = $request->filled('user_id') ? $request->user_id : ($request->filled('employee_id') ? $request->employee_id : null);

        if ($viewingEmployeeId) {
            // * Admin/Superadmin viewing specific employee's attendance
            if ($user->role === 'employee') {
                // * Employees can only view their own attendance
                if ($viewingEmployeeId != $user->id) {
                    abort(403, 'You can only view your own attendance');
                }
                $query->where('user_id', $user->id);
            } elseif ($user->role === 'admin') {
                // * Admin can only view employees from their branch
                $targetEmployee = User::find($viewingEmployeeId);
                if (!$targetEmployee || $targetEmployee->branch !== $user->branch) {
                    abort(403, 'You can only view attendance for employees in your branch');
                }
                $query->where('user_id', $viewingEmployeeId);
            } else {
                // * Superadmin can view any employee's attendance
                $query->where('user_id', $viewingEmployeeId);
            }
        } else {
            // * No specific employee requested - show based on role
            if ($user->role === 'employee') {
                $query->where('user_id', $user->id);
            } elseif ($user->role === 'admin') {
                // * Admin can only view their own attendance
                $query->where('user_id', $user->id);
            }
            // * Superadmin can see all (no filter needed)
        }

        // Date filters
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $attendanceLogs = $query->orderBy('date', 'desc')
            ->orderBy('time_in', 'desc')
            ->paginate(15);

        // Get employees for filter dropdown
        $employees = User::where('role', 'employee')
            ->when($user->role === 'admin', function($q) use ($user) {
                $q->where('branch', $user->branch);
            })
            ->orderBy('first_name')
            ->get();

        // * Get the employee being viewed (if viewing specific employee's attendance)
        $viewingEmployee = null;
        if ($viewingEmployeeId) {
            $viewingEmployee = User::find($viewingEmployeeId);
        }

        // If AJAX request, return only the table body
        if ($request->ajax() || $request->has('ajax')) {
            return view('attendance.partials.table-body', compact('attendanceLogs'));
        }

        return view('attendance.index', compact('attendanceLogs', 'employees', 'viewingEmployee'));
    }

    public function timeIn(Request $request)
    {
        // Log to both Laravel log and PHP error_log for debugging
        $debugInfo = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => [
                'accept' => $request->header('Accept'),
                'x-requested-with' => $request->header('X-Requested-With'),
                'content-type' => $request->header('Content-Type'),
            ],
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ];
        
        \Log::info('=== TIME IN REQUEST START ===', $debugInfo);
        error_log('TIME_IN_START: ' . json_encode($debugInfo));

        try {
            $user = auth()->user();
            
            \Log::info('User authenticated', [
                'user_id' => $user ? $user->id : null,
                'user_email' => $user ? $user->email : null,
                'user_branch' => $user ? $user->branch : null,
            ]);
            
            if (!$user) {
                \Log::warning('Time in failed: User not authenticated');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401)->header('Content-Type', 'application/json');
            }

            // Get Philippine time - use UTC offset if timezone not available
            try {
                $philippineTime = \Carbon\Carbon::now('Asia/Manila');
                \Log::info('Philippine time retrieved using Asia/Manila timezone', [
                    'formatted_time' => $philippineTime->format('Y-m-d H:i:s'),
                    'timezone' => $philippineTime->timezone->getName(),
                ]);
            } catch (\Exception $e) {
                // Fallback: Use UTC and add 8 hours for Philippine time
                $philippineTime = \Carbon\Carbon::now('UTC')->addHours(8);
                \Log::warning('Asia/Manila timezone not available, using UTC+8 offset', [
                    'error' => $e->getMessage(),
                    'formatted_time' => $philippineTime->format('Y-m-d H:i:s'),
                ]);
            }
            
            $today = $philippineTime->format('Y-m-d');
            \Log::info('Today date calculated', ['date' => $today]);

            // Use direct date comparison for better production compatibility
            // Try multiple methods for maximum compatibility
            \Log::info('Checking for existing attendance record', [
                'user_id' => $user->id,
                'date' => $today,
            ]);

            try {
                $existingAttendance = AttendanceLog::where('user_id', $user->id)
                    ->whereDate('date', $today)
                    ->first();
                \Log::info('Existing attendance check (whereDate method)', [
                    'found' => $existingAttendance ? true : false,
                    'has_time_in' => $existingAttendance && $existingAttendance->time_in ? true : false,
                    'time_in' => $existingAttendance && $existingAttendance->time_in ? $existingAttendance->time_in : null,
                ]);
            } catch (\Exception $e) {
                \Log::warning('whereDate method failed, trying whereRaw', [
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                ]);
                // Fallback using whereRaw
                try {
                    $existingAttendance = AttendanceLog::where('user_id', $user->id)
                        ->whereRaw('DATE(date) = ?', [$today])
                        ->first();
                    \Log::info('Existing attendance check (whereRaw method)', [
                        'found' => $existingAttendance ? true : false,
                    ]);
                } catch (\Exception $e2) {
                    \Log::warning('whereRaw method failed, trying direct string comparison', [
                        'error' => $e2->getMessage(),
                        'error_code' => $e2->getCode(),
                    ]);
                    // Last resort - string comparison
                    $existingAttendance = AttendanceLog::where('user_id', $user->id)
                        ->where('date', $today)
                        ->first();
                    \Log::info('Existing attendance check (direct string method)', [
                        'found' => $existingAttendance ? true : false,
                    ]);
                }
            }

            if ($existingAttendance && $existingAttendance->time_in) {
                \Log::info('User already timed in today', [
                    'existing_time_in' => $existingAttendance->time_in,
                    'attendance_id' => $existingAttendance->id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'You have already timed in today at ' . $existingAttendance->time_in
                ], 400)->header('Content-Type', 'application/json');
            }

            // Try to create attendance record with error handling
            \Log::info('Attempting to create/update attendance record', [
                'user_id' => $user->id,
                'branch' => $user->branch,
            ]);

            try {
                $attendance = AttendanceLog::timeIn($user->id, $user->branch);
                
                \Log::info('AttendanceLog::timeIn() completed', [
                    'attendance_id' => $attendance ? $attendance->id : null,
                    'time_in' => $attendance ? $attendance->time_in : null,
                    'date' => $attendance ? $attendance->date : null,
                ]);
                
                if (!$attendance) {
                    \Log::error('AttendanceLog::timeIn() returned null');
                    throw new \Exception('Failed to create attendance record');
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $errorDetails = [
                    'user_id' => $user->id,
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'sql_state' => $e->getCode(),
                    'sql' => method_exists($e, 'getSql') ? $e->getSql() : 'N/A',
                    'bindings' => method_exists($e, 'getBindings') ? $e->getBindings() : [],
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ];
                
                \Log::error('Database QueryException creating attendance', $errorDetails);
                error_log('TIME_IN_QUERY_EXCEPTION: ' . json_encode($errorDetails));
                
                // Return error response
                $response = [
                    'success' => false,
                    'message' => 'Database error occurred. Please try again.',
                ];
                
                // Only include debug info if APP_DEBUG is enabled
                if (config('app.debug')) {
                    $response['debug'] = [
                        'error_type' => 'QueryException',
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'file' => basename($e->getFile()),
                        'line' => $e->getLine(),
                    ];
                }
                
                return response()->json($response, 500)->header('Content-Type', 'application/json');
            } catch (\Exception $e) {
                $errorDetails = [
                    'user_id' => $user->id,
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'class' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ];
                
                \Log::error('General Exception creating attendance record', $errorDetails);
                error_log('TIME_IN_GENERAL_EXCEPTION: ' . json_encode($errorDetails));
                
                // Return error response
                $response = [
                    'success' => false,
                    'message' => 'Failed to record time in. Please try again.',
                ];
                
                // Only include debug info if APP_DEBUG is enabled
                if (config('app.debug')) {
                    $response['debug'] = [
                        'error_type' => get_class($e),
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'file' => basename($e->getFile()),
                        'line' => $e->getLine(),
                    ];
                }
                
                return response()->json($response, 500)->header('Content-Type', 'application/json');
            }

            // Try to log audit, but don't fail the time-in if logging fails
            try {
                \Log::info('Attempting to log audit for time-in');
                AuditLog::log(
                    'time_in',
                    "Employee timed in at {$attendance->time_in}",
                    $attendance
                );
                \Log::info('Audit log created successfully');
            } catch (\Exception $e) {
                \Log::warning('Failed to log time-in audit (non-critical)', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Continue - don't fail the time-in operation
            }

            \Log::info('=== TIME IN SUCCESS ===', [
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'time_in' => $philippineTime->format('H:i:s'),
                'date' => $today,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Time in recorded successfully',
                'time_in' => $philippineTime->format('H:i:s')
            ])->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $fatalErrorDetails = [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ];
            
            \Log::error('=== TIME IN FATAL ERROR ===', $fatalErrorDetails);
            error_log('TIME_IN_FATAL_ERROR: ' . json_encode($fatalErrorDetails));
            
            // Return error response
            $response = [
                'success' => false,
                'message' => 'An error occurred while recording time in. Please try again.',
            ];
            
            // Only include debug info if APP_DEBUG is enabled
            if (config('app.debug')) {
                $response['debug'] = [
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                    'trace_preview' => substr($e->getTraceAsString(), 0, 500),
                ];
            }
            
            return response()->json($response, 500)->header('Content-Type', 'application/json');
        }
    }

    public function timeOut(Request $request)
    {
        // Log to both Laravel log and PHP error_log for debugging
        $debugInfo = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ];
        
        \Log::info('=== TIME OUT REQUEST START ===', $debugInfo);
        error_log('TIME_OUT_START: ' . json_encode($debugInfo));

        try {
            $user = auth()->user();
            
            \Log::info('User authenticated for time-out', [
                'user_id' => $user ? $user->id : null,
                'user_email' => $user ? $user->email : null,
            ]);
            
            if (!$user) {
                \Log::warning('Time out failed: User not authenticated');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401)->header('Content-Type', 'application/json');
            }
            
            // Get Philippine time - use UTC offset if timezone not available
            try {
                $philippineTime = \Carbon\Carbon::now('Asia/Manila');
                \Log::info('Philippine time retrieved for time-out', [
                    'formatted_time' => $philippineTime->format('Y-m-d H:i:s'),
                ]);
            } catch (\Exception $e) {
                // Fallback: Use UTC and add 8 hours for Philippine time
                $philippineTime = \Carbon\Carbon::now('UTC')->addHours(8);
                \Log::warning('Asia/Manila timezone not available for time-out, using UTC+8 offset', [
                    'error' => $e->getMessage(),
                ]);
            }

            \Log::info('Attempting to update attendance record for time-out', [
                'user_id' => $user->id,
            ]);

            // Try to get attendance record with error handling
            try {
                $attendance = AttendanceLog::timeOut($user->id);
                
                \Log::info('AttendanceLog::timeOut() completed', [
                    'attendance_id' => $attendance ? $attendance->id : null,
                    'time_out' => $attendance ? $attendance->time_out : null,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $errorDetails = [
                    'user_id' => $user->id,
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'sql_state' => $e->getCode(),
                    'sql' => method_exists($e, 'getSql') ? $e->getSql() : 'N/A',
                    'bindings' => method_exists($e, 'getBindings') ? $e->getBindings() : [],
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ];
                
                \Log::error('Database QueryException during time-out', $errorDetails);
                error_log('TIME_OUT_QUERY_EXCEPTION: ' . json_encode($errorDetails));
                
                // Return error with debug info
                // Always include basic debug info for database errors to help diagnose issues
                $response = [
                    'success' => false,
                    'message' => 'Database error occurred. Please try again.',
                    'debug' => [
                        'error_type' => 'QueryException',
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'file' => basename($e->getFile()),
                        'line' => $e->getLine(),
                    ],
                ];
                
                // Include SQL details only if APP_DEBUG is enabled
                if (config('app.debug')) {
                    $response['debug']['sql_preview'] = method_exists($e, 'getSql') ? substr($e->getSql(), 0, 200) : 'N/A';
                    $response['debug']['sql_state'] = $e->getCode();
                }
                
                return response()->json($response, 500)->header('Content-Type', 'application/json');
            } catch (\Exception $e) {
                $errorDetails = [
                    'user_id' => $user->id,
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'class' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ];
                
                \Log::error('General Exception during time-out', $errorDetails);
                error_log('TIME_OUT_GENERAL_EXCEPTION: ' . json_encode($errorDetails));
                
                // Return error with debug info
                $response = [
                    'success' => false,
                    'message' => 'Failed to record time out. Please try again.',
                ];
                
                // Only include debug info if APP_DEBUG is enabled
                if (config('app.debug')) {
                    $response['debug'] = [
                        'error_type' => get_class($e),
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'file' => basename($e->getFile()),
                        'line' => $e->getLine(),
                    ];
                }
                
                return response()->json($response, 500)->header('Content-Type', 'application/json');
            }

            if (!$attendance) {
                // * Tell the employee when the day is already finished instead of "time in first"
                $finished = AttendanceLog::where('user_id', $user->id)
                    ->whereDate('date', $philippineTime->format('Y-m-d'))
                    ->whereNotNull('time_out')
                    ->first();

                return response()->json([
                    'success' => false,
                    'message' => $finished
                        ? 'You have already timed out today at ' . $finished->time_out . '.'
                        : 'No time in record found for today. Please time in first.'
                ], 400)->header('Content-Type', 'application/json');
            }

            // Try to log audit, but don't fail the time-out if logging fails
            try {
                AuditLog::log(
                    'time_out',
                    "Employee timed out at {$attendance->time_out}. Total hours: {$attendance->total_hours}",
                    $attendance
                );
            } catch (\Exception $e) {
                \Log::warning('Failed to log time-out audit: ' . $e->getMessage());
                // Continue - don't fail the time-out operation
            }

            return response()->json([
                'success' => true,
                'message' => 'Time out recorded successfully',
                'time_out' => $philippineTime->format('H:i:s'),
                'total_hours' => number_format($attendance->total_hours, 1) . ' hours'
            ])->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $fatalErrorDetails = [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ];
            
            \Log::error('=== TIME OUT FATAL ERROR ===', $fatalErrorDetails);
            error_log('TIME_OUT_FATAL_ERROR: ' . json_encode($fatalErrorDetails));
            
            // Return error response
            $response = [
                'success' => false,
                'message' => 'An error occurred while recording time out. Please try again.',
            ];
            
            // Only include debug info if APP_DEBUG is enabled
            if (config('app.debug')) {
                $response['debug'] = [
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                    'trace_preview' => substr($e->getTraceAsString(), 0, 500),
                ];
            }
            
            return response()->json($response, 500)->header('Content-Type', 'application/json');
        }
    }

    public function todayStatus()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'has_timed_in' => false,
                    'has_timed_out' => false,
                    'time_in' => null,
                    'time_out' => null,
                    'total_hours' => null,
                ], 401)->header('Content-Type', 'application/json');
            }
            
            // Get Philippine time - use UTC offset if timezone not available
            try {
                $philippineTime = \Carbon\Carbon::now('Asia/Manila');
            } catch (\Exception $e) {
                // Fallback: Use UTC and add 8 hours for Philippine time
                $philippineTime = \Carbon\Carbon::now('UTC')->addHours(8);
                \Log::warning('Asia/Manila timezone not available, using UTC+8 offset: ' . $e->getMessage());
            }
            
            $today = $philippineTime->format('Y-m-d');

            $attendance = null;
            
            // Use direct date comparison for better production compatibility
            // Try multiple methods for maximum compatibility
            try {
                $attendance = AttendanceLog::where('user_id', $user->id)
                    ->whereDate('date', $today)
                    ->first();
            } catch (\Illuminate\Database\QueryException $e) {
                // Database query error - try fallback
                \Log::warning('whereDate failed, trying whereRaw: ' . $e->getMessage());
                try {
                    $attendance = AttendanceLog::where('user_id', $user->id)
                        ->whereRaw('DATE(date) = ?', [$today])
                        ->first();
                } catch (\Exception $e2) {
                    // Last resort - string comparison
                    \Log::warning('whereRaw failed, trying direct string: ' . $e2->getMessage());
                    try {
                        $attendance = AttendanceLog::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();
                    } catch (\Exception $e3) {
                        \Log::error('All date comparison methods failed: ' . $e3->getMessage());
                        // Return empty status
                        return response()->json([
                            'has_timed_in' => false,
                            'has_timed_out' => false,
                            'time_in' => null,
                            'time_out' => null,
                            'total_hours' => null,
                        ])->header('Content-Type', 'application/json');
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Unexpected error in todayStatus: ' . $e->getMessage());
                // Return empty status
                return response()->json([
                    'has_timed_in' => false,
                    'has_timed_out' => false,
                    'time_in' => null,
                    'time_out' => null,
                    'total_hours' => null,
                ])->header('Content-Type', 'application/json');
            }

            $timeIn = null;
            $timeOut = null;

            if ($attendance) {
                // Handle time_in formatting
                if ($attendance->time_in) {
                    try {
                        $timeIn = \Carbon\Carbon::createFromFormat('H:i:s', $attendance->time_in)->format('h:i A');
                    } catch (\Exception $e) {
                        $timeIn = $attendance->time_in; // Use as-is if formatting fails
                    }
                }

                // Handle time_out formatting
                if ($attendance->time_out) {
                    try {
                        $timeOut = \Carbon\Carbon::createFromFormat('H:i:s', $attendance->time_out)->format('h:i A');
                    } catch (\Exception $e) {
                        $timeOut = $attendance->time_out; // Use as-is if formatting fails
                    }
                }
            }

            return response()->json([
                // * The dashboard only updates the Today's Status boxes when success is true
                'success' => true,
                'has_timed_in' => $attendance && $attendance->time_in,
                'has_timed_out' => $attendance && $attendance->time_out,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'total_hours' => $attendance && $attendance->total_hours ? $attendance->total_hours : null,
            ])->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            \Log::error('Today status error: ' . $e->getMessage());
            
            return response()->json([
                'has_timed_in' => false,
                'has_timed_out' => false,
                'time_in' => null,
                'time_out' => null,
                'total_hours' => null,
            ])->header('Content-Type', 'application/json');
        }
    }
}

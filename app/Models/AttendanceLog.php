<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    protected $fillable = [
        'user_id',
        'branch',
        'date',
        'time_in',
        'time_out',
        'break_start',
        'break_end',
        'total_hours',
        'notes',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'total_hours' => 'decimal:2',
    ];

    /**
     * Get the current Philippine time
     */
    protected static function getPhilippineTime()
    {
        try {
            return \Carbon\Carbon::now('Asia/Manila');
        } catch (\Exception $e) {
            // Fallback: Use UTC and add 8 hours for Philippine time if timezone not available
            // This handles servers without timezone data (like some shared hosting)
            \Log::warning('Asia/Manila timezone not available, using UTC+8 offset: ' . $e->getMessage());
            return \Carbon\Carbon::now('UTC')->addHours(8);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calculateTotalHours()
    {
        if ($this->time_in && $this->time_out) {
            $timeIn = strtotime($this->time_in);
            $timeOut = strtotime($this->time_out);
            $breakTime = 0;

            if ($this->break_start && $this->break_end) {
                $breakStart = strtotime($this->break_start);
                $breakEnd = strtotime($this->break_end);
                $breakTime = ($breakEnd - $breakStart) / 3600;
            }

            $totalHours = (($timeOut - $timeIn) / 3600) - $breakTime;
            $this->total_hours = max(0, $totalHours);
            $this->save();
        }
    }

    public static function timeIn($userId, $branch)
    {
        \Log::info('AttendanceLog::timeIn() called', [
            'user_id' => $userId,
            'branch' => $branch,
        ]);

        $philippineTime = self::getPhilippineTime();
        $today = $philippineTime->format('Y-m-d');
        $timeIn = $philippineTime->format('H:i:s');

        \Log::info('AttendanceLog::timeIn() - Date and time calculated', [
            'today' => $today,
            'time_in' => $timeIn,
        ]);

        // Use direct date comparison for better production compatibility
        // Try multiple methods for maximum compatibility
        $existing = null;
        try {
            // Method 1: Direct date comparison (works on all databases)
            \Log::info('AttendanceLog::timeIn() - Trying whereDate method');
            $existing = self::where('user_id', $userId)
                ->whereDate('date', $today)
                ->first();
            \Log::info('AttendanceLog::timeIn() - whereDate query completed', [
                'found_record' => $existing ? true : false,
                'record_id' => $existing ? $existing->id : null,
            ]);
        } catch (\Exception $e) {
            \Log::warning('AttendanceLog::timeIn() - whereDate failed, trying whereRaw', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);
            // Method 2: Fallback using whereRaw with DATE() function
            try {
                \Log::info('AttendanceLog::timeIn() - Trying whereRaw method');
                $existing = self::where('user_id', $userId)
                    ->whereRaw('DATE(date) = ?', [$today])
                    ->first();
                \Log::info('AttendanceLog::timeIn() - whereRaw query completed', [
                    'found_record' => $existing ? true : false,
                ]);
            } catch (\Exception $e2) {
                \Log::warning('AttendanceLog::timeIn() - whereRaw failed, trying direct string', [
                    'error' => $e2->getMessage(),
                    'error_code' => $e2->getCode(),
                ]);
                // Method 3: Last resort - string comparison
                \Log::info('AttendanceLog::timeIn() - Trying direct string comparison');
                try {
                    $existing = self::where('user_id', $userId)
                        ->where('date', $today)
                        ->first();
                    \Log::info('AttendanceLog::timeIn() - Direct string query completed', [
                        'found_record' => $existing ? true : false,
                    ]);
                } catch (\Exception $e3) {
                    \Log::error('AttendanceLog::timeIn() - All date comparison methods failed', [
                        'error' => $e3->getMessage(),
                        'error_code' => $e3->getCode(),
                        'trace' => $e3->getTraceAsString(),
                    ]);
                    throw $e3;
                }
            }
        }

        if ($existing) {
            // Update existing record
            \Log::info('AttendanceLog::timeIn() - Updating existing record', [
                'attendance_id' => $existing->id,
                'current_time_in' => $existing->time_in,
                'new_time_in' => $timeIn,
            ]);

            try {
                $existing->update([
                    'branch' => $branch,
                    'time_in' => $timeIn,
                    'status' => 'present'
                ]);
                \Log::info('AttendanceLog::timeIn() - Record updated successfully', [
                    'attendance_id' => $existing->id,
                ]);
                return $existing;
            } catch (\Exception $e) {
                \Log::error('AttendanceLog::timeIn() - Failed to update existing record', [
                    'attendance_id' => $existing->id,
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }

        // Create new record
        \Log::info('AttendanceLog::timeIn() - Creating new record', [
            'data' => [
                'user_id' => $userId,
                'branch' => $branch,
                'date' => $today,
                'time_in' => $timeIn,
                'status' => 'present'
            ]
        ]);

        try {
            $newRecord = self::create([
                'user_id' => $userId,
                'branch' => $branch,
                'date' => $today,
                'time_in' => $timeIn,
                'status' => 'present'
            ]);
            \Log::info('AttendanceLog::timeIn() - New record created successfully', [
                'attendance_id' => $newRecord->id,
                'time_in' => $newRecord->time_in,
                'date' => $newRecord->date,
            ]);
            return $newRecord;
        } catch (\Exception $e) {
            \Log::error('AttendanceLog::timeIn() - Failed to create new record', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'user_id' => $userId,
                'data' => [
                    'user_id' => $userId,
                    'branch' => $branch,
                    'date' => $today,
                    'time_in' => $timeIn,
                    'status' => 'present'
                ],
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public static function timeOut($userId)
    {
        \Log::info('AttendanceLog::timeOut() called', [
            'user_id' => $userId,
        ]);

        $philippineTime = self::getPhilippineTime();
        $today = $philippineTime->format('Y-m-d');
        $timeOut = $philippineTime->format('H:i:s');

        \Log::info('AttendanceLog::timeOut() - Date and time calculated', [
            'today' => $today,
            'time_out' => $timeOut,
        ]);
        
        // Use direct date comparison for better production compatibility
        // Try multiple methods for maximum compatibility
        $attendance = null;
        try {
            // Method 1: Direct date comparison (works on all databases)
            \Log::info('AttendanceLog::timeOut() - Trying whereDate method');
            $attendance = self::where('user_id', $userId)
                ->whereDate('date', $today)
                ->whereNotNull('time_in')
                ->whereNull('time_out')
                ->first();
            \Log::info('AttendanceLog::timeOut() - whereDate query completed', [
                'found_record' => $attendance ? true : false,
                'record_id' => $attendance ? $attendance->id : null,
            ]);
        } catch (\Exception $e) {
            \Log::warning('AttendanceLog::timeOut() - whereDate failed, trying whereRaw', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);
            // Method 2: Fallback using whereRaw with DATE() function
            try {
                \Log::info('AttendanceLog::timeOut() - Trying whereRaw method');
                $attendance = self::where('user_id', $userId)
                    ->whereRaw('DATE(date) = ?', [$today])
                    ->whereNotNull('time_in')
                    ->whereNull('time_out')
                    ->first();
                \Log::info('AttendanceLog::timeOut() - whereRaw query completed', [
                    'found_record' => $attendance ? true : false,
                ]);
            } catch (\Exception $e2) {
                \Log::warning('AttendanceLog::timeOut() - whereRaw failed, trying direct string', [
                    'error' => $e2->getMessage(),
                    'error_code' => $e2->getCode(),
                ]);
                // Method 3: Last resort - string comparison
                \Log::info('AttendanceLog::timeOut() - Trying direct string comparison');
                try {
                    $attendance = self::where('user_id', $userId)
                        ->where('date', $today)
                        ->whereNotNull('time_in')
                        ->whereNull('time_out')
                        ->first();
                    \Log::info('AttendanceLog::timeOut() - Direct string query completed', [
                        'found_record' => $attendance ? true : false,
                    ]);
                } catch (\Exception $e3) {
                    \Log::error('AttendanceLog::timeOut() - All date comparison methods failed', [
                        'error' => $e3->getMessage(),
                        'error_code' => $e3->getCode(),
                        'trace' => $e3->getTraceAsString(),
                    ]);
                    throw $e3;
                }
            }
        }

        if ($attendance) {
            \Log::info('AttendanceLog::timeOut() - Updating existing record', [
                'attendance_id' => $attendance->id,
                'current_time_out' => $attendance->time_out,
                'new_time_out' => $timeOut,
                'current_time_in' => $attendance->time_in,
            ]);

            try {
                $attendance->time_out = $timeOut;
                
                // Calculate total hours
                if ($attendance->time_in) {
                    try {
                        $timeInCarbon = \Carbon\Carbon::createFromFormat('H:i:s', $attendance->time_in);
                        $timeOutCarbon = \Carbon\Carbon::createFromFormat('H:i:s', $attendance->time_out);
                        
                        // Ensure we calculate correctly - handle case where time_out is earlier (next day)
                        if ($timeOutCarbon->lt($timeInCarbon)) {
                            $timeOutCarbon->addDay();
                        }
                        
                        // Calculate difference in minutes and convert to hours
                        // Use abs() to ensure positive value, and always calculate from time_in to time_out
                        $totalMinutes = abs($timeOutCarbon->diffInMinutes($timeInCarbon));
                        $totalHours = $totalMinutes / 60;
                        
                        // Ensure positive value
                        if ($totalHours < 0) {
                            \Log::warning('AttendanceLog::timeOut() - Negative total_hours calculated, forcing positive', [
                                'time_in' => $attendance->time_in,
                                'time_out' => $attendance->time_out,
                                'calculated_hours' => $totalHours,
                            ]);
                            $totalHours = abs($totalHours);
                        }
                        
                        $attendance->total_hours = round($totalHours, 2);
                        
                        \Log::info('AttendanceLog::timeOut() - Total hours calculated', [
                            'total_hours' => $attendance->total_hours,
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning('AttendanceLog::timeOut() - Failed to calculate total hours', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                \Log::info('AttendanceLog::timeOut() - Saving record', [
                    'data' => [
                        'time_out' => $attendance->time_out,
                        'total_hours' => $attendance->total_hours,
                    ]
                ]);
                
                $attendance->save();
                
                \Log::info('AttendanceLog::timeOut() - Record saved successfully', [
                    'attendance_id' => $attendance->id,
                ]);
                
                return $attendance;
            } catch (\Exception $e) {
                \Log::error('AttendanceLog::timeOut() - Failed to save record', [
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }

        \Log::info('AttendanceLog::timeOut() - No attendance record found for time-out', [
            'user_id' => $userId,
            'date' => $today,
        ]);

        return null;
    }
}

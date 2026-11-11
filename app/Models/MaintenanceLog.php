<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_number',
        'task_id',
        'employee_id',
        'equipment_name',
        'equipment_model',
        'equipment_serial',
        'type',
        'description',
        'issues_found',
        'actions_taken',
        'work_performed',
        'parts_used',
        'materials_used',
        'repair_cost',
        'status',
        'branch',
        'performed_at',
        'started_at',
        'completed_at',
        'next_maintenance_date',
        'hours_worked',
        'photos',
        'videos',
        'notes',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_maintenance_date' => 'date',
        'hours_worked' => 'decimal:2',
        'repair_cost' => 'decimal:2',
        'photos' => 'array',
        'videos' => 'array',
    ];

    // Relationships
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function customerFeedback()
    {
        return $this->hasMany(CustomerFeedback::class);
    }

    // Accessors
    public function getTypeNameAttribute()
    {
        return match($this->type) {
            'repair' => 'Repair',
            'installation' => 'Installation',
            'reinstallation' => 'Re-installation',
            'checkup' => 'Check-up',
            'dismantling' => 'Dismantling',
            'preventive' => 'Preventive Maintenance',
            'maintenance_logs' => 'Maintenance Logs',
            default => $this->type
        };
    }

    public function getStatusNameAttribute()
    {
        return match($this->status) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'requires_followup' => 'Requires Follow-up',
            default => $this->status
        };
    }

    public function getBranchNameAttribute()
    {
        return match($this->branch) {
            'las_pinas' => 'Las Piñas',
            'cebu' => 'Cebu',
            'davao' => 'Davao',
            default => $this->branch
        };
    }

    public function getPhotoUrlsAttribute()
    {
        if (!$this->photos) return [];

        return collect($this->photos)->map(function ($photo) {
            // * Use url() helper to avoid route cache issues
            // * This will work even if routes are cached
            return url('/storage/' . $photo);
        })->toArray();
    }
    
    /**
     * Check if photo file actually exists on disk
     */
    public function photoExists($photoPath)
    {
        return \Storage::disk('public')->exists($photoPath);
    }

    public function getVideoUrlsAttribute()
    {
        if (!$this->videos) return [];

        return collect($this->videos)->map(function ($video) {
            return asset('storage/' . $video);
        })->toArray();
    }

    // Scopes
    public function scopeByBranch($query, $branch)
    {
        return $query->where('branch', $branch);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRequiresFollowup($query)
    {
        return $query->where('status', 'requires_followup');
    }

    // Methods
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function addPhoto($photoPath)
    {
        $photos = $this->photos ?? [];
        $photos[] = $photoPath;
        $this->update(['photos' => $photos]);
    }

    public function addVideo($videoPath)
    {
        $videos = $this->videos ?? [];
        $videos[] = $videoPath;
        $this->update(['videos' => $videos]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            if (!$log->log_number) {
                $log->log_number = static::generateUniqueLogNumber();
            }
            if (!$log->branch && auth()->check()) {
                $log->branch = auth()->user()->branch;
            }
        });
    }

    /**
     * Generate a unique log number
     * Handles race conditions by checking for existing numbers
     */
    protected static function generateUniqueLogNumber()
    {
        $datePrefix = now()->format('Ymd');
        $basePattern = 'LOG-' . $datePrefix . '-';
        
        // Get the highest sequence number for today
        $maxLog = static::where('log_number', 'like', $basePattern . '%')
            ->orderBy('log_number', 'desc')
            ->first();
        
        if ($maxLog) {
            // Extract the sequence number from the existing log
            $lastNumber = (int) substr($maxLog->log_number, -4);
            $sequence = $lastNumber + 1;
        } else {
            // First log for today
            $sequence = 1;
        }
        
        // Generate log number and ensure it's unique (handle race conditions)
        $maxAttempts = 100;
        $attempt = 0;
        
        do {
            $logNumber = $basePattern . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $exists = static::where('log_number', $logNumber)->exists();
            
            if (!$exists) {
                return $logNumber;
            }
            
            // If exists, try next sequence number
            $sequence++;
            $attempt++;
        } while ($attempt < $maxAttempts);
        
        // Fallback: use timestamp if all attempts fail
        return $basePattern . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
    }
}

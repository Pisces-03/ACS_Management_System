<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'requested_by',
        'task_id',
        'branch',
        'materials',
        'category',
        'justification',
        'priority',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'total_estimated_cost',
        'actual_cost',
        'needed_by',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'needed_by' => 'datetime',
        'total_estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'materials' => 'array',
    ];

    // Relationships
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
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

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Accessors
    public function getBranchNameAttribute()
    {
        return match($this->branch) {
            'las_pinas' => 'Las Piñas',
            'cebu' => 'Cebu',
            'davao' => 'Davao',
            default => $this->branch
        };
    }

    public function getStatusNameAttribute()
    {
        return match($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'ordered' => 'Ordered',
            'received' => 'Received',
            default => $this->status
        };
    }

    public function getPriorityNameAttribute()
    {
        return match($this->priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => $this->priority
        };
    }

    public function getTotalItemsAttribute()
    {
        return count($this->materials ?? []);
    }

    public function getTotalQuantityAttribute()
    {
        return collect($this->materials ?? [])->sum('quantity');
    }

    // Methods
    public function approve($approvedBy)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => \Carbon\Carbon::now('Asia/Manila'),
        ]);
    }

    public function reject($approvedBy, $reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approvedBy,
            'approved_at' => \Carbon\Carbon::now('Asia/Manila'),
            'rejection_reason' => $reason,
        ]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            $philippineTime = \Carbon\Carbon::now('Asia/Manila');
            // * Generate request_number if not provided (controller usually provides it)
            if (!$request->request_number) {
                $prefix = 'REQ-' . $philippineTime->format('Ymd') . '-';
                $sequence = static::count() + 1;
                while (static::where('request_number', $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT))->exists()) {
                    $sequence++;
                }
                $request->request_number = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            }
            // * Set branch from authenticated user if not provided
            if (!$request->branch && auth()->check()) {
                $request->branch = auth()->user()->branch;
            }
        });
    }
}

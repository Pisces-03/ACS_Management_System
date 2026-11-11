<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'branch',
        'position',
        'department',
        'phone',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function maintenanceLogs()
    {
        return $this->hasMany(MaintenanceLog::class, 'employee_id');
    }

    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class, 'requested_by');
    }

    public function approvedRequests()
    {
        return $this->hasMany(MaterialRequest::class, 'approved_by');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function customerFeedback()
    {
        return $this->hasMany(CustomerFeedback::class, 'employee_id');
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
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

    // Scopes
    public function scopeByBranch($query, $branch)
    {
        return $query->where('branch', $branch);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isSuperAdmin()
    {
        return $this->role === 'superadmin';
    }

    public function canManageUsers()
    {
        return $this->role === 'superadmin';
    }

    public function canAssignTasks()
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

    public function canApproveRequests()
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

    public function isAdminOfBranch($branch)
    {
        if ($this->role === 'superadmin') return true;
        if ($this->role !== 'admin') return false;
        return $this->branch === $branch;
    }
}

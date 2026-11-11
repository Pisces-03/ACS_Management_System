<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the given employee.
     */
    public function view(User $auth, User $employee)
    {
        if ($auth->isSuperAdmin()) return true;
        if ($auth->id === $employee->id) return true;
        if ($auth->isAdmin()) {
            return $auth->branch === $employee->branch;
        }
        return false;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $auth)
    {
        return $auth->isSuperAdmin() || $auth->isAdmin();
    }

    /**
     * Determine whether the user can update the employee.
     */
    public function update(User $auth, User $employee)
    {
        if ($auth->isSuperAdmin()) return true;
        if ($auth->isAdmin()) {
            // Admins can update users in their branch, but should not elevate to superadmin
            return $auth->branch === $employee->branch;
        }
        // Users can update themselves (limited fields) — allow here, controller enforces specifics
        return $auth->id === $employee->id;
    }

    /**
     * Determine whether the user can delete the employee.
     */
    public function delete(User $auth, User $employee)
    {
        if ($auth->isSuperAdmin()) return true;
        if ($auth->isAdmin()) {
            if ($auth->id === $employee->id) return false; // cannot delete self
            if ($employee->isSuperAdmin()) return false;
            return $auth->branch === $employee->branch;
        }
        return false;
    }

    /**
     * Determine whether the user can request a branch change for the employee.
     */
    public function requestBranchChange(User $auth, User $employee)
    {
        if ($auth->isSuperAdmin()) return true;
        if ($auth->isAdmin()) {
            return $auth->branch === $employee->branch;
        }
        return false;
    }

    /**
     * Determine whether the user can directly change branch (superadmin only).
     */
    public function changeBranch(User $auth)
    {
        return $auth->isSuperAdmin();
    }
}

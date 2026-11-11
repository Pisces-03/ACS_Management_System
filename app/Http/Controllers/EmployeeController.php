<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Rules\StrongPassword;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(\App\Http\Middleware\AdminMiddleware::class);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = User::where('role', '!=', 'superadmin');

        // If admin, only show employees from their branch
        if ($user->role === 'admin') {
            $query->where('branch', $user->branch);
        }

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('branch') && $user->role === 'superadmin') {
            $query->where('branch', $request->branch);
        }

        $statusFilter = $request->input('status');
        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $employees = $query->latest()->paginate(10)->appends($request->all());

        // Calculate statistics - total should always show all employees (no filters applied)
        $baseQuery = User::where('role', '!=', 'superadmin');
        if ($user->role === 'admin') {
            $baseQuery->where('branch', $user->branch);
        }

        // For stats, use unfiltered base query
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'admins' => (clone $baseQuery)->where('role', 'admin')->count(),
            'employees' => (clone $baseQuery)->where('role', 'employee')->count(),
        ];

        return view('employees.index', compact('employees', 'stats'));
    }

    public function create()
    {
        $user = auth()->user();

        $this->authorize('create', User::class);
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $this->authorize('create', User::class);
        $rules = [
            'employee_id' => 'nullable|string|unique:users',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
            'role' => 'required|in:superadmin,admin,employee',
            'branch' => 'required|in:las_pinas,cebu,davao',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|in:INSTALLATION TEAM,REPAIR TEAM,DISMANTLING TEAM,CHECK UP TEAM,OFFICE TEAM',
            'phone' => 'nullable|regex:/^(\+63)?[0-9]{11}$/',
        ];

        $validated = $request->validate($rules);

        // If creator is an admin, force the branch to the admin's branch and force role to 'employee'
        if ($user->role === 'admin') {
            $validated['branch'] = $user->branch;
            $validated['role'] = 'employee';
        }

        // Auto-generate employee_id if not provided
        if (empty($validated['employee_id'])) {
            $rolePrefix = match($validated['role']) {
                'superadmin' => 'SA',
                'admin' => 'AD',
                'employee' => 'EM',
            };
            
            // * Generate employee_id within a transaction with row locking to prevent race conditions
            $validated['employee_id'] = DB::transaction(function () use ($rolePrefix) {
                // * Use lockForUpdate to prevent concurrent requests from generating the same ID
                // * Order by numeric part of employee_id (CAST as UNSIGNED) instead of string ordering
                $lastEmployee = User::where('employee_id', 'like', $rolePrefix . '%')
                    ->orderByRaw('CAST(SUBSTRING(employee_id, 3) AS UNSIGNED) DESC')
                    ->lockForUpdate()
                    ->first();
                
                if ($lastEmployee) {
                    // * Extract numeric part (everything after the 2-character prefix)
                    $lastNumber = (int) substr($lastEmployee->employee_id, 2);
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }
                
                $newEmployeeId = $rolePrefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
                
                // * Double-check the ID doesn't exist (in case of edge cases)
                $exists = User::where('employee_id', $newEmployeeId)->exists();
                if ($exists) {
                    // * If it exists, find the next available number
                    $maxNumber = User::where('employee_id', 'like', $rolePrefix . '%')
                        ->selectRaw('MAX(CAST(SUBSTRING(employee_id, 3) AS UNSIGNED)) as max_num')
                        ->value('max_num') ?? 0;
                    $newNumber = $maxNumber + 1;
                    $newEmployeeId = $rolePrefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
                }
                
                return $newEmployeeId;
            });
        }

        // * Wrap user creation in try-catch to handle any remaining race conditions
        try {
            User::create([
                'employee_id' => $validated['employee_id'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'branch' => $validated['branch'],
                'position' => $validated['position'] ?? null,
                'department' => $validated['department'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // * Handle duplicate employee_id error (race condition fallback)
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'employee_id')) {
                Log::warning('Duplicate employee_id detected, regenerating', [
                    'attempted_id' => $validated['employee_id'],
                    'role' => $validated['role']
                ]);
                
                // * Regenerate employee_id and retry once
                $rolePrefix = match($validated['role']) {
                    'superadmin' => 'SA',
                    'admin' => 'AD',
                    'employee' => 'EM',
                };
                
                $validated['employee_id'] = DB::transaction(function () use ($rolePrefix) {
                    $maxNumber = User::where('employee_id', 'like', $rolePrefix . '%')
                        ->selectRaw('MAX(CAST(SUBSTRING(employee_id, 3) AS UNSIGNED)) as max_num')
                        ->value('max_num') ?? 0;
                    $newNumber = $maxNumber + 1;
                    return $rolePrefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
                });
                
                // * Retry creation with new ID
                User::create([
                    'employee_id' => $validated['employee_id'],
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => $validated['role'],
                    'branch' => $validated['branch'],
                    'position' => $validated['position'] ?? null,
                    'department' => $validated['department'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                ]);
            } else {
                // * Re-throw if it's a different error
                throw $e;
            }
        }

        return redirect()->route('employees.index')
            ->with('success', 'Employee created successfully');
    }

    public function show(User $employee)
    {
        $user = auth()->user();
        $this->authorize('view', $employee);
        return view('employees.show', compact('employee'));
    }

    public function edit(User $employee)
    {
        $user = auth()->user();
        $this->authorize('update', $employee);
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, User $employee)
    {
        $user = auth()->user();
        $this->authorize('update', $employee);
        
        // Dynamic validation based on user role
        $roleValidation = $user->role === 'superadmin' 
            ? 'required|in:superadmin,admin,employee'
            : 'required|in:employee';
        
        $validated = $request->validate([
            'employee_id' => 'nullable|string|unique:users,employee_id,' . $employee->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $employee->id,
            'role' => $roleValidation,
            'branch' => 'required|in:las_pinas,cebu,davao',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|in:INSTALLATION TEAM,REPAIR TEAM,DISMANTLING TEAM,CHECK UP TEAM,OFFICE TEAM',
            'phone' => 'nullable|regex:/^(\+63)?[0-9]{11}$/',
            'password' => ['nullable', 'string', 'confirmed', new StrongPassword],
        ]);

        // If updater is admin, prevent changing role/branch: force branch to admin's branch and keep existing role
        if ($user->role === 'admin') {
            $validated['branch'] = $user->branch;
            $validated['role'] = $employee->role;
        }

        // Auto-regenerate employee_id if role changed or ID is empty
        if (empty($validated['employee_id']) || $validated['role'] !== $employee->role) {
            $rolePrefix = match($validated['role']) {
                'superadmin' => 'SA',
                'admin' => 'AD',
                'employee' => 'EM',
            };
            
            // * Generate employee_id within a transaction with row locking to prevent race conditions
            $validated['employee_id'] = DB::transaction(function () use ($rolePrefix, $employee) {
                // * Use lockForUpdate to prevent concurrent requests from generating the same ID
                // * Order by numeric part of employee_id (CAST as UNSIGNED) instead of string ordering
                $lastEmployee = User::where('employee_id', 'like', $rolePrefix . '%')
                    ->where('id', '!=', $employee->id) // * Exclude current employee
                    ->orderByRaw('CAST(SUBSTRING(employee_id, 3) AS UNSIGNED) DESC')
                    ->lockForUpdate()
                    ->first();
                
                if ($lastEmployee) {
                    // * Extract numeric part (everything after the 2-character prefix)
                    $lastNumber = (int) substr($lastEmployee->employee_id, 2);
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }
                
                $newEmployeeId = $rolePrefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
                
                // * Double-check the ID doesn't exist (in case of edge cases)
                $exists = User::where('employee_id', $newEmployeeId)
                    ->where('id', '!=', $employee->id)
                    ->exists();
                if ($exists) {
                    // * If it exists, find the next available number
                    $maxNumber = User::where('employee_id', 'like', $rolePrefix . '%')
                        ->where('id', '!=', $employee->id)
                        ->selectRaw('MAX(CAST(SUBSTRING(employee_id, 3) AS UNSIGNED)) as max_num')
                        ->value('max_num') ?? 0;
                    $newNumber = $maxNumber + 1;
                    $newEmployeeId = $rolePrefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
                }
                
                return $newEmployeeId;
            });
        }

        $updateData = [
            'employee_id' => $validated['employee_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'branch' => $validated['branch'],
            'position' => $validated['position'],
            'department' => $validated['department'] ?? null,
            'phone' => $validated['phone'],
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $employee->update($updateData);

        return redirect()->route('employees.index')
            ->with('success', 'Employee updated successfully');
    }

    public function destroy(Request $request, User $employee)
    {
        // Use policy to authorize deletion (superadmin or branch-scoped admin)
        $this->authorize('delete', $employee);

        try {
            // Check if employee has related records that might prevent deletion
            $hasTasks = $employee->assignedTasks()->exists() || $employee->createdTasks()->exists();
            $hasMaintenanceLogs = $employee->maintenanceLogs()->exists();
            $hasMaterialRequests = $employee->materialRequests()->exists();
            
            if ($hasTasks || $hasMaintenanceLogs || $hasMaterialRequests) {
                // If using soft deletes, just mark as inactive instead
                $employee->update(['is_active' => false]);
                $message = 'Employee deactivated successfully. Related records prevent deletion.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'data' => [
                            'action' => 'deactivated'
                        ]
                    ]);
                }

                return redirect()->route('employees.index')
                    ->with('success', $message);
            }

            $employee->delete();
            $message = 'Employee deleted successfully';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'action' => 'deleted'
                    ]
                ]);
            }

            return redirect()->route('employees.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            // If deletion fails due to foreign key constraints, deactivate instead
            if (str_contains($e->getMessage(), 'foreign key constraint')) {
                $employee->update(['is_active' => false]);
                $message = 'Employee deactivated successfully. Cannot delete due to related records.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'data' => [
                            'action' => 'deactivated'
                        ]
                    ]);
                }

                return redirect()->route('employees.index')
                    ->with('success', $message);
            }
            
            $errorMessage = 'Error deleting employee: ' . $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return redirect()->route('employees.index')
                ->with('error', $errorMessage);
        }
    }

    public function updateStatus(Request $request, User $employee)
    {
        // Authorize update action (admins allowed for same branch via policy)
        $this->authorize('update', $employee);

        $user = auth()->user();
        if ($user->isAdmin() && $employee->branch !== $user->branch) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $employee->update([
            'is_active' => $request->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee status updated successfully'
        ]);
    }

    public function updateBranch(Request $request, User $employee)
    {
        $validated = $request->validate([
            'branch' => 'required|in:las_pinas,cebu,davao',
            'reason' => 'nullable|string|max:500'
        ]);

        $user = auth()->user();

        // * Only the superadmin can move an employee to another branch
        if ($user->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Only the superadmin can change an employee\'s branch.'], 403);
        }

        // If superadmin, perform the change directly
        if ($user->role === 'superadmin') {
            $oldBranch = $employee->branch;
            $newBranch = $validated['branch'];

            $employee->update([
                'branch' => $newBranch
            ]);

            \App\Models\AuditLog::log(
                'branch_changed',
                "Branch changed for user {$employee->id} from {$oldBranch} to {$newBranch}",
                $employee,
                ['old_branch' => $oldBranch],
                ['new_branch' => $newBranch]
            );

            return response()->json([
                'success' => true,
                'message' => 'Employee branch updated successfully',
                'data' => [
                    'old_branch' => $oldBranch,
                    'new_branch' => $newBranch,
                    'reason' => $validated['reason'] ?? null
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    public function resetPassword(Request $request, User $employee)
    {
        // * Authorize reset password action (admins allowed for same branch via policy)
        $this->authorize('update', $employee);

        $user = auth()->user();
        if ($user->isAdmin() && $employee->branch !== $user->branch) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // * Generate a random temporary password
        $temporaryPassword = \Illuminate\Support\Str::random(12);

        // * Update the employee's password
        $employee->update([
            'password' => Hash::make($temporaryPassword)
        ]);

        // * Log the password reset action
        \App\Models\AuditLog::log(
            'password_reset',
            "Password reset for user {$employee->id} by {$user->id}",
            $employee,
            [],
            ['reset_by' => $user->id, 'reset_at' => now()]
        );

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. Please share the new password securely with the employee.',
            'password' => $temporaryPassword
        ]);
    }
}

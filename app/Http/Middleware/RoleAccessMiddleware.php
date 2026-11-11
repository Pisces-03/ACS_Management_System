<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $route = $request->route() ? $request->route()->getName() : 'unknown';
        
        // Define restricted routes based on roles
        $restrictedRoutes = [
            'employee' => [
                // Employee cannot access admin/superadmin routes
                'employees.index', 'employees.create', 'employees.store', 'employees.show', 
                'employees.edit', 'employees.update', 'employees.destroy',
                // * Employees only update the status of their own tasks (tasks.status)
                'tasks.create', 'tasks.store', 'tasks.edit', 'tasks.update', 'tasks.assign',
                'tasks.cancel', 'tasks.destroy', 'tasks.destroy.post',
                'audit-logs.index', 'audit-logs.show', 'audit-logs.search', 'audit-logs.export'
            ],
            'admin' => [
                // Admin cannot access superadmin routes
                'audit-logs.export'
            ]
        ];

        // Check if current user's role has restrictions for this route
        if (isset($restrictedRoutes[$user->role]) && in_array($route, $restrictedRoutes[$user->role])) {
            abort(403, 'Access denied. You do not have permission to access this page.');
        }

        return $next($request);
    }
}

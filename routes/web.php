<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PreventiveMaintenanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

// * Serve uploaded files from storage/app/public when the public/storage symlink is missing
// * (common on shared hosting). BinaryFileResponse supports range requests, so videos can seek.
Route::get('/storage/{path}', function ($path) {
    $root = realpath(storage_path('app/public'));
    $fullPath = realpath($root . DIRECTORY_SEPARATOR . $path);

    // * Reject directory traversal and missing files
    if (!$fullPath || !str_starts_with($fullPath, $root) || !is_file($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath, ['Cache-Control' => 'public, max-age=31536000']);
})->where('path', '.*')->name('storage.serve');

// Public routes
Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Two-Factor Authentication routes
Route::get('/2fa', [AuthController::class, 'showTwoFactorChallenge'])->name('2fa.challenge');
Route::post('/2fa', [AuthController::class, 'verifyTwoFactor'])->name('2fa.verify');
Route::post('/2fa/resend', [AuthController::class, 'resendTwoFactor'])->name('2fa.resend');


// Protected routes
Route::middleware([\App\Http\Middleware\Authenticate::class, '2fa', \App\Http\Middleware\RoleAccessMiddleware::class])->group(function () {
    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Routes
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');

    // Attendance Routes
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/time-in', [AttendanceController::class, 'timeIn'])->name('attendance.time-in');
    Route::post('/attendance/time-out', [AttendanceController::class, 'timeOut'])->name('attendance.time-out');
    Route::get('/attendance/today-status', [AttendanceController::class, 'todayStatus'])->name('attendance.today-status');
    // * The old monthly attendance page now lives in Reports & Analytics
    Route::get('/attendance/report', fn () => redirect()->route('reports.index', ['report_type' => 'attendance']))->name('attendance.report');

    // Task Management Routes
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/{task}/delete', [TaskController::class, 'destroy'])->name('tasks.destroy.post');
    Route::put('/tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');
    Route::put('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::post('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status.post');
    Route::post('/tasks/{task}/cancel', [TaskController::class, 'cancel'])->name('tasks.cancel');
    Route::get('/tasks/{task}/maintenance-schedule', [TaskController::class, 'getMaintenanceSchedule'])->name('tasks.maintenance-schedule.show');
    Route::post('/tasks/{task}/maintenance-schedule', [TaskController::class, 'storeMaintenanceSchedule'])->name('tasks.maintenance-schedule.store');
    Route::put('/tasks/{task}/maintenance-schedule', [TaskController::class, 'updateMaintenanceSchedule'])->name('tasks.maintenance-schedule.update');
    Route::post('/tasks/{task}/maintenance-schedule/update', [TaskController::class, 'updateMaintenanceSchedule'])->name('tasks.maintenance-schedule.update.post');
    Route::post('/tasks/{task}/maintenance-schedule/complete', [TaskController::class, 'completeMaintenance'])->name('tasks.maintenance-schedule.complete');
    Route::delete('/tasks/{task}/maintenance-schedule', [TaskController::class, 'deleteMaintenanceSchedule'])->name('tasks.maintenance-schedule.delete');
    Route::post('/tasks/{task}/maintenance-schedule/delete', [TaskController::class, 'deleteMaintenanceSchedule'])->name('tasks.maintenance-schedule.delete.post');

    // Maintenance Management Routes
    Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('/maintenance/create', [MaintenanceController::class, 'create'])->name('maintenance.create');
    Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
    Route::get('/maintenance/{maintenance}', [MaintenanceController::class, 'show'])->name('maintenance.show');
    Route::get('/maintenance/{maintenance}/edit', [MaintenanceController::class, 'edit'])->name('maintenance.edit');
    Route::put('/maintenance/{maintenance}', [MaintenanceController::class, 'update'])->name('maintenance.update');
    Route::delete('/maintenance/{maintenance}', [MaintenanceController::class, 'destroy'])->name('maintenance.destroy');

    // Preventive Maintenance (automated scheduling) Routes
    Route::get('/preventive-maintenance', [PreventiveMaintenanceController::class, 'index'])->name('preventive-maintenance.index');
    Route::post('/preventive-maintenance/generate-tasks', [PreventiveMaintenanceController::class, 'generateTasks'])->name('preventive-maintenance.generate-tasks');

    // Material Request Routes
    Route::get('/material-requests', [MaterialRequestController::class, 'index'])->name('material-requests.index');
    Route::get('/material-requests/create', [MaterialRequestController::class, 'create'])->name('material-requests.create');
    Route::post('/material-requests', [MaterialRequestController::class, 'store'])->name('material-requests.store');
    Route::get('/material-requests/{materialRequest}', [MaterialRequestController::class, 'show'])->name('material-requests.show');
    // * Material request approval routes - accessible to admins and superadmins
    // * Using POST instead of PUT as workaround (some servers block PUT)
    Route::post('/material-requests/{id}/approve', [MaterialRequestController::class, 'approve'])
        ->name('material-requests.approve')
        ->where('id', '[0-9]+');
    
    // * Keep PUT route as fallback
    Route::post('/material-requests/{id}/reject', [MaterialRequestController::class, 'reject'])
        ->name('material-requests.reject')
        ->where('id', '[0-9]+');
    Route::put('/material-requests/{materialRequest}/status', [MaterialRequestController::class, 'updateStatus'])->name('material-requests.status');
    Route::post('/material-requests/{materialRequest}/status', [MaterialRequestController::class, 'updateStatus'])->name('material-requests.status.post');
    
    // * Pending Approvals Routes (for admins/superadmins)
    Route::get('/pending-approvals', [MaterialRequestController::class, 'pendingApprovals'])->name('pending-approvals.index');
    

    // Admin/Superadmin only routes
    Route::middleware([\App\Http\Middleware\AdminMiddleware::class])->group(function () {
        // Employee Management Routes
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::patch('/employees/{employee}/status', [EmployeeController::class, 'updateStatus'])->name('employees.update-status');
        Route::post('/employees/{employee}/status', [EmployeeController::class, 'updateStatus']);
        Route::post('/employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])->name('employees.reset-password');
        Route::patch('/employees/{employee}/branch', [EmployeeController::class, 'updateBranch'])->name('employees.update-branch');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::post('/employees/{employee}/delete', [EmployeeController::class, 'destroy'])
            ->name('employees.destroy.post');

        // Audit Logs Routes
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show')
            ->where('auditLog', '[0-9]+'); // * keep /audit-logs/export from matching here
        Route::get('/audit-logs-search', [AuditLogController::class, 'search'])->name('audit-logs.search');
    });

    // Superadmin only routes
    Route::middleware([\App\Http\Middleware\SuperAdminMiddleware::class])->group(function () {
        // Export Audit Logs
        Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
    });

    // Reports Routes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/maintenance', [ReportController::class, 'maintenance'])->name('reports.maintenance');
    Route::get('/reports/performance', [ReportController::class, 'performance'])->name('reports.performance');
    Route::get('/reports/materials', [ReportController::class, 'materials'])->name('reports.materials');
    Route::get('/reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Customer Feedback (Employee submit; Admin/Superadmin report)
    Route::get('/feedback/create', [\App\Http\Controllers\CustomerFeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/feedback', [\App\Http\Controllers\CustomerFeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/feedback/my', [\App\Http\Controllers\CustomerFeedbackController::class, 'myIndex'])->name('feedback.my');

    // Customer Feedback Reports
    Route::get('/reports/customer-feedback', [\App\Http\Controllers\CustomerFeedbackReportController::class, 'index'])->name('reports.customer-feedback');
    Route::get('/reports/customer-feedback/export', [\App\Http\Controllers\CustomerFeedbackReportController::class, 'export'])->name('reports.customer-feedback.export');

    // Notification Routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/notifications/widget', [NotificationController::class, 'widget'])->name('notifications.widget');
});

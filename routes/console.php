<?php

use Illuminate\Support\Facades\Schedule;

// Schedule preventive maintenance task generation
Schedule::command('maintenance:generate-tasks')
    ->dailyAt('06:00')
    ->timezone('Asia/Manila')
    ->description('Generate preventive maintenance tasks for due equipment')
    ->emailOutputOnFailure(['admin@coldstorageacs.com']);

// Schedule overdue task checking with notifications
Schedule::command('tasks:check-overdue --send-notifications')
    ->dailyAt('09:00')
    ->timezone('Asia/Manila')
    ->description('Check for overdue tasks and send notifications');

// Schedule task maintenance check with notifications
Schedule::command('tasks:check-maintenance')
    ->dailyAt('08:00')
    ->timezone('Asia/Manila')
    ->description('Check for upcoming task maintenance and send notifications');

// Schedule weekly summary report
Schedule::command('maintenance:generate-tasks --dry-run')
    ->weeklyOn(1, '08:00') // Every Monday at 8 AM
    ->timezone('Asia/Manila')
    ->description('Weekly maintenance schedule preview');

// Clean up old audit logs (optional - keep 6 months)
Schedule::command('model:prune', [
    '--model' => 'App\\Models\\AuditLog',
])
    ->daily()
    ->when(app()->environment('production'));

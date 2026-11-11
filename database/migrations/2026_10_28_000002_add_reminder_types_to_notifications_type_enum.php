<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * * NotificationService sends maintenance reminders and branch change requests,
     * * but those types were never added to the enum, so inserting them failed.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('info', 'warning', 'success', 'error', 'task_assigned', 'task_overdue', 'approval_needed', 'maintenance_due', 'maintenance_findings_submitted', 'task_maintenance_advance', 'task_maintenance_due', 'branch_change_request') NOT NULL DEFAULT 'info'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('info', 'warning', 'success', 'error', 'task_assigned', 'task_overdue', 'approval_needed', 'maintenance_due', 'maintenance_findings_submitted') NOT NULL DEFAULT 'info'");
    }
};

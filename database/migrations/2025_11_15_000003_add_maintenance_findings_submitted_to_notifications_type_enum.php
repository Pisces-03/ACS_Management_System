<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'maintenance_findings_submitted' to the type enum
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('info', 'warning', 'success', 'error', 'task_assigned', 'task_overdue', 'approval_needed', 'maintenance_due', 'maintenance_findings_submitted') NOT NULL DEFAULT 'info'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'maintenance_findings_submitted' from the type enum
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('info', 'warning', 'success', 'error', 'task_assigned', 'task_overdue', 'approval_needed', 'maintenance_due') NOT NULL DEFAULT 'info'");
    }
};


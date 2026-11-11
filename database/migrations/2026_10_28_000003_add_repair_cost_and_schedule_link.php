<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // * Repair expense recorded by the team leader on each maintenance log
        if (!Schema::hasColumn('maintenance_logs', 'repair_cost')) {
            Schema::table('maintenance_logs', function (Blueprint $table) {
                $table->decimal('repair_cost', 10, 2)->nullable()->after('materials_used');
            });
        }

        // * Links an auto-generated preventive task to the schedule that produced it
        if (!Schema::hasColumn('tasks', 'maintenance_schedule_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->foreignId('maintenance_schedule_id')->nullable()->after('assigned_employees')
                    ->constrained('task_maintenance_schedules')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'maintenance_schedule_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropConstrainedForeignId('maintenance_schedule_id');
            });
        }

        if (Schema::hasColumn('maintenance_logs', 'repair_cost')) {
            Schema::table('maintenance_logs', function (Blueprint $table) {
                $table->dropColumn('repair_cost');
            });
        }
    }
};

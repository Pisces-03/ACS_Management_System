<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // * The attendance_logs create migration already has this column on fresh installs
        if (Schema::hasColumn('attendance_logs', 'total_hours')) {
            return;
        }

        Schema::table('attendance_logs', function (Blueprint $table) {
            // Check if break_end exists, otherwise add after time_out
            if (Schema::hasColumn('attendance_logs', 'break_end')) {
                $table->decimal('total_hours', 4, 2)->nullable()->after('break_end');
            } elseif (Schema::hasColumn('attendance_logs', 'time_out')) {
                $table->decimal('total_hours', 4, 2)->nullable()->after('time_out');
            } else {
                // Fallback: just add the column
                $table->decimal('total_hours', 4, 2)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropColumn('total_hours');
        });
    }
};

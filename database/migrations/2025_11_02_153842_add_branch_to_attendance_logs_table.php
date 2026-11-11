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
        Schema::table('attendance_logs', function (Blueprint $table) {
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('attendance_logs', 'branch')) {
                $table->enum('branch', ['las_pinas', 'cebu', 'davao'])->nullable()->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_logs', 'branch')) {
                $table->dropColumn('branch');
            }
        });
    }
};
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
        // Add 'maintenance_logs' to the type enum
        DB::statement("ALTER TABLE maintenance_logs MODIFY COLUMN type ENUM('installation', 'reinstallation', 'repair', 'dismantling', 'checkup', 'maintenance_logs') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'maintenance_logs' from the type enum
        DB::statement("ALTER TABLE maintenance_logs MODIFY COLUMN type ENUM('installation', 'reinstallation', 'repair', 'dismantling', 'checkup') NOT NULL");
    }
};


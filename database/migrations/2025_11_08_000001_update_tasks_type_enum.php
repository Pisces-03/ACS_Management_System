<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update tasks table type enum to include 'reinstallation' and remove 'preventive'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `tasks` MODIFY COLUMN `type` ENUM('installation', 'reinstallation', 'repair', 'dismantling', 'checkup') NOT NULL");
        } else {
            // For SQLite or other databases, we'd need to recreate the table
            // This is a simplified version - you may need to handle data migration
            Schema::table('tasks', function (Blueprint $table) {
                $table->string('type')->change();
            });
            
            // Update existing 'preventive' to 'checkup' or another appropriate value
            DB::table('tasks')->where('type', 'preventive')->update(['type' => 'checkup']);
            
            Schema::table('tasks', function (Blueprint $table) {
                $table->enum('type', ['installation', 'reinstallation', 'repair', 'dismantling', 'checkup'])->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Revert to original enum values
            DB::statement("ALTER TABLE `tasks` MODIFY COLUMN `type` ENUM('repair', 'installation', 'checkup', 'dismantling', 'preventive') NOT NULL");
            
            // Map 'reinstallation' back to 'installation' for rollback
            DB::table('tasks')->where('type', 'reinstallation')->update(['type' => 'installation']);
        } else {
            Schema::table('tasks', function (Blueprint $table) {
                $table->string('type')->change();
            });
            
            DB::table('tasks')->where('type', 'reinstallation')->update(['type' => 'installation']);
            
            Schema::table('tasks', function (Blueprint $table) {
                $table->enum('type', ['repair', 'installation', 'checkup', 'dismantling', 'preventive'])->change();
            });
        }
    }
};


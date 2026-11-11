<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * * Update notifications table to match the custom structure used by the application
     * * Recreates the table with correct structure (auto-incrementing id instead of CHAR(36))
     * * Adds user_id, title, message, type, priority, branch, is_read columns
     */
    public function up()
    {
        // * Drop and recreate the table to fix the id column type
        // * This is necessary because the table was created with CHAR(36) id (Laravel's default notifications)
        // * but the application needs BIGINT UNSIGNED AUTO_INCREMENT
        Schema::dropIfExists('notifications');
        
        Schema::create('notifications', function (Blueprint $table) {
            $table->id(); // * Creates BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->enum('type', ['info', 'warning', 'success', 'error', 'task_assigned', 'task_overdue', 'approval_needed', 'maintenance_due'])->default('info');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('branch', ['las_pinas', 'cebu', 'davao'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * * Recreates the original Laravel notifications table structure
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
        
        // * Recreate with Laravel's default notifications structure
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_number')->unique();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('set null');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['repair', 'installation', 'checkup', 'dismantling', 'preventive']);
            $table->string('equipment_name');
            $table->string('equipment_model')->nullable();
            $table->string('equipment_serial')->nullable();
            $table->text('description');
            $table->text('work_performed');
            $table->text('materials_used')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'requires_followup']);
            $table->enum('branch', ['las_pinas', 'cebu', 'davao']);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->decimal('hours_worked', 5, 2)->nullable();
            $table->json('photos')->nullable(); // Store photo file paths
            $table->json('videos')->nullable(); // Store video file paths
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('maintenance_logs');
    }
};

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
        // For SQLite, we need to recreate the table with the new enum values
        if (DB::getDriverName() === 'sqlite') {
            // Create a new table with the updated schema
            Schema::create('maintenance_logs_new', function (Blueprint $table) {
                $table->id();
                $table->string('log_number')->unique();
                $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('set null');
                $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
                $table->enum('type', ['installation', 'reinstallation', 'repair', 'dismantling', 'checkup']);
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
                $table->json('photos')->nullable();
                $table->json('videos')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            // Copy data from old table to new table, mapping old values to new ones
            $mapping = [
                'repair' => 'repair',
                'installation' => 'installation',
                'checkup' => 'checkup',
                'dismantling' => 'dismantling',
                'preventive' => 'installation' // Map preventive to installation
            ];

            $oldData = DB::table('maintenance_logs')->get();
            foreach ($oldData as $row) {
                $newType = $mapping[$row->type] ?? 'repair';
                DB::table('maintenance_logs_new')->insert([
                    'id' => $row->id,
                    'log_number' => $row->log_number,
                    'task_id' => $row->task_id,
                    'employee_id' => $row->employee_id,
                    'type' => $newType,
                    'equipment_name' => $row->equipment_name,
                    'equipment_model' => $row->equipment_model,
                    'equipment_serial' => $row->equipment_serial,
                    'description' => $row->description,
                    'work_performed' => $row->work_performed,
                    'materials_used' => $row->materials_used,
                    'status' => $row->status,
                    'branch' => $row->branch,
                    'started_at' => $row->started_at,
                    'completed_at' => $row->completed_at,
                    'hours_worked' => $row->hours_worked,
                    'photos' => $row->photos,
                    'videos' => $row->videos,
                    'notes' => $row->notes,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }

            // Drop old table and rename new table
            Schema::dropIfExists('maintenance_logs');
            Schema::rename('maintenance_logs_new', 'maintenance_logs');
        } else {
            // For MySQL/PostgreSQL, use MODIFY COLUMN
            DB::statement("ALTER TABLE maintenance_logs MODIFY COLUMN type ENUM('installation', 'reinstallation', 'repair', 'dismantling', 'checkup') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Recreate table with original enum values
            Schema::create('maintenance_logs_old', function (Blueprint $table) {
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
                $table->json('photos')->nullable();
                $table->json('videos')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            // Copy data back, mapping new values to old ones
            $mapping = [
                'installation' => 'installation',
                'reinstallation' => 'installation', // Map reinstallation to installation
                'repair' => 'repair',
                'dismantling' => 'dismantling',
                'checkup' => 'checkup'
            ];

            $newData = DB::table('maintenance_logs')->get();
            foreach ($newData as $row) {
                $oldType = $mapping[$row->type] ?? 'repair';
                DB::table('maintenance_logs_old')->insert([
                    'id' => $row->id,
                    'log_number' => $row->log_number,
                    'task_id' => $row->task_id,
                    'employee_id' => $row->employee_id,
                    'type' => $oldType,
                    'equipment_name' => $row->equipment_name,
                    'equipment_model' => $row->equipment_model,
                    'equipment_serial' => $row->equipment_serial,
                    'description' => $row->description,
                    'work_performed' => $row->work_performed,
                    'materials_used' => $row->materials_used,
                    'status' => $row->status,
                    'branch' => $row->branch,
                    'started_at' => $row->started_at,
                    'completed_at' => $row->completed_at,
                    'hours_worked' => $row->hours_worked,
                    'photos' => $row->photos,
                    'videos' => $row->videos,
                    'notes' => $row->notes,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }

            Schema::dropIfExists('maintenance_logs');
            Schema::rename('maintenance_logs_old', 'maintenance_logs');
        } else {
            DB::statement("ALTER TABLE maintenance_logs MODIFY COLUMN type ENUM('repair', 'installation', 'checkup', 'dismantling', 'preventive') NOT NULL");
        }
    }
};

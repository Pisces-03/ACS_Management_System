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
        Schema::create('task_maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->enum('frequency_type', ['days', 'weeks', 'months', 'years']);
            $table->integer('frequency_value')->comment('e.g., 2 for "every 2 months"');
            $table->integer('advance_notice_days')->default(10)->comment('Days before maintenance to send advance notification (default 10)');
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_due_date');
            $table->dateTime('advance_notification_sent_at')->nullable()->comment('Track when advance notification was sent');
            $table->dateTime('due_notification_sent_at')->nullable()->comment('Track when due date notification was sent');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('task_id');
            $table->index('next_due_date');
            $table->index('is_active');
            $table->unique('task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_maintenance_schedules');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['info', 'warning', 'success', 'error', 'task_assigned', 'task_overdue', 'approval_needed', 'maintenance_due']);
            $table->json('data')->nullable(); // Additional data for the notification
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('branch', ['las_pinas', 'cebu', 'davao'])->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};

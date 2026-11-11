<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('branch', ['las_pinas', 'cebu', 'davao']);
            $table->date('date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->decimal('total_hours', 4, 2)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'halfday'])->default('present');
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_logs');
    }
};

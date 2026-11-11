<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('set null');
            $table->enum('branch', ['las_pinas', 'cebu', 'davao']);
            $table->json('materials'); // [{name, quantity, unit, estimated_cost, description}]
            $table->text('justification');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'approved', 'rejected', 'ordered', 'received'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->decimal('total_estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->timestamp('needed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('material_requests');
    }
};

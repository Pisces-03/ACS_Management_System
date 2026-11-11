<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('cascade');
            $table->foreignId('maintenance_log_id')->nullable()->constrained('maintenance_logs')->onDelete('set null');
            $table->string('customer_name');
            $table->string('customer_contact')->nullable();
            $table->integer('rating')->unsigned(); // 1-5 rating
            $table->text('feedback')->nullable();
            $table->json('service_aspects')->nullable(); // {quality, timeliness, professionalism, communication}
            $table->boolean('would_recommend')->default(true);
            $table->text('suggestions')->nullable();
            $table->enum('branch', ['las_pinas', 'cebu', 'davao']);
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_feedback');
    }
};

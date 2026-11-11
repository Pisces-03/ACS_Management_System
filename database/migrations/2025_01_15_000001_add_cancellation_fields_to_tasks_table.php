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
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('completion_notes')->comment('Reason why the task was cancelled');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason')->comment('Timestamp when the task was cancelled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['cancellation_reason', 'cancelled_at']);
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tasks now start as Pending and move to In Progress when the employee starts them,
     * so existing "assigned" tasks become "pending".
     */
    public function up(): void
    {
        DB::table('tasks')->where('status', 'assigned')->update(['status' => 'pending']);
    }

    public function down(): void
    {
        // * Nothing to restore: "assigned" and "pending" meant the same thing (not started yet)
    }
};

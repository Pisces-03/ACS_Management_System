<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * * Tasks created before this fix stored assigned_employees as a JSON string inside JSON.
     * * Rewrite those rows as a plain JSON array of integer user IDs.
     */
    public function up(): void
    {
        DB::table('tasks')->whereNotNull('assigned_employees')->orderBy('id')->each(function ($task) {
            $ids = json_decode($task->assigned_employees, true);
            if (is_string($ids)) {
                $ids = json_decode($ids, true);
            }
            if (!is_array($ids)) {
                return;
            }

            DB::table('tasks')->where('id', $task->id)->update([
                'assigned_employees' => json_encode(array_map('intval', $ids)),
            ]);
        });
    }

    public function down(): void
    {
        //
    }
};

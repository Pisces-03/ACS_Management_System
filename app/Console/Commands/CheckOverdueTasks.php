<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\Notification;
use App\Services\NotificationService;
use Carbon\Carbon;

class CheckOverdueTasks extends Command
{
    protected $signature = 'tasks:check-overdue
                            {--send-notifications : Notify the assigned employee about each overdue task}';

    protected $description = 'List overdue tasks and optionally notify the assigned employees';

    public function handle()
    {
        $tasks = Task::with('assignedEmployee')->overdue()->get();

        if ($tasks->isEmpty()) {
            $this->info('No overdue tasks.');
            return Command::SUCCESS;
        }

        $this->table(
            ['Task', 'Title', 'Assigned To', 'Due Date'],
            $tasks->map(fn ($task) => [
                $task->task_number,
                $task->title,
                $task->assignedEmployee?->full_name ?? 'Unassigned',
                $task->due_date?->format('M d, Y'),
            ])
        );

        if (!$this->option('send-notifications')) {
            return Command::SUCCESS;
        }

        $today = Carbon::now('Asia/Manila')->toDateString();
        $sent = 0;

        foreach ($tasks as $task) {
            // * Only one overdue reminder per task per day
            $alreadySent = Notification::where('type', 'task_overdue')
                ->whereDate('created_at', $today)
                ->where('data->task_id', $task->id)
                ->exists();

            if (!$alreadySent && NotificationService::taskOverdue($task)) {
                $sent++;
            }
        }

        $this->info("Sent {$sent} overdue notification(s).");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TaskMaintenanceSchedule;
use App\Services\NotificationService;
use Carbon\Carbon;

class CheckTaskMaintenance extends Command
{
    protected $signature = 'tasks:check-maintenance';

    protected $description = 'Send advance and due-date reminders for task maintenance schedules';

    public function handle()
    {
        $schedules = TaskMaintenanceSchedule::with(['task.creator', 'task.assignedEmployee'])
            ->where('is_active', true)
            ->whereNotNull('next_due_date')
            ->get();

        $advanceSent = 0;
        $dueSent = 0;

        foreach ($schedules as $schedule) {
            if (!$schedule->task) {
                continue;
            }

            // * Due-date reminder takes priority over the advance notice
            if ($schedule->shouldSendDueNotification()) {
                NotificationService::taskMaintenanceReminder($schedule->task, 'due');
                $schedule->update(['due_notification_sent_at' => Carbon::now('Asia/Manila')]);
                $dueSent++;
            } elseif ($schedule->shouldSendAdvanceNotification()) {
                NotificationService::taskMaintenanceReminder($schedule->task, 'advance');
                $schedule->update(['advance_notification_sent_at' => Carbon::now('Asia/Manila')]);
                $advanceSent++;
            }
        }

        $this->info("Checked {$schedules->count()} schedule(s): {$advanceSent} advance and {$dueSent} due reminder(s) sent.");

        return Command::SUCCESS;
    }
}

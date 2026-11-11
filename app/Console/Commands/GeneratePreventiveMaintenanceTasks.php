<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TaskMaintenanceSchedule;
use Carbon\Carbon;

class GeneratePreventiveMaintenanceTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:generate-tasks
                            {--dry-run : Show what tasks would be generated without creating them}
                            {--branch= : Filter by specific branch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate preventive maintenance tasks for maintenance schedules that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $branch = $this->option('branch');

        if ($this->option('dry-run')) {
            $due = TaskMaintenanceSchedule::with('task')
                ->where('is_active', true)
                ->whereDate('next_due_date', '<=', Carbon::now('Asia/Manila')->toDateString())
                ->when($branch, fn ($q) => $q->whereHas('task', fn ($t) => $t->where('branch', $branch)))
                ->get()
                ->filter(fn ($schedule) => $schedule->task && !$schedule->openGeneratedTask());

            if ($due->isEmpty()) {
                $this->info('No preventive maintenance tasks are due at this time.');
                return Command::SUCCESS;
            }

            foreach ($due as $schedule) {
                $this->line("  - {$schedule->task->task_number} ({$schedule->task->branch_name}): due {$schedule->next_due_date->format('M d, Y')}");
            }
            $this->info("Dry run: {$due->count()} task(s) would be generated.");

            return Command::SUCCESS;
        }

        $tasks = TaskMaintenanceSchedule::generateDueTasks($branch);

        foreach ($tasks as $task) {
            $this->info("  ✓ Generated {$task->task_number}: {$task->title}");
        }
        $this->info("Generated {$tasks->count()} preventive maintenance task(s).");

        return Command::SUCCESS;
    }
}

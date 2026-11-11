<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MigrateEmployeeIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employees:migrate-ids {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing employee IDs to new format (SA####, AD####, EM####)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get all users with old format IDs (not starting with SA, AD, or EM)
        $users = User::whereNotIn('employee_id', function($query) {
            $query->select('employee_id')
                  ->from('users')
                  ->where('employee_id', 'like', 'SA%')
                  ->orWhere('employee_id', 'like', 'AD%')
                  ->orWhere('employee_id', 'like', 'EM%');
        })
        ->whereNotNull('employee_id')
        ->get();

        if ($users->isEmpty()) {
            $this->info('No users found with old format employee IDs.');
            return;
        }

        $this->info("Found {$users->count()} users with old format employee IDs:");
        $this->newLine();

        $changes = [];
        $roleCounters = ['SA' => 0, 'AD' => 0, 'EM' => 0];

        // Get current highest numbers for each role
        foreach (['SA', 'AD', 'EM'] as $prefix) {
            $lastEmployee = User::where('employee_id', 'like', $prefix . '%')
                ->orderBy('employee_id', 'desc')
                ->first();
            
            if ($lastEmployee) {
                $lastNumber = (int) substr($lastEmployee->employee_id, 2);
                $roleCounters[$prefix] = $lastNumber;
            }
        }

        foreach ($users as $user) {
            $rolePrefix = match($user->role) {
                'superadmin' => 'SA',
                'admin' => 'AD',
                'employee' => 'EM',
                default => 'EM'
            };

            $roleCounters[$rolePrefix]++;
            $newId = $rolePrefix . str_pad($roleCounters[$rolePrefix], 4, '0', STR_PAD_LEFT);

            $changes[] = [
                'user_id' => $user->id,
                'name' => $user->full_name,
                'old_id' => $user->employee_id,
                'new_id' => $newId,
                'role' => $user->role
            ];

            $this->line("• {$user->full_name} ({$user->role}): {$user->employee_id} → {$newId}");
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info('This is what would be changed. Run without --dry-run to apply changes.');
            return;
        }

        $this->newLine();
        if (!$this->confirm('Do you want to proceed with migrating these employee IDs?')) {
            $this->info('Migration cancelled.');
            return;
        }

        // Apply changes
        $this->info('Migrating employee IDs...');
        $progressBar = $this->output->createProgressBar(count($changes));
        $progressBar->start();

        DB::transaction(function() use ($changes, $progressBar) {
            foreach ($changes as $change) {
                User::where('id', $change['user_id'])
                    ->update(['employee_id' => $change['new_id']]);
                
                // Log the change
                \App\Models\AuditLog::log(
                    'employee_id_migrated',
                    "Employee ID migrated from {$change['old_id']} to {$change['new_id']}",
                    User::find($change['user_id']),
                    ['old_employee_id' => $change['old_id']],
                    ['new_employee_id' => $change['new_id']]
                );
                
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine();
        $this->info('Employee ID migration completed successfully!');
        $this->info("Migrated " . count($changes) . " employee IDs.");
    }
}

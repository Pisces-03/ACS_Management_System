<?php

namespace App\Services;

use App\Models\CustomerFeedback;
use App\Models\MaintenanceLog;
use App\Models\MaterialRequest;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * * Cost analysis, recurring issues and personnel performance per branch.
 * * Used by the dashboards and the reports page.
 */
class BranchAnalytics
{
    public const BRANCHES = [
        'las_pinas' => 'Las Piñas',
        'cebu' => 'Cebu',
        'davao' => 'Davao',
    ];

    // * Material requests that turned into real spending
    private const SPENT_STATUSES = ['approved', 'ordered', 'received'];

    /**
     * Expenses and maintenance frequency per branch.
     * Repair expenses come from maintenance logs, material expenses from approved requests
     * (actual cost when recorded, otherwise the estimate).
     */
    public static function costSummary(?string $branch = null, ?Carbon $from = null, ?Carbon $to = null, ?int $employeeId = null): Collection
    {
        $branches = $branch ? [$branch => self::BRANCHES[$branch] ?? $branch] : self::BRANCHES;

        $repair = MaintenanceLog::query()
            ->when($branch, fn ($q) => $q->where('branch', $branch))
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->selectRaw('branch, COUNT(*) as logs, COALESCE(SUM(repair_cost), 0) as repair_cost')
            ->groupBy('branch')
            ->get()
            ->keyBy('branch');

        $materials = MaterialRequest::query()
            ->whereIn('status', self::SPENT_STATUSES)
            ->when($branch, fn ($q) => $q->where('branch', $branch))
            ->when($employeeId, fn ($q) => $q->where('requested_by', $employeeId))
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->selectRaw('branch, COUNT(*) as requests, COALESCE(SUM(COALESCE(actual_cost, total_estimated_cost)), 0) as material_cost')
            ->groupBy('branch')
            ->get()
            ->keyBy('branch');

        $byType = MaintenanceLog::query()
            ->when($branch, fn ($q) => $q->where('branch', $branch))
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->selectRaw('branch, type, COUNT(*) as total')
            ->groupBy('branch', 'type')
            ->get()
            ->groupBy('branch');

        return collect($branches)->map(function ($label, $key) use ($repair, $materials, $byType) {
            $repairCost = (float) ($repair[$key]->repair_cost ?? 0);
            $materialCost = (float) ($materials[$key]->material_cost ?? 0);

            return [
                'branch' => $key,
                'label' => $label,
                'maintenance_count' => (int) ($repair[$key]->logs ?? 0),
                'maintenance_by_type' => ($byType[$key] ?? collect())->pluck('total', 'type')->all(),
                'material_requests' => (int) ($materials[$key]->requests ?? 0),
                'repair_cost' => $repairCost,
                'material_cost' => $materialCost,
                'total_cost' => $repairCost + $materialCost,
            ];
        })->values();
    }

    /**
     * Equipment that needed service more than once - a sign of a recurring problem.
     */
    public static function recurringIssues(?string $branch = null, int $limit = 5, ?Carbon $from = null, ?Carbon $to = null, ?int $employeeId = null): Collection
    {
        return MaintenanceLog::query()
            ->when($branch, fn ($q) => $q->where('branch', $branch))
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->whereIn('type', ['repair', 'checkup', 'maintenance_logs'])
            ->selectRaw('LOWER(TRIM(equipment_name)) as equipment_key, MAX(equipment_name) as equipment_name, branch,
                COUNT(*) as occurrences, COALESCE(SUM(repair_cost), 0) as total_cost, MAX(created_at) as last_seen')
            ->groupBy(DB::raw('LOWER(TRIM(equipment_name))'), 'branch')
            ->havingRaw('COUNT(*) >= 2')
            ->orderByDesc('occurrences')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'equipment_name' => $row->equipment_name,
                'branch' => self::BRANCHES[$row->branch] ?? $row->branch,
                'occurrences' => (int) $row->occurrences,
                'total_cost' => (float) $row->total_cost,
                'last_seen' => Carbon::parse($row->last_seen),
            ]);
    }

    /**
     * Employees ranked by completed tasks, with their average customer rating.
     */
    public static function personnelPerformance(?string $branch = null, int $limit = 5, ?Carbon $from = null, ?Carbon $to = null, ?int $employeeId = null): Collection
    {
        $employees = User::where('role', 'employee')
            ->when(!$employeeId, fn ($q) => $q->where('is_active', true))
            ->when($branch, fn ($q) => $q->where('branch', $branch))
            ->when($employeeId, fn ($q) => $q->where('id', $employeeId))
            ->get();

        $ratings = CustomerFeedback::query()
            ->whereNotNull('employee_id')
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->selectRaw('employee_id, AVG(rating) as avg_rating, COUNT(*) as reviews')
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        return $employees->map(function ($employee) use ($ratings, $from, $to) {
            $tasks = Task::assignedTo($employee->id)
                ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]));

            $completed = (clone $tasks)->where('status', 'completed')->count();
            $overdue = (clone $tasks)->overdue()->count();
            $rating = $ratings[$employee->id] ?? null;

            return [
                'name' => $employee->full_name,
                'branch' => self::BRANCHES[$employee->branch] ?? $employee->branch,
                'department' => $employee->department,
                'completed_tasks' => $completed,
                'overdue_tasks' => $overdue,
                'avg_rating' => $rating ? round((float) $rating->avg_rating, 1) : null,
                'reviews' => $rating->reviews ?? 0,
            ];
        })
            ->sortByDesc(fn ($row) => [$row['completed_tasks'], $row['avg_rating'] ?? 0])
            ->take($limit)
            ->values();
    }
}

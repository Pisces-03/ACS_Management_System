<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomerFeedback;
use App\Models\Task;
use App\Models\MaintenanceLog;
use Illuminate\Support\Facades\Auth;

class CustomerFeedbackController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'employee') {
            abort(403);
        }

        $task = null;
        $maintenance = null;

        if ($request->filled('task')) {
            $task = Task::findOrFail($request->task);
            if (!$task->isAssignedTo($user->id)) {
                abort(403);
            }
        }

        if ($request->filled('maintenance')) {
            $maintenance = MaintenanceLog::findOrFail($request->maintenance);
            if ($maintenance->employee_id !== $user->id) {
                abort(403);
            }
        }

        if (!$task && !$maintenance) {
            abort(400, 'Task or maintenance log is required.');
        }

        if ($this->alreadyEvaluated($task?->id, $maintenance?->id)) {
            return redirect($task ? route('tasks.show', $task) : route('feedback.my'))
                ->with('error', 'The customer has already submitted an evaluation for this task.');
        }

        return view('feedback.create', compact('task', 'maintenance'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'employee') {
            abort(403);
        }

        $validated = $request->validate([
            'task_id' => 'nullable|exists:tasks,id',
            'maintenance_log_id' => 'nullable|exists:maintenance_logs,id',
            'customer_name' => 'required|string|max:255',
            'customer_contact' => [
                'nullable',
                'string',
                'regex:/^(\+63\d{10}|\d{11})$/',
                'min:11',
                'max:13',
            ],
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string',
            'service_aspects.quality' => 'nullable|integer|min:1|max:5',
            'service_aspects.timeliness' => 'nullable|integer|min:1|max:5',
            'service_aspects.professionalism' => 'nullable|integer|min:1|max:5',
            'would_recommend' => 'nullable|boolean',
            'suggestions' => 'nullable|string',
        ], [
            'customer_contact.regex' => 'Customer contact must be 11 digits (e.g., 09123456789) or +63 followed by 10 digits (e.g., +6391234567).',
            'customer_contact.min' => 'Customer contact must be at least 11 characters.',
            'customer_contact.max' => 'Customer contact must not exceed 13 characters.',
        ]);

        // Authorization based on linkage
        if (!empty($validated['task_id'])) {
            $task = Task::findOrFail($validated['task_id']);
            if (!$task->isAssignedTo($user->id)) {
                abort(403);
            }
        }
        if (!empty($validated['maintenance_log_id'])) {
            $maintenance = MaintenanceLog::findOrFail($validated['maintenance_log_id']);
            if ($maintenance->employee_id !== $user->id) {
                abort(403);
            }
        }

        // At least one linkage
        if (empty($validated['task_id']) && empty($validated['maintenance_log_id'])) {
            return back()->with('error', 'Please select a task or maintenance log.');
        }

        // * Blocks a second submission (e.g. a re-sent form or the browser Back button)
        if ($this->alreadyEvaluated($validated['task_id'] ?? null, $validated['maintenance_log_id'] ?? null)) {
            return redirect()->route('feedback.my')
                ->with('error', 'The customer has already submitted an evaluation for this task.');
        }

        CustomerFeedback::create([
            'task_id' => $validated['task_id'] ?? null,
            'maintenance_log_id' => $validated['maintenance_log_id'] ?? null,
            'employee_id' => $user->id,
            'customer_name' => $validated['customer_name'],
            'customer_contact' => $validated['customer_contact'] ?? null,
            'rating' => $validated['rating'],
            'feedback' => $validated['feedback'] ?? null,
            'service_aspects' => $request->input('service_aspects') ?? null,
            'would_recommend' => (bool)($validated['would_recommend'] ?? true),
            'suggestions' => $validated['suggestions'] ?? null,
            'branch' => $user->branch,
            'submitted_at' => now('Asia/Manila'),
        ]);

        return redirect()->route('feedback.my')->with('success', 'Thank you! Your evaluation has been submitted.');
    }

    // * One evaluation per task (or per log when it has no task)
    private function alreadyEvaluated(?int $taskId, ?int $maintenanceLogId): bool
    {
        if ($taskId) {
            return CustomerFeedback::where('task_id', $taskId)->exists();
        }

        return $maintenanceLogId
            && CustomerFeedback::where('maintenance_log_id', $maintenanceLogId)->exists();
    }

    public function myIndex(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'employee') {
            abort(403);
        }

        $feedback = CustomerFeedback::with(['task.maintenanceLogs', 'maintenanceLog'])
            ->where('employee_id', $user->id)
            ->orderBy('submitted_at', 'desc')
            ->paginate(10);

        return view('feedback.my', compact('feedback'));
    }
}



<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialRequest;
use App\Models\Task;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class MaterialRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // * Employees see their own requests
        if ($user->role === 'employee') {
            $query = MaterialRequest::with(['requestedBy', 'approvedBy', 'task'])
                ->where('requested_by', $user->id);

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Priority filter
            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }

            // Calculate stats
            $statsQuery = MaterialRequest::query()->where('requested_by', $user->id);
            $stats = [
                'total' => (clone $statsQuery)->count(),
                'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
                'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
                'rejected' => (clone $statsQuery)->where('status', 'rejected')->count(),
            ];

            $materialRequests = $query->orderBy('created_at', 'desc')->paginate(15);

            return view('material-requests.index', compact('materialRequests', 'stats'));
        }
        
        // * Admins and Superadmins should use pending approvals route
        abort(403, 'Please use the Pending Approvals page to view material requests.');
    }
    
    /**
     * * Pending Approvals page for admins and superadmins
     */
    public function pendingApprovals(Request $request)
    {
        $user = Auth::user();
        
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort(403);
        }
        
        $query = MaterialRequest::with(['requestedBy', 'approvedBy', 'task']);

        // * Filter based on user role
        if ($user->role === 'admin') {
            $query->where('branch', $user->branch);
        }
        // * Superadmins can see all branches

        // * Status filter - default to pending if not specified, "all" shows every status
        $status = $request->input('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // * Priority filter
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // * Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhereHas('requestedBy', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // * Calculate stats
        $statsQuery = MaterialRequest::query();
        if ($user->role === 'admin') {
            $statsQuery->where('branch', $user->branch);
        }
        
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $statsQuery)->where('status', 'rejected')->count(),
            'ordered' => (clone $statsQuery)->where('status', 'ordered')->count(),
            'received' => (clone $statsQuery)->where('status', 'received')->count(),
        ];

        $materialRequests = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('material-requests.pending-approvals', compact('materialRequests', 'stats', 'status'));
    }

    public function create()
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            abort(403);
        }

        // Get available tasks for this user's branch
        $tasks = Task::where('branch', $user->branch)
            ->where('status', '!=', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get recent requests for reference
        $recentRequests = MaterialRequest::where('requested_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('material-requests.create', compact('tasks', 'recentRequests'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role === 'admin') {
            abort(403);
        }
        
        $user = Auth::user();
        
        // * Handle both single-item form format and array format
        if ($request->has('item_name')) {
            // * Single-item form format (from create.blade.php)
            $validated = $request->validate([
                'task_id' => 'nullable|exists:tasks,id',
                'item_name' => 'required|string|max:255',
                'quantity' => 'required|integer|min:1',
                'unit' => 'required|string|max:50',
                'estimated_cost' => 'nullable|numeric|min:0',
                'description' => 'nullable|string',
                'category' => 'required|string|max:255',
                'purpose' => 'required|string',
                'priority' => 'required|in:low,medium,high,urgent,normal',
                'needed_by' => 'nullable|date|after:today',
            ]);
            
            // * Convert single item to array format
            $materials = [[
                'name' => $validated['item_name'],
                'quantity' => $validated['quantity'],
                'unit' => $validated['unit'],
                'estimated_cost' => $validated['estimated_cost'] ?? 0,
                'description' => $validated['description'] ?? '',
            ]];
            
            $justification = $validated['purpose'];
            $priority = $validated['priority'] === 'normal' ? 'medium' : $validated['priority'];
            $totalCost = $validated['estimated_cost'] ?? 0;
        } else {
            // * Array format (for API or future multi-item forms)
            $validated = $request->validate([
                'task_id' => 'nullable|exists:tasks,id',
                'materials' => 'required|array|min:1',
                'materials.*.name' => 'required|string|max:255',
                'materials.*.quantity' => 'required|integer|min:1',
                'materials.*.unit' => 'required|string|max:50',
                'materials.*.estimated_cost' => 'nullable|numeric|min:0',
                'materials.*.description' => 'nullable|string',
                'category' => 'required|string|max:255',
                'justification' => 'required|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'needed_by' => 'nullable|date|after:today',
            ]);
            
            $materials = $validated['materials'];
            $justification = $validated['justification'];
            $priority = $validated['priority'];
            $totalCost = collect($validated['materials'])->sum('estimated_cost');
        }

        // * request_number is generated by the model (unique even for two requests in the same second)
        $materialRequest = MaterialRequest::create([
            'requested_by' => $user->id,
            'task_id' => $validated['task_id'] ?? null,
            'branch' => $user->branch,
            'materials' => $materials,
            'category' => $validated['category'] ?? null,
            'justification' => $justification,
            'priority' => $priority,
            'total_estimated_cost' => $totalCost,
            'needed_by' => $validated['needed_by'] ?? null,
            'status' => 'pending',
        ]);

        // Send notifications to approvers
        NotificationService::materialRequestApprovalNeeded($materialRequest);

        AuditLog::log(
            'create',
            "Created material request: {$materialRequest->request_number}",
            $materialRequest,
            null,
            $validated
        );

        return redirect()->route('material-requests.index')
            ->with('success', 'Material request submitted successfully');
    }

    public function show(MaterialRequest $materialRequest)
    {
        $materialRequest->load(['requestedBy', 'approvedBy', 'task']);

        $user = Auth::user();
        
        // * Check access permissions
        if ($user->role === 'employee' && $materialRequest->requested_by !== $user->id) {
            abort(403);
        }
        
        if ($user->role === 'admin' && $materialRequest->branch !== $user->branch) {
            abort(403);
        }
        
        // * Admins and superadmins can view requests for approval
        // * Employees can view their own requests

        return view('material-requests.show', compact('materialRequest'));
    }

    public function approve(Request $request, $id)
    {
        $user = Auth::user();

        // * Log method execution
        \Log::info('Material request approval attempt', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'user_branch' => $user->branch,
            'request_id_param' => $id,
            'route_param' => $request->route('materialRequest'),
            'wants_json' => $request->wantsJson(),
            'is_ajax' => $request->ajax(),
            'accept_header' => $request->header('Accept'),
        ]);
        
        // * Manually find the MaterialRequest to handle failures gracefully
        $materialRequest = MaterialRequest::find($id);
        if (!$materialRequest) {
            \Log::warning('MaterialRequest not found', [
                'request_id' => $id,
                'user_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Material request not found'
            ], 404);
        }

        // * Log AFTER finding the model
        \Log::info('=== APPROVE METHOD CALLED (AFTER MODEL FOUND) ===', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'user_branch' => $user->branch,
            'request_id' => $materialRequest->id,
            'request_branch' => $materialRequest->branch,
            'request_status' => $materialRequest->status,
            'is_superadmin' => $user->role === 'superadmin',
            'wants_json' => $request->wantsJson(),
            'is_ajax' => $request->ajax(),
            'accept_header' => $request->header('Accept'),
        ]);

        // * Debug: Log the exact role value and type
        \Log::info('=== ROLE CHECK DEBUG ===', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'role_type' => gettype($user->role),
            'role_length' => strlen($user->role),
            'role_bytes' => bin2hex($user->role),
            'is_admin' => $user->role === 'admin',
            'is_superadmin' => $user->role === 'superadmin',
            'in_array_check' => in_array($user->role, ['admin', 'superadmin']),
            'lowercase_check' => in_array(strtolower(trim($user->role)), ['admin', 'superadmin']),
        ]);

        // * Use case-insensitive check to handle any case variations
        $normalizedRole = strtolower(trim($user->role));
        
        if (!in_array($normalizedRole, ['admin', 'superadmin'])) {
            \Log::warning('Approval denied: User is not admin/superadmin', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'normalized_role' => $normalizedRole,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can approve requests. Your role: ' . $user->role . ' (normalized: ' . $normalizedRole . ')'
            ], 403);
        }

        // * Only check branch for admins, superadmins bypass branch check
        if ($normalizedRole === 'admin' && $materialRequest->branch !== $user->branch) {
            \Log::warning('Approval denied: Branch mismatch', [
                'user_id' => $user->id,
                'user_branch' => $user->branch,
                'request_branch' => $materialRequest->branch,
            ]);
            return response()->json([
                'success' => false,
                'message' => "You can only approve requests from your branch. Your branch: {$user->branch}, Request branch: {$materialRequest->branch}"
            ], 403);
        }

        // * Check if request is still pending
        if ($materialRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been ' . $materialRequest->status
            ], 400);
        }

        // * Get data from JSON body or form data
        $requestData = $request->all();
        
        // * If request is JSON, merge JSON data
        if ($request->isJson() || $request->header('Content-Type') === 'application/json') {
            $jsonData = $request->json()->all();
            $requestData = array_merge($requestData, $jsonData);
        }
        
        $validated = validator($requestData, [
            'approval_notes' => 'nullable|string',
            'actual_cost' => 'nullable|numeric|min:0',
        ])->validate();

        $oldStatus = $materialRequest->status;

        $materialRequest->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $validated['approval_notes'] ?? null,
            'actual_cost' => $validated['actual_cost'] ?? null,
        ]);

        // Send notification to requester
        NotificationService::materialRequestStatusChanged($materialRequest, $oldStatus);

        AuditLog::log(
            'approve',
            "Approved material request: {$materialRequest->request_number}",
            $materialRequest,
            ['status' => $oldStatus],
            ['status' => 'approved', 'approval_notes' => $validated['approval_notes'] ?? null]
        );

        return response()->json([
            'success' => true,
            'message' => 'Material request approved successfully'
        ]);
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();

        // * Log method execution
        \Log::info('Material request rejection attempt', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'user_branch' => $user->branch,
            'request_id_param' => $id,
            'route_param' => $request->route('materialRequest'),
            'wants_json' => $request->wantsJson(),
            'is_ajax' => $request->ajax(),
            'accept_header' => $request->header('Accept'),
        ]);
        
        // * Manually find the MaterialRequest to handle failures gracefully
        $materialRequest = MaterialRequest::find($id);
        if (!$materialRequest) {
            \Log::warning('MaterialRequest not found', [
                'request_id' => $id,
                'user_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Material request not found'
            ], 404);
        }

        // * Log AFTER finding the model
        \Log::info('=== REJECT METHOD CALLED (AFTER MODEL FOUND) ===', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'user_branch' => $user->branch,
            'request_id' => $materialRequest->id,
            'request_branch' => $materialRequest->branch,
            'request_status' => $materialRequest->status,
            'is_superadmin' => $user->role === 'superadmin',
            'wants_json' => $request->wantsJson(),
            'is_ajax' => $request->ajax(),
            'accept_header' => $request->header('Accept'),
        ]);

        // * Debug: Log the exact role value and type
        \Log::info('=== ROLE CHECK DEBUG (REJECT) ===', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'role_type' => gettype($user->role),
            'role_length' => strlen($user->role),
            'role_bytes' => bin2hex($user->role),
            'is_admin' => $user->role === 'admin',
            'is_superadmin' => $user->role === 'superadmin',
            'in_array_check' => in_array($user->role, ['admin', 'superadmin']),
            'lowercase_check' => in_array(strtolower(trim($user->role)), ['admin', 'superadmin']),
        ]);

        // * Use case-insensitive check to handle any case variations
        $normalizedRole = strtolower(trim($user->role));
        if (!in_array($normalizedRole, ['admin', 'superadmin'])) {
            \Log::warning('Rejection denied: User is not admin/superadmin', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'normalized_role' => $normalizedRole,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can reject requests. Your role: ' . $user->role . ' (normalized: ' . $normalizedRole . ')'
            ], 403);
        }

        // * Only check branch for admins, superadmins bypass branch check
        if ($normalizedRole === 'admin' && $materialRequest->branch !== $user->branch) {
            \Log::warning('Rejection denied: Branch mismatch', [
                'user_id' => $user->id,
                'user_branch' => $user->branch,
                'request_branch' => $materialRequest->branch,
            ]);
            return response()->json([
                'success' => false,
                'message' => "You can only reject requests from your branch. Your branch: {$user->branch}, Request branch: {$materialRequest->branch}"
            ], 403);
        }

        // * Check if request is still pending
        if ($materialRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been ' . $materialRequest->status
            ], 400);
        }

        // * Get data from JSON body or form data
        $requestData = $request->all();
        
        // * If request is JSON, merge JSON data
        if ($request->isJson() || $request->header('Content-Type') === 'application/json') {
            $jsonData = $request->json()->all();
            $requestData = array_merge($requestData, $jsonData);
        }
        
        $validated = validator($requestData, [
            'rejection_reason' => 'required|string|min:10',
        ])->validate();

        $oldStatus = $materialRequest->status;

        $materialRequest->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        // Send notification to requester
        NotificationService::materialRequestStatusChanged($materialRequest, $oldStatus);

        AuditLog::log(
            'reject',
            "Rejected material request: {$materialRequest->request_number}. Reason: {$validated['rejection_reason']}",
            $materialRequest,
            ['status' => $oldStatus],
            ['status' => 'rejected', 'rejection_reason' => $validated['rejection_reason']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Material request rejected'
        ]);
    }

    public function updateStatus(Request $request, MaterialRequest $materialRequest)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        if ($user->role === 'admin' && $materialRequest->branch !== $user->branch) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:ordered,received',
            'notes' => 'nullable|string',
            'actual_cost' => 'nullable|numeric|min:0',
        ]);

        // * Procurement moves forward only: approved -> ordered -> received
        $allowedFrom = ['ordered' => ['approved'], 'received' => ['approved', 'ordered']];
        if (!in_array($materialRequest->status, $allowedFrom[$validated['status']], true)) {
            $message = "A {$materialRequest->status} request cannot be marked as {$validated['status']}.";
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 400)
                : back()->with('error', $message);
        }

        $oldStatus = $materialRequest->status;

        $updates = ['status' => $validated['status']];
        if (!empty($validated['notes'])) {
            $updates['approval_notes'] = trim(($materialRequest->approval_notes ? $materialRequest->approval_notes . "\n" : '')
                . ucfirst($validated['status']) . ': ' . $validated['notes']);
        }
        if (isset($validated['actual_cost'])) {
            $updates['actual_cost'] = $validated['actual_cost'];
        }

        $materialRequest->update($updates);

        NotificationService::materialRequestStatusChanged($materialRequest, $oldStatus);

        AuditLog::log(
            'update',
            "Updated material request status: {$materialRequest->request_number} to {$validated['status']}",
            $materialRequest,
            ['status' => $oldStatus],
            $updates
        );

        $message = 'Material request marked as ' . $validated['status'] . '.';
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('material-requests.show', $materialRequest)->with('success', $message);
    }
}

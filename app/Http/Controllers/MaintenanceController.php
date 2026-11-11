<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaintenanceLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;

class MaintenanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $query = MaintenanceLog::with(['employee']);

        // Filter based on user role
        if ($user->role === 'admin') {
            $query->byBranch($user->branch);
        } elseif ($user->role === 'employee') {
            $query->where('employee_id', $user->id);
        }

        // Optional filters to align with UI
        if (request('status')) {
            $query->where('status', request('status'));
        }
        if (request('type')) {
            $query->where('type', request('type'));
        }
        if (request('search')) {
            $search = '%' . request('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('log_number', 'like', $search)
                  ->orWhere('equipment_name', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        // Calculate stats before pagination
        $statsQuery = MaintenanceLog::query();
        if ($user->role === 'admin') {
            $statsQuery->byBranch($user->branch);
        } elseif ($user->role === 'employee') {
            $statsQuery->where('employee_id', $user->id);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'this_month' => (clone $statsQuery)->whereMonth('created_at', now()->month)->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
        ];

        $maintenanceLogs = $query->latest()->paginate(10);

        return view('maintenance.index', compact('maintenanceLogs', 'stats'));
    }

    public function create()
    {
        $user = Auth::user();

        // Get technicians/employees who can be assigned to maintenance tasks
        $technicians = User::where('role', 'employee');

        if ($user->role === 'admin') {
            $technicians->where('branch', $user->branch);
        }

        $technicians = $technicians->get();

        return view('maintenance.create', compact('technicians'));
    }

    public function store(Request $request)
    {
        // * Debug: Log that store method was called
        \Log::info('MaintenanceController@store called', [
            'has_photos' => $request->hasFile('photos'),
            'photos_count' => $request->hasFile('photos') ? count($request->file('photos')) : 0,
            'all_input_keys' => array_keys($request->all()),
            'method' => $request->method()
        ]);
        
        try {
            $validated = $request->validate([
                'equipment_name' => 'required|string|max:255',
                // Align with DB enum: installation|reinstallation|repair|dismantling|checkup|maintenance_logs
                'type' => 'required|in:installation,reinstallation,repair,dismantling,checkup,maintenance_logs',
                'description' => 'required|string',
                'issues_found' => 'nullable|string',
                'actions_taken' => 'required|string',
                'parts_used' => 'nullable|string',
                'next_maintenance_date' => 'nullable|date|after:today',
                'photos' => 'nullable|array',
                'photos.*' => 'file|mimes:jpeg,jpg,png|max:5120',
                'videos' => 'nullable|array',
                'videos.*' => 'file|mimes:mp4,mov,webm,3gp|max:20480',
                'repair_cost' => 'nullable|numeric|min:0',
            ]);
            
            \Log::info('Validation passed', ['validated_keys' => array_keys($validated)]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', [
                'errors' => $e->errors(),
                'input' => $request->except(['photos']) // Don't log file contents
            ]);
            throw $e;
        }

        $user = Auth::user();
        $photoPaths = [];
        if ($request->hasFile('photos')) {
            // * Ensure the maintenance-photos directory exists
            $storage = Storage::disk('public');
            if (!$storage->exists('maintenance-photos')) {
                $storage->makeDirectory('maintenance-photos');
                \Log::info('Created maintenance-photos directory');
            }
            
            foreach ($request->file('photos') as $photo) {
                if ($photo->isValid()) {
                    try {
                        // * Generate a unique filename to avoid conflicts
                        $filename = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $photo->getClientOriginalName());
                        
                        // * Get the full storage path
                        $directory = storage_path('app/public/maintenance-photos');
                        $fullPath = $directory . '/' . $filename;
                        
                        // * Ensure directory exists and is writable
                        if (!is_dir($directory)) {
                            if (!mkdir($directory, 0755, true)) {
                                \Log::error('Failed to create directory', ['directory' => $directory, 'permissions' => substr(sprintf('%o', fileperms(dirname($directory))), -4)]);
                                continue;
                            }
                        }
                        
                        // * Check if directory is writable
                        if (!is_writable($directory)) {
                            \Log::error('Directory is not writable', [
                                'directory' => $directory,
                                'permissions' => substr(sprintf('%o', fileperms($directory)), -4),
                                'owner' => fileowner($directory),
                                'current_user' => get_current_user()
                            ]);
                            continue;
                        }
                        
                        // * Store image in FILE SYSTEM (storage/app/public/maintenance-photos/)
                        // * NOT in MySQL database - only file paths are stored in DB
                        // * This is the correct approach for file storage
                        if (move_uploaded_file($photo->getRealPath(), $fullPath)) {
                            // * File successfully saved to: storage/app/public/maintenance-photos/filename.jpg
                            // * Now save only the relative path to database (JSON array)
                            $relativePath = 'maintenance-photos/' . $filename;
                            $photoPaths[] = $relativePath;
                            
                            \Log::info('Photo uploaded successfully (stored in file system)', [
                                'file_system_path' => $fullPath, // Actual file location
                                'database_path' => $relativePath, // Path stored in MySQL JSON column
                                'size' => filesize($fullPath),
                                'original_name' => $photo->getClientOriginalName()
                            ]);
                        } else {
                            // * Fallback: Try using Laravel's storeAs method
                            $path = $photo->storeAs('maintenance-photos', $filename, 'public');
                            
                            if ($path && $storage->exists($path)) {
                                // * File saved successfully
                                $photoPaths[] = $path;
                                \Log::info('Photo uploaded successfully (using Laravel storeAs)', [
                                    'file_system_path' => storage_path('app/public/' . $path),
                                    'database_path' => $path,
                                    'size' => $photo->getSize(),
                                    'original_name' => $photo->getClientOriginalName()
                                ]);
                            } else {
                                \Log::error('Photo upload failed - both methods failed', [
                                    'filename' => $photo->getClientOriginalName(),
                                    'storeAs_path' => $path,
                                    'full_path' => $fullPath,
                                    'directory_exists' => is_dir($directory),
                                    'directory_writable' => is_writable($directory),
                                    'directory_permissions' => is_dir($directory) ? substr(sprintf('%o', fileperms($directory)), -4) : 'N/A',
                                    'temp_file' => $photo->getRealPath(),
                                    'temp_exists' => file_exists($photo->getRealPath()),
                                    'php_error' => error_get_last()
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('Photo upload exception', [
                            'filename' => $photo->getClientOriginalName(),
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } else {
                    \Log::error('Invalid photo file', [
                        'filename' => $photo->getClientOriginalName(), 
                        'error' => $photo->getError(),
                        'error_code' => $photo->getError() === UPLOAD_ERR_INI_SIZE ? 'File too large (php.ini)' : 
                                       ($photo->getError() === UPLOAD_ERR_FORM_SIZE ? 'File too large (form)' : 'Unknown')
                    ]);
                }
            }
        } else {
            \Log::info('No photos in request', [
                'hasFiles' => $request->hasFile('photos'), 
                'allFiles' => array_keys($request->allFiles())
            ]);
        }

        // * Videos are stored on the public disk like photos (only file paths go to the DB)
        $videoPaths = [];
        foreach ($request->file('videos', []) as $video) {
            if ($video->isValid()) {
                $filename = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $video->getClientOriginalName());
                if ($path = $video->storeAs('maintenance-videos', $filename, 'public')) {
                    $videoPaths[] = $path;
                }
            }
        }

        $workPerformed = $validated['actions_taken'];
        if (!empty($validated['issues_found'])) {
            $workPerformed = "Issues Found: " . $validated['issues_found'] . "\n\nActions Taken: " . $validated['actions_taken'];
        }

        // Let the model generate the unique log_number automatically
        // * For employee submissions, set status to 'pending' for admin review
        $maintenanceLog = MaintenanceLog::create([
            'employee_id' => $user->id,
            'type' => $validated['type'],
            'equipment_name' => $validated['equipment_name'],
            'description' => $validated['description'],
            'work_performed' => $workPerformed,
            'materials_used' => $validated['parts_used'] ?? null,
            'repair_cost' => $validated['repair_cost'] ?? null,
            'next_maintenance_date' => $validated['next_maintenance_date'] ?? null,
            'status' => 'pending', // * Employee submissions need admin review
            'branch' => $user->branch,
            'performed_at' => now(), // * When the maintenance was actually performed
            // * Store only file paths in MySQL JSON column (photos stored in file system, not in DB)
            'photos' => !empty($photoPaths) ? $photoPaths : null,
            'videos' => !empty($videoPaths) ? $videoPaths : null,
        ]);
        
        \Log::info('Maintenance log created', [
            'log_id' => $maintenanceLog->id,
            'log_number' => $maintenanceLog->log_number,
            'photos_count' => count($photoPaths),
            'photos_stored_in' => 'File System: storage/app/public/maintenance-photos/',
            'photos_paths_in_db' => $photoPaths, // Only paths stored in MySQL, not actual images
            'user_role' => $user->role,
            'user_id' => $user->id,
            'user_name' => $user->full_name
        ]);

        // * Notify branch admins and superadmins when employee creates a maintenance log
        if ($user->role === 'employee') {
            \Log::info('Employee creating maintenance log - attempting to send notifications', [
                'employee_id' => $user->id,
                'employee_name' => $user->full_name,
                'maintenance_log_id' => $maintenanceLog->id,
                'branch' => $maintenanceLog->branch
            ]);
            
            try {
                $notifications = NotificationService::maintenanceFindingsSubmitted($maintenanceLog, $user);
                \Log::info('Maintenance findings notifications sent successfully', [
                    'notifications_count' => count($notifications),
                    'notification_ids' => array_map(fn($n) => $n->id, $notifications)
                ]);
            } catch (\Throwable $notifyError) {
                // Log and continue; should not break maintenance log creation
                \Log::error('Maintenance findings notification failed', [
                    'error' => $notifyError->getMessage(),
                    'trace' => $notifyError->getTraceAsString(),
                    'file' => $notifyError->getFile(),
                    'line' => $notifyError->getLine()
                ]);
            }
        } else {
            \Log::info('User is not an employee - skipping notification', [
                'user_role' => $user->role,
                'user_id' => $user->id
            ]);
        }

        return redirect()->route('maintenance.index')
            ->with('success', 'Maintenance log created successfully');
    }

    public function show(MaintenanceLog $maintenance)
    {
        $this->authorizeView($maintenance);
        $maintenance->load(['employee']);
        return view('maintenance.show', compact('maintenance'));
    }

    public function edit(MaintenanceLog $maintenance)
    {
        $this->authorizeReview($maintenance);
        $maintenance->load(['employee', 'task']);

        return view('maintenance.edit', compact('maintenance'));
    }

    /**
     * * Admin review of submitted findings: set the status and record costs and notes.
     */
    public function update(Request $request, MaintenanceLog $maintenance)
    {
        $this->authorizeReview($maintenance);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,requires_followup',
            'repair_cost' => 'nullable|numeric|min:0',
            'hours_worked' => 'nullable|numeric|min:0|max:999',
            'next_maintenance_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $oldValues = $maintenance->only(array_keys($validated));

        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = $maintenance->completed_at ?? now();
        } else {
            $validated['completed_at'] = null;
        }

        $maintenance->update($validated);

        \App\Models\AuditLog::log(
            'update',
            "Reviewed maintenance log {$maintenance->log_number} (status: {$maintenance->status})",
            $maintenance,
            $oldValues,
            $validated
        );

        return redirect()->route('maintenance.show', $maintenance)
            ->with('success', 'Maintenance log updated successfully.');
    }

    private function authorizeView(MaintenanceLog $maintenance): void
    {
        $user = Auth::user();
        if ($user->role === 'employee' && $maintenance->employee_id !== $user->id) {
            abort(403, 'You can only view your own maintenance logs.');
        }
        if ($user->role === 'admin' && $maintenance->branch !== $user->branch) {
            abort(403, 'You can only view maintenance logs from your branch.');
        }
    }

    private function authorizeReview(MaintenanceLog $maintenance): void
    {
        if (Auth::user()->role === 'employee') {
            abort(403, 'Only admins can review maintenance logs.');
        }
        $this->authorizeView($maintenance);
    }

    public function destroy(MaintenanceLog $maintenance)
    {
        $user = Auth::user();
        
        // Authorization check
        if ($user->role === 'admin' && $maintenance->branch !== $user->branch) {
            abort(403, 'You can only delete maintenance logs from your branch.');
        } elseif ($user->role === 'employee') {
            abort(403, 'You do not have permission to delete maintenance logs.');
        }
        
        // Delete associated photos if any
        if ($maintenance->photos) {
            foreach ($maintenance->photos as $photo) {
                if (Storage::disk('public')->exists($photo)) {
                    Storage::disk('public')->delete($photo);
                }
            }
        }
        
        // Delete associated videos if any
        if ($maintenance->videos) {
            foreach ($maintenance->videos as $video) {
                if (Storage::disk('public')->exists($video)) {
                    Storage::disk('public')->delete($video);
                }
            }
        }
        
        $maintenance->delete();
        
        return redirect()->route('maintenance.index')
            ->with('success', 'Maintenance log deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    /**
     * Get notifications for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'notifications' => [],
                    'pagination' => []
                ], 401)->header('Content-Type', 'application/json');
            }
            
            $limit = $request->get('limit', 10);
            
            try {
                $notifications = Notification::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate($limit);
                
                return response()->json([
                    'notifications' => $notifications->items(),
                    'pagination' => [
                        'current_page' => $notifications->currentPage(),
                        'last_page' => $notifications->lastPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                    ]
                ])->header('Content-Type', 'application/json');
            } catch (\Illuminate\Database\QueryException $e) {
                \Log::warning('Notifications table query failed in index: ' . $e->getMessage());
                
                return response()->json([
                    'notifications' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $limit,
                        'total' => 0,
                    ]
                ])->header('Content-Type', 'application/json');
            }
        } catch (\Exception $e) {
            \Log::error('Error fetching notifications index: ' . $e->getMessage());
            
            return response()->json([
                'notifications' => [],
                'pagination' => []
            ], 500)->header('Content-Type', 'application/json');
        }
    }

    /**
     * Get unread notifications
     */
    public function unread()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'notifications' => [],
                    'count' => 0,
                    'error' => 'User not authenticated'
                ], 401)->header('Content-Type', 'application/json');
            }
            
            // Check if notifications table exists
            try {
                // Get all notifications (read and unread) for display
                $allNotifications = Notification::where('user_id', $user->id)
                    ->orderBy('is_read', 'asc') // Unread first
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->get();
                
                // Get unread count for badge
                $count = NotificationService::getUnreadCount($user);
            } catch (\Illuminate\Database\QueryException $e) {
                // Database table might not exist or query failed
                \Log::warning('Notifications table query failed: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'code' => $e->getCode()
                ]);
                
                return response()->json([
                    'notifications' => [],
                    'count' => 0
                ])->header('Content-Type', 'application/json');
            } catch (\Exception $e) {
                // Other errors from NotificationService
                \Log::error('Error in NotificationService: ' . $e->getMessage());
                
                return response()->json([
                    'notifications' => [],
                    'count' => 0
                ])->header('Content-Type', 'application/json');
            }
            
            return response()->json([
                'notifications' => $allNotifications ?? [],
                'count' => $count ?? 0
            ])->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            \Log::error('Error fetching unread notifications: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'notifications' => [],
                'count' => 0,
                'error' => 'Failed to load notifications'
            ], 500)->header('Content-Type', 'application/json');
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification)
    {
        // Ensure user can only mark their own notifications
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }
        
        NotificationService::markAsRead($notification);
        
        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ])->header('Content-Type', 'application/json');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = auth()->user();
        
        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        
        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ])->header('Content-Type', 'application/json');
    }

    /**
     * Delete a notification
     */
    public function destroy(Notification $notification)
    {
        // Ensure user can only delete their own notifications
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }
        
        $notification->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Notification deleted'
        ])->header('Content-Type', 'application/json');
    }

    /**
     * Get notification count widget data
     */
    public function widget()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'unread_count' => 0,
                    'error' => 'User not authenticated'
                ], 401)->header('Content-Type', 'application/json');
            }
            
            // Check if notifications table exists
            try {
                $unreadCount = NotificationService::getUnreadCount($user);
            } catch (\Illuminate\Database\QueryException $e) {
                // Database table might not exist or query failed
                \Log::warning('Notifications table query failed in widget: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'code' => $e->getCode()
                ]);
                
                return response()->json([
                    'unread_count' => 0
                ])->header('Content-Type', 'application/json');
            } catch (\Exception $e) {
                // Other errors from NotificationService
                \Log::error('Error in NotificationService widget: ' . $e->getMessage());
                
                return response()->json([
                    'unread_count' => 0
                ])->header('Content-Type', 'application/json');
            }
            
            return response()->json([
                'unread_count' => $unreadCount ?? 0
            ])->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            \Log::error('Error fetching notification widget: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'unread_count' => 0,
                'error' => 'Failed to load notification count'
            ], 500)->header('Content-Type', 'application/json');
        }
    }
}

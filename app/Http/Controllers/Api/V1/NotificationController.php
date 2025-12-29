<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\LeysNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(
        private LeysNotificationService $notificationService
    ) {}

    /**
     * Get user notifications with pagination
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $type = $request->input('type');
        
        $notifications = $this->notificationService->getUserNotifications(
            $request->user()->id,
            $perPage,
            $type
        );

        return NotificationResource::collection($notifications);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count
            ],
            'message' => 'Unread notifications count retrieved successfully'
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(string $id, Request $request): JsonResponse
    {
        $notification = $this->notificationService->markAsRead(
            $id,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => new NotificationResource($notification),
            'message' => 'Notification marked as read successfully'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => [
                'marked_count' => $count
            ],
            'message' => 'All notifications marked as read successfully'
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $this->notificationService->deleteNotification($id, $request->user()->id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Notification deleted successfully'
        ], 200);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return NotificationResource::collection($notifications);
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'unread_count' => $request->user()
                ->notifications()
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function markAsRead(Request $request, int $notification)
    {
        $userNotification = $request->user()
            ->notifications()
            ->findOrFail($notification);

        $userNotification->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }
}
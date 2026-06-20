<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        $notifications = Notification::orderBy('created_at', 'desc')->paginate(20);
        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully.',
            'data' => $notifications
        ]);
    }

    public function getUnreadCount(Request $request)
    {
        $count = Notification::where('is_read', false)->count();
        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count
            ]
        ]);
    }

    public function markAsRead(Request $request)
    {
        $request->validate([
            'id' => 'nullable|integer'
        ]);

        $id = $request->post('id');

        if ($id) {
            $notification = Notification::find($id);
            if ($notification) {
                $notification->update(['is_read' => true]);
            }
        } else {
            
            Notification::where('is_read', false)->update(['is_read' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read successfully.'
        ]);
    }

    public function deleteNotification(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        $notification = Notification::find($request->post('id'));
        if ($notification) {
            $notification->delete();
            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notification not found.'
        ], 404);
    }
}

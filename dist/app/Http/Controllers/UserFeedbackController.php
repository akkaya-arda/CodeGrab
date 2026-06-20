<?php

namespace App\Http\Controllers;

use App\Models\UserFeedback;
use App\Models\Notification;
use Illuminate\Http\Request;

class UserFeedbackController extends Controller
{
    public function getFeedbacks(Request $request)
    {
        
        $feedbacks = UserFeedback::with('fetchLog')->orderBy('created_at', 'desc')->paginate(20);
        return response()->json([
            'success' => true,
            'message' => 'Feedbacks retrieved successfully.',
            'data' => $feedbacks
        ]);
    }

    public function submitFeedback(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'platform' => 'required|string',
            'is_working' => 'required|boolean',
            'comment' => 'nullable|string',
            'log_id' => 'nullable|integer|exists:guard_fetch_logs,id'
        ]);

        $feedback = UserFeedback::create([
            'email' => $request->post('email'),
            'platform' => $request->post('platform'),
            'is_working' => (bool) $request->post('is_working'),
            'comment' => $request->post('comment'),
            'log_id' => $request->post('log_id'),
        ]);

        
        if (!$feedback->is_working) {
            Notification::create([
                'type' => 'user_report',
                'title' => "User reported broken code for " . $feedback->platform,
                'message' => "The user at {$feedback->email} reported that the intercepted code for {$feedback->platform} did not work. Comment: " . ($feedback->comment ?? 'No comment provided.')
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your feedback!'
        ]);
    }
}

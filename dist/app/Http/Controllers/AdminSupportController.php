<?php

namespace App\Http\Controllers;

use App\Models\SupportThread;
use App\Models\SupportMessage;
use Illuminate\Http\Request;

class AdminSupportController extends Controller
{
    
    public function index()
    {
        $threads = SupportThread::withCount(['messages'])
            ->orderBy('status', 'asc') 
            ->orderBy('updated_at', 'desc')
            ->get();

        
        foreach ($threads as $thread) {
            $lastMessage = SupportMessage::where('support_thread_id', $thread->id)
                ->orderBy('created_at', 'desc')
                ->first();
            $thread->last_message = $lastMessage ? $lastMessage->message : null;
            $thread->last_message_time = $lastMessage ? $lastMessage->created_at->toIso8601String() : null;
        }

        return response()->json([
            'success' => true,
            'message' => 'Support threads retrieved successfully.',
            'data' => $threads
        ]);
    }

    
    public function show($id)
    {
        $thread = SupportThread::find($id);

        if (!$thread) {
            return response()->json([
                'success' => false,
                'message' => 'Support thread not found.'
            ], 404);
        }

        $messages = $thread->messages()->orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Support thread details loaded.',
            'data' => [
                'thread' => $thread,
                'messages' => $messages
            ]
        ]);
    }

    
    public function reply(Request $request, $id)
    {
        $thread = SupportThread::find($id);

        if (!$thread) {
            return response()->json([
                'success' => false,
                'message' => 'Support thread not found.'
            ], 404);
        }

        $data = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $messageText = trim($data['message']);

        $message = SupportMessage::create([
            'support_thread_id' => $thread->id,
            'sender' => 'admin',
            'message' => $messageText,
        ]);

        
        $thread->touch();

        $messages = $thread->messages()->orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Reply sent successfully.',
            'data' => [
                'thread' => $thread,
                'messages' => $messages
            ]
        ]);
    }

    
    public function close(Request $request, $id)
    {
        $thread = SupportThread::find($id);

        if (!$thread) {
            return response()->json([
                'success' => false,
                'message' => 'Support thread not found.'
            ], 404);
        }

        $thread->status = 'resolved';
        $thread->save();

        return response()->json([
            'success' => true,
            'message' => 'Support thread marked as resolved.',
            'data' => $thread
        ]);
    }
}

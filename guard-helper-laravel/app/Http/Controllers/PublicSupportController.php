<?php

namespace App\Http\Controllers;

use App\Models\SupportThread;
use App\Models\SupportMessage;
use App\Models\AccessGrant;
use App\Models\Setting;
use App\Models\Notification;
use Illuminate\Http\Request;

class PublicSupportController extends Controller
{
    
    public function sendMessage(Request $request)
    {
        
        $supportEnabled = Setting::getValue('support_portal_enabled', '0') === '1';
        $supportMode = Setting::getValue('support_mode', 'built_in');

        if (!$supportEnabled || $supportMode !== 'built_in') {
            return response()->json([
                'success' => false,
                'message' => 'Built-in support chat is disabled.'
            ], 403);
        }

        
        $data = $request->validate([
            'thread_token' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'token' => 'nullable|string|max:255', 
        ]);

        $threadToken = trim($data['thread_token']);
        $messageText = trim($data['message']);
        $invitationToken = isset($data['token']) ? trim($data['token']) : null;

        
        $thread = SupportThread::where('token', $threadToken)->first();
        $isNewThread = false;

        if (!$thread) {
            $isNewThread = true;
            $accessGrantId = null;
            $email = null;
            $platform = null;

            if ($invitationToken) {
                $grant = AccessGrant::where('token', $invitationToken)->first();
                if ($grant) {
                    $accessGrantId = $grant->id;
                    $email = $grant->email;
                    $platform = $grant->platform;
                }
            }

            $thread = SupportThread::create([
                'token' => $threadToken,
                'access_grant_id' => $accessGrantId,
                'user_email' => $email,
                'platform' => $platform,
                'status' => 'open',
            ]);
        } else {
            
            if ($thread->status === 'closed' || $thread->status === 'resolved') {
                $thread->status = 'open';
                $thread->save();
            }
        }

        
        $message = SupportMessage::create([
            'support_thread_id' => $thread->id,
            'sender' => 'user',
            'message' => $messageText,
        ]);

        
        $thread->touch();

        
        $isFirstMessage = $thread->messages()->count() === 1;
        if ($isFirstMessage) {
            $emailContext = $thread->user_email ?: 'Guest';
            $platformContext = $thread->platform ?: 'General';
            
            Notification::create([
                'type' => 'support_alert',
                'title' => "Support Chat Started by {$emailContext} (" . strtoupper($platformContext) . ")",
                'message' => "First message: \"{$messageText}\"\n\nReply from your Admin Panel: /support-chats"
            ]);
        }

        
        $messages = $thread->messages()->orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => [
                'thread' => $thread,
                'messages' => $messages
            ]
        ]);
    }

    
    public function getThread(Request $request, $threadToken)
    {
        $supportEnabled = Setting::getValue('support_portal_enabled', '0') === '1';
        $supportMode = Setting::getValue('support_mode', 'built_in');

        if (!$supportEnabled || $supportMode !== 'built_in') {
            return response()->json([
                'success' => false,
                'message' => 'Built-in support chat is disabled.'
            ], 403);
        }

        $thread = SupportThread::where('token', $threadToken)->first();

        if (!$thread) {
            return response()->json([
                'success' => true,
                'data' => [
                    'thread' => null,
                    'messages' => []
                ]
            ]);
        }

        $messages = $thread->messages()->orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'thread' => $thread,
                'messages' => $messages
            ]
        ]);
    }
}

<?php

namespace App\Observers;

use App\Models\Notification;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Channels\SmtpChannel;
use Illuminate\Support\Facades\Log;

class NotificationObserver
{
    
    public function created(Notification $notification): void
    {
        
        try {
            $telegram = new TelegramChannel();
            if ($telegram->isEnabled()) {
                $telegram->send($notification);
            }
        } catch (\Throwable $e) {
            Log::error('Error sending Telegram notification: ' . $e->getMessage(), [
                'notification_id' => $notification->id,
                'exception' => $e
            ]);
        }

        
        try {
            $smtp = new SmtpChannel();
            if ($smtp->isEnabled()) {
                $smtp->send($notification);
            }
        } catch (\Throwable $e) {
            Log::error('Error sending SMTP notification: ' . $e->getMessage(), [
                'notification_id' => $notification->id,
                'exception' => $e
            ]);
        }
    }
}

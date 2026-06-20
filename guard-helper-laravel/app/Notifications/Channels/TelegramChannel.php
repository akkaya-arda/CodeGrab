<?php

namespace App\Notifications\Channels;

use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class TelegramChannel implements NotificationChannelInterface
{
    public function isEnabled(): bool
    {
        $enabled = Setting::getValue('telegram_enabled');
        return $enabled === '1' || $enabled === 'true';
    }

    public function send(Notification $notification): void
    {
        $token = Setting::getValue('telegram_bot_token');
        $chatId = Setting::getValue('telegram_chat_id');

        if (!$token || !$chatId) {
            return;
        }

        $message = "⚠️ <b>System Notification Alert</b>\n\n"
            . "<b>Type:</b> " . htmlspecialchars($notification->type) . "\n"
            . "<b>Title:</b> " . htmlspecialchars($notification->title) . "\n"
            . "<b>Details:</b>\n<code>" . htmlspecialchars($notification->message) . "</code>";

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);
    }
}

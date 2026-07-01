<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private ?string $token;

    public function __construct()
    {
        $this->token = \App\Models\Setting::getValue('telegram_bot_token');
    }

    public function sendMessage($chatId, string $text, ?array $replyMarkup = null): bool
    {
        if (!$this->token) {
            return false;
        }

        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ];

            if ($replyMarkup) {
                $payload['reply_markup'] = $replyMarkup;
            }

            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", $payload);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("[TelegramService] sendMessage failed: " . $e->getMessage());
            return false;
        }
    }

    public function editMessageText($chatId, int $messageId, string $text, ?array $replyMarkup = null): bool
    {
        if (!$this->token) {
            return false;
        }

        try {
            $payload = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ];

            if ($replyMarkup) {
                $payload['reply_markup'] = $replyMarkup;
            }

            $response = Http::post("https://api.telegram.org/bot{$this->token}/editMessageText", $payload);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("[TelegramService] editMessageText failed: " . $e->getMessage());
            return false;
        }
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): bool
    {
        if (!$this->token) {
            return false;
        }

        try {
            $payload = [
                'callback_query_id' => $callbackQueryId
            ];

            if ($text) {
                $payload['text'] = $text;
                $payload['show_alert'] = $showAlert;
            }

            $response = Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", $payload);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("[TelegramService] answerCallbackQuery failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendDocument($chatId, string $content, string $filename, string $caption, $replyToMessageId = null): bool
    {
        if (!$this->token) {
            return false;
        }

        try {
            $response = Http::attach('document', $content, $filename)
                ->post("https://api.telegram.org/bot{$this->token}/sendDocument", [
                    'chat_id' => $chatId,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                    'reply_to_message_id' => $replyToMessageId
                ]);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("[TelegramService] sendDocument failed: " . $e->getMessage());
            return false;
        }
    }
}

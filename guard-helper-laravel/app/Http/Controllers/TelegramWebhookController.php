<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\Telegram\TelegramService;
use App\Services\Telegram\TelegramCommandHandler;
use App\Services\Telegram\TelegramMenuHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    private TelegramService $telegramService;
    private TelegramCommandHandler $commandHandler;
    private TelegramMenuHandler $menuHandler;

    public function __construct()
    {
        $this->telegramService = new TelegramService();
        $this->commandHandler = new TelegramCommandHandler($this->telegramService);
        $this->menuHandler = new TelegramMenuHandler($this->telegramService);
    }

    public function handle(Request $request)
    {
        $chatId = $request->input('message.chat.id') ?? $request->input('callback_query.message.chat.id');
        $botToken = Setting::getValue('telegram_bot_token');

        if (!$botToken || !$chatId) {
            return response()->json(['success' => true]);
        }

        $configuredChatId = Setting::getValue('telegram_chat_id');
        if (empty($configuredChatId) || trim($configuredChatId) !== trim((string)$chatId)) {
            Log::warning("[Telegram Webhook] Unauthorized chat ID: {$chatId}");
            $this->telegramService->sendMessage($chatId, "Access denied. Chat ID <b>" . htmlspecialchars($chatId) . "</b> is not authorized in Guard Helper settings.");
            return response()->json(['success' => true]);
        }

        $callbackQuery = $request->input('callback_query');
        if ($callbackQuery) {
            $callbackId = $callbackQuery['id'];
            $data = $callbackQuery['data'] ?? '';
            $messageId = $callbackQuery['message']['message_id'] ?? 0;

            if ($data && $messageId) {
                $this->menuHandler->handle($chatId, $messageId, $callbackId, $data);
            }

            return response()->json(['success' => true]);
        }

        $text = $request->input('message.text');
        if ($text) {
            $this->commandHandler->handle($chatId, $text);
        }

        return response()->json(['success' => true]);
    }
}

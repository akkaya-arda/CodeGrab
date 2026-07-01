<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::setValue('telegram_bot_token', '123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ');
        Setting::setValue('telegram_chat_id', '987654321');
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response(['ok' => true], 200)
        ]);
    }

    public function test_unauthorized_chat_id_is_blocked(): void
    {
        $response = $this->postJson('/api/webhook/telegram/message', [
            'message' => [
                'chat' => ['id' => 111111111],
                'text' => '/start',
                'message_id' => 1
            ]
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bot123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ/sendMessage'
                && str_contains($request['text'], 'Access denied');
        });
    }

    public function test_start_command_returns_main_menu(): void
    {
        $response = $this->postJson('/api/webhook/telegram/message', [
            'message' => [
                'chat' => ['id' => 987654321],
                'text' => '/start',
                'message_id' => 2
            ]
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bot123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ/sendMessage'
                && str_contains($request['text'], 'CodeGrab Admin Dashboard');
        });
    }

    public function test_statistics_callback_query_is_handled(): void
    {
        $response = $this->postJson('/api/webhook/telegram/message', [
            'callback_query' => [
                'id' => 'cb_stats_123',
                'data' => 'menu:stats',
                'message' => [
                    'chat' => ['id' => 987654321],
                    'message_id' => 101
                ]
            ]
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bot123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ/answerCallbackQuery'
                && $request['callback_query_id'] === 'cb_stats_123';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bot123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ/editMessageText'
                && $request['message_id'] === 101
                && str_contains($request['text'], 'System Statistics');
        });
    }

    public function test_settings_callback_query_is_handled(): void
    {
        $response = $this->postJson('/api/webhook/telegram/message', [
            'callback_query' => [
                'id' => 'cb_settings_123',
                'data' => 'menu:settings',
                'message' => [
                    'chat' => ['id' => 987654321],
                    'message_id' => 102
                ]
            ]
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bot123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ/answerCallbackQuery'
                && $request['callback_query_id'] === 'cb_settings_123';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bot123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ/editMessageText'
                && $request['message_id'] === 102
                && str_contains($request['text'], 'Settings Dashboard');
        });
    }
}

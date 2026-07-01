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

    public function test_bulk_generation_flow_is_handled(): void
    {
        $bundle = \App\Models\AccountBundle::create([
            'name' => 'Test Bundle',
            'email' => 'bundle@test.com',
            'platform' => 'Steam',
            'password' => 'secret123',
            'is_active' => true
        ]);

        $response = $this->postJson('/api/webhook/telegram/message', [
            'callback_query' => [
                'id' => 'cb_bulk_1',
                'data' => "bulk_bu:{$bundle->id}",
                'message' => [
                    'chat' => ['id' => 987654321],
                    'message_id' => 201
                ]
            ]
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bot123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ/editMessageText'
                && $request['message_id'] === 201
                && str_contains($request['text'], 'quantity to generate');
        });

        $response = $this->postJson('/api/webhook/telegram/message', [
            'callback_query' => [
                'id' => 'cb_bulk_2',
                'data' => "bulk_li:{$bundle->id}:5:1d:unlim",
                'message' => [
                    'chat' => ['id' => 987654321],
                    'message_id' => 202
                ]
            ]
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bot123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ/editMessageText'
                && $request['message_id'] === 202
                && str_contains($request['text'], 'Bulk Access Tokens Generated');
        });

        $this->assertEquals(5, \App\Models\AccessGrant::where('account_bundle_id', $bundle->id)->count());
    }
}

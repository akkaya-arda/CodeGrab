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

    public function test_bundle_crud_management_flows_are_handled(): void
    {
        $bundle = \App\Models\AccountBundle::create([
            'name' => 'To Be Managed',
            'email' => 'manage@test.com',
            'platform' => 'Steam',
            'password' => 'secret123',
            'is_active' => true
        ]);

        $response = $this->postJson('/api/webhook/telegram/message', [
            'callback_query' => [
                'id' => 'cb_list',
                'data' => 'menu:manage_bundles',
                'message' => [
                    'chat' => ['id' => 987654321],
                    'message_id' => 301
                ]
            ]
        ]);
        $response->assertStatus(200);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'editMessageText')
                && str_contains($request['text'] ?? '', 'Account Bundle Manager');
        });

        $response = $this->postJson('/api/webhook/telegram/message', [
            'callback_query' => [
                'id' => 'cb_view',
                'data' => "bundle_view:{$bundle->id}",
                'message' => [
                    'chat' => ['id' => 987654321],
                    'message_id' => 302
                ]
            ]
        ]);
        $response->assertStatus(200);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'editMessageText')
                && str_contains($request['text'] ?? '', 'Account Bundle Details')
                && str_contains($request['text'] ?? '', 'To Be Managed');
        });

        $response = $this->postJson('/api/webhook/telegram/message', [
            'callback_query' => [
                'id' => 'cb_toggle',
                'data' => "bundle_toggle:{$bundle->id}",
                'message' => [
                    'chat' => ['id' => 987654321],
                    'message_id' => 303
                ]
            ]
        ]);
        $response->assertStatus(200);
        $bundle->refresh();
        $this->assertFalse($bundle->is_active);

        $response = $this->postJson('/api/webhook/telegram/message', [
            'message' => [
                'chat' => ['id' => 987654321],
                'text' => '/addbundle NewCreated | created@test.com | Steam | pass123 | steam_user',
                'message_id' => 304
            ]
        ]);
        $response->assertStatus(200);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'] ?? '', 'Account Bundle Added Successfully!')
                && str_contains($request['text'] ?? '', 'NewCreated')
                && str_contains($request['text'] ?? '', 'created@test.com');
        });
        $newBundle = \App\Models\AccountBundle::where('name', 'NewCreated')->first();
        $this->assertNotNull($newBundle);
        $this->assertEquals('steam_user', $newBundle->login_username);

        $response = $this->postJson('/api/webhook/telegram/message', [
            'callback_query' => [
                'id' => 'cb_delete',
                'data' => "bundle_delete:{$bundle->id}",
                'message' => [
                    'chat' => ['id' => 987654321],
                    'message_id' => 305
                ]
            ]
        ]);
        $response->assertStatus(200);
        $this->assertNull(\App\Models\AccountBundle::find($bundle->id));
    }

    public function test_telegram_web_app_endpoints_are_secure(): void
    {
        $botToken = '123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ';
        $user = ['id' => 987654321, 'first_name' => 'Admin'];
        $authDate = time();

        $params = [
            'auth_date' => $authDate,
            'user' => json_encode($user)
        ];
        ksort($params);
        $dataCheckStrings = [];
        foreach ($params as $key => $val) {
            $dataCheckStrings[] = "{$key}={$val}";
        }
        $dataCheckString = implode("\n", $dataCheckStrings);
        $secretKey = hash_hmac('sha256', $botToken, 'Webapps', true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);
        $validInitData = "auth_date={$authDate}&user=" . urlencode(json_encode($user)) . "&hash={$hash}";

        $response = $this->postJson('/api/telegram-api/platforms', [
            'init_data' => $validInitData
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'platforms']);

        $response = $this->postJson('/api/telegram-api/platforms', [
            'init_data' => $validInitData . 'tampered'
        ]);
        $response->assertStatus(403);
    }

    public function test_telegram_add_bundle_wizard(): void
    {
        Http::fake();
        $platform = \App\Models\PlatformGuardEmailFilter::create([
            'name' => 'Steam',
            'grab_regex' => '/Code: (.*)/i',
            'sender_pattern' => 'noreply@steampowered.com'
        ]);
        $gmail = \App\Models\GmailAccount::create([
            'id' => 9999,
            'email' => 'wizard@gmail.com',
            'access_token' => 'token',
            'access_token_expires_at' => now()->addHour()->toDateTimeString(),
            'refresh_token' => 'refresh',
            'refresh_token_expires_at' => now()->addDays(30)->toDateTimeString(),
            'is_active' => true
        ]);

        $response = $this->postJson('/api/webhook/telegram/message', [
            'callback_query' => [
                'id' => 'cb_add',
                'data' => 'bundle_add',
                'message' => [
                    'chat' => ['id' => 987654321],
                    'message_id' => 300
                ]
            ]
        ]);
        $response->assertStatus(200);

        $response = $this->postJson('/api/webhook/telegram/message', [
            'callback_query' => [
                'id' => 'cb_add_pl',
                'data' => "b_add_pl:{$platform->id}",
                'message' => [
                    'chat' => ['id' => 987654321],
                    'message_id' => 301
                ]
            ]
        ]);
        $response->assertStatus(200);

        $response = $this->postJson('/api/webhook/telegram/message', [
            'callback_query' => [
                'id' => 'cb_add_em',
                'data' => "b_add_em:{$platform->id}:gmail_{$gmail->id}",
                'message' => [
                    'chat' => ['id' => 987654321],
                    'message_id' => 302
                ]
            ]
        ]);
        $response->assertStatus(200);
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has('tg_add_bundle_987654321'));

        $response = $this->postJson('/api/webhook/telegram/message', [
            'message' => [
                'chat' => ['id' => 987654321],
                'text' => 'Wizard Steam | secretpass123 | wizard_user',
                'message_id' => 303
            ]
        ]);
        $response->assertStatus(200);

        $bundle = \App\Models\AccountBundle::where('name', 'Wizard Steam')->first();
        $this->assertNotNull($bundle);
        $this->assertEquals('wizard@gmail.com', $bundle->email);
        $this->assertEquals($platform->name, $bundle->platform);
        $this->assertEquals('wizard_user', $bundle->login_username);
        $this->assertEquals('secretpass123', $bundle->password);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'] ?? '', 'Account Bundle Added Successfully!');
        });
    }
}

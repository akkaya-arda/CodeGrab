<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SettingsNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_admin_can_retrieve_default_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/settings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.telegram_enabled', '0')
            ->assertJsonPath('data.smtp_port', 587)
            ->assertJsonPath('data.email_timeframe_limit', '1200');
    }

    public function test_admin_can_save_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/settings', [
                'telegram_enabled' => '1',
                'telegram_bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
                'telegram_chat_id' => '-100123456789',
                'smtp_enabled' => '1',
                'smtp_host' => 'smtp.testserver.com',
                'smtp_port' => 465,
                'smtp_encryption' => 'ssl',
                'smtp_username' => 'testuser',
                'smtp_password' => 'testpass',
                'smtp_from_address' => 'sender@testserver.com',
                'smtp_from_name' => 'Sender',
                'smtp_to_address' => 'recipient@testserver.com',
                'email_timeframe_limit' => 300,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals('1', Setting::getValue('telegram_enabled'));
        $this->assertEquals('testuser', Setting::getValue('smtp_username'));
        $this->assertEquals('testpass', Setting::getValue('smtp_password'));
        $this->assertEquals(300, Setting::getValue('email_timeframe_limit'));
    }

    public function test_notification_creation_triggers_observer_channels(): void
    {
        // 1. Enable channels in settings
        Setting::setValue('telegram_enabled', '1');
        Setting::setValue('telegram_bot_token', '123456:ABC');
        Setting::setValue('telegram_chat_id', '-100123456789');

        Setting::setValue('smtp_enabled', '1');
        Setting::setValue('smtp_host', 'smtp.testserver.com');
        Setting::setValue('smtp_port', 587);
        Setting::setValue('smtp_to_address', 'recipient@testserver.com');

        // 2. Mock Http and Mail facades
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response(['ok' => true], 200),
        ]);

        Mail::fake();

        // 3. Trigger notification creation
        Notification::create([
            'type' => 'auth_error',
            'title' => 'Test Notification Title',
            'message' => 'Test Notification Message Body',
        ]);

        // 4. Assert Http call was made
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                $request['chat_id'] === '-100123456789' &&
                str_contains($request['text'], 'Test Notification Title');
        });

        // 5. Assert Mail call was made
        Mail::assertSent(\App\Mail\NotificationMail::class, function ($mail) {
            return $mail->hasTo('recipient@testserver.com');
        });
    }

    public function test_admin_can_send_test_smtp_email(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/settings/test-smtp', [
                'smtp_host' => 'smtp.testserver.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'smtp_username' => 'testuser',
                'smtp_password' => 'testpass',
                'smtp_from_address' => 'sender@testserver.com',
                'smtp_from_name' => 'Sender',
                'smtp_to_address' => 'recipient@testserver.com',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Test email sent successfully!');

        Mail::assertSent(\App\Mail\NotificationMail::class, function ($mail) {
            return $mail->hasTo('recipient@testserver.com') && 
                $mail->notification->type === 'test_alert';
        });
    }

    public function test_telegram_webhook_cannot_be_activated_without_token(): void
    {
        Setting::setValue('telegram_bot_token', '');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/settings/telegram/webhook/toggle', [
                'activate' => true
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Telegram Bot Token is not configured. Please fill in the Bot Token first.');
    }

    public function test_telegram_webhook_cannot_be_activated_on_localhost(): void
    {
        Setting::setValue('telegram_bot_token', '123456:ABC');
        config(['app.url' => 'http://localhost:8000']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/settings/telegram/webhook/toggle', [
                'activate' => true
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Telegram Webhook cannot be activated on a local environment (localhost / 127.0.0.1) as Telegram requires a public, secure HTTPS endpoint to send callbacks.');
    }

    public function test_telegram_webhook_toggle_registration_success(): void
    {
        Setting::setValue('telegram_bot_token', '123456:ABC');
        config(['app.url' => 'https://guardhelper.com']);

        Http::fake([
            'https://api.telegram.org/bot123456:ABC/setWebhook' => Http::response(['ok' => true], 200),
            'https://api.telegram.org/bot123456:ABC/deleteWebhook' => Http::response(['ok' => true], 200),
        ]);

        // Activate webhook
        $response1 = $this->actingAs($this->admin)
            ->postJson('/api/admin/settings/telegram/webhook/toggle', [
                'activate' => true
            ]);

        $response1->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Telegram Webhook registered successfully! Webhook URL: https://guardhelper.com/api/webhook/telegram/message');

        $this->assertEquals('1', Setting::getValue('telegram_webhook_active'));

        // Deactivate webhook
        $response2 = $this->actingAs($this->admin)
            ->postJson('/api/admin/settings/telegram/webhook/toggle', [
                'activate' => false
            ]);

        $response2->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals('0', Setting::getValue('telegram_webhook_active'));
    }

    public function test_admin_can_retrieve_oauth_config_without_secrets(): void
    {
        config([
            'oauth.google.client_id' => 'google-id-123',
            'oauth.google.client_secret' => 'super-secret-google-key',
            'oauth.google.redirect_uri' => 'http://localhost/api/oauth/google/callback',
            'oauth.outlook.client_id' => 'outlook-id-456',
            'oauth.outlook.client_secret' => 'super-secret-outlook-key',
            'oauth.outlook.redirect_uri' => 'http://localhost/api/oauth/outlook/callback',
            'oauth.outlook.tenant' => 'consumers',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/settings/oauth-config');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.google_client_id', 'google-id-123')
            ->assertJsonPath('data.google_client_secret_exists', true)
            // Assert secret is NEVER exposed
            ->assertJsonMissing(['google_client_secret' => 'super-secret-google-key'])
            ->assertJsonMissing(['google_client_secret' => '********'])
            ->assertJsonPath('data.outlook_client_id', 'outlook-id-456')
            ->assertJsonPath('data.outlook_client_secret_exists', true)
            ->assertJsonMissing(['outlook_client_secret' => 'super-secret-outlook-key'])
            ->assertJsonMissing(['outlook_client_secret' => '********']);
    }

    public function test_admin_can_save_oauth_config_to_env_file(): void
    {
        $envPath = base_path('.env');
        $originalEnv = file_exists($envPath) ? file_get_contents($envPath) : null;

        try {
            $response = $this->actingAs($this->admin)
                ->postJson('/api/admin/settings/oauth-config', [
                    'google_client_id' => 'test-google-id',
                    'google_client_secret' => 'test-google-secret',
                    'google_redirect_uri' => 'http://localhost/test/google/callback',
                    'outlook_client_id' => 'test-outlook-id',
                    'outlook_client_secret' => 'test-outlook-secret',
                    'outlook_redirect_uri' => 'http://localhost/test/outlook/callback',
                    'outlook_tenant' => 'common',
                ]);

            $response->assertStatus(200)
                ->assertJsonPath('success', true);

            // Assert it modified the env file
            $newEnvContent = file_get_contents($envPath);
            $this->assertStringContainsString('GOOGLE_OAUTH_CLIENT_ID=test-google-id', $newEnvContent);
            $this->assertStringContainsString('GOOGLE_OAUTH_CLIENT_SECRET=test-google-secret', $newEnvContent);
            $this->assertStringContainsString('GOOGLE_OAUTH_REDIRECT_URI=http://localhost/test/google/callback', $newEnvContent);
            $this->assertStringContainsString('OUTLOOK_OAUTH_CLIENT_ID=test-outlook-id', $newEnvContent);
            $this->assertStringContainsString('OUTLOOK_OAUTH_CLIENT_SECRET=test-outlook-secret', $newEnvContent);
            $this->assertStringContainsString('OUTLOOK_OAUTH_REDIRECT_URI=http://localhost/test/outlook/callback', $newEnvContent);
            $this->assertStringContainsString('OUTLOOK_OAUTH_TENANT=common', $newEnvContent);

            // Assert it did NOT write to database Setting table
            $this->assertNull(Setting::where('key', 'GOOGLE_OAUTH_CLIENT_ID')->first());
            $this->assertNull(Setting::where('key', 'GOOGLE_OAUTH_CLIENT_SECRET')->first());
            $this->assertNull(Setting::where('key', 'OUTLOOK_OAUTH_CLIENT_ID')->first());
            $this->assertNull(Setting::where('key', 'OUTLOOK_OAUTH_CLIENT_SECRET')->first());
        } finally {
            if ($originalEnv !== null) {
                file_put_contents($envPath, $originalEnv);
            } else {
                @unlink($envPath);
            }
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Models\AccessGrant;
use App\Models\PlatformGuardEmailFilter;
use App\Models\GmailAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessGrantTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PlatformGuardEmailFilter $platform;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->platform = PlatformGuardEmailFilter::create([
            'name' => 'Steam',
            'logo' => 'steam.png',
            'sender' => 'noreply@steampowered.com',
            'subject' => 'Steam Guard',
            'regex' => '/is: ([A-Z0-9]{5})/'
        ]);

        // Configure default settings
        Setting::updateOrCreate(['key' => 'frontend_url'], ['value' => 'http://localhost:4200']);
        Setting::updateOrCreate(['key' => 'webhook_secret_key'], ['value' => 'super_secret_key_123']);
        Setting::updateOrCreate(['key' => 'public_access_portal_enabled'], ['value' => '0']);
    }

    public function test_admin_can_list_access_grants(): void
    {
        AccessGrant::create([
            'token' => 'tok_1',
            'email' => 'user@test.com',
            'platform' => 'Steam',
            'limit' => 10,
            'uses' => 2,
            'is_active' => true
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/access-grants');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_create_access_grant(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/access-grants', [
                'email' => 'new@test.com',
                'platform' => 'Steam',
                'limit' => 15
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'new@test.com')
            ->assertJsonPath('data.platform', 'Steam')
            ->assertJsonPath('data.limit', 15);

        $this->assertDatabaseHas('access_grants', [
            'email' => 'new@test.com',
            'platform' => 'Steam'
        ]);
    }

    public function test_admin_can_delete_access_grant(): void
    {
        $grant = AccessGrant::create([
            'token' => 'tok_delete',
            'email' => 'user@test.com',
            'platform' => 'Steam',
            'limit' => 10,
            'uses' => 0,
            'is_active' => true
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson('/api/admin/access-grants/' . $grant->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('access_grants', [
            'id' => $grant->id
        ]);
    }

    public function test_admin_can_get_registered_emails(): void
    {
        GmailAccount::create([
            'email' => 'gmail_registered@test.com',
            'is_active' => true,
            'access_token' => 'fake_access',
            'access_token_expires_at' => now()->addHour(),
            'refresh_token' => 'fake_refresh',
            'refresh_token_expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/access-grants/emails');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['gmail_registered@test.com']);
    }

    public function test_public_can_verify_valid_token(): void
    {
        $grant = AccessGrant::create([
            'token' => 'tok_valid_test',
            'email' => 'user@test.com',
            'platform' => 'Steam',
            'limit' => 10,
            'uses' => 3,
            'is_active' => true
        ]);

        $response = $this->getJson('/api/public/access-grant?token=tok_valid_test');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'user@test.com')
            ->assertJsonPath('data.platform', 'Steam')
            ->assertJsonPath('data.remaining', 7);
    }

    public function test_public_verify_censors_email_when_hide_email_is_true(): void
    {
        $bundle = \App\Models\AccountBundle::create([
            'name' => 'Steam Shared Account',
            'email' => 'bundle-real-email@test.com',
            'login_username' => 'steam_login_user@test.com',
            'password' => 'secret123',
            'platform' => 'Steam',
            'hide_email' => true,
            'is_active' => true
        ]);

        $grant = AccessGrant::create([
            'token' => 'tok_hidden_email_test',
            'email' => 'bundle-real-email@test.com',
            'platform' => 'Steam',
            'limit' => 10,
            'uses' => 0,
            'is_active' => true,
            'hide_email' => true,
            'account_bundle_id' => $bundle->id,
        ]);

        $response = $this->getJson('/api/public/access-grant?token=tok_hidden_email_test');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'b***l@test.com')
            ->assertJsonPath('data.hide_email', true)
            ->assertJsonPath('data.account_bundle.email', 'b***l@test.com')
            ->assertJsonPath('data.account_bundle.login_username', 'steam_login_user@test.com');
    }

    public function test_public_verification_fails_for_invalid_or_expired_token(): void
    {
        // 1. Non-existent token
        $this->getJson('/api/public/access-grant?token=invalid_tok')
            ->assertStatus(404);

        // 2. Inactive token
        $inactive = AccessGrant::create([
            'token' => 'tok_inactive',
            'email' => 'user@test.com',
            'platform' => 'Steam',
            'limit' => 10,
            'uses' => 0,
            'is_active' => false
        ]);
        $this->getJson('/api/public/access-grant?token=tok_inactive')
            ->assertStatus(403);

        // 3. Limit reached token
        $limitReached = AccessGrant::create([
            'token' => 'tok_limit',
            'email' => 'user@test.com',
            'platform' => 'Steam',
            'limit' => 5,
            'uses' => 5,
            'is_active' => true
        ]);
        $this->getJson('/api/public/access-grant?token=tok_limit')
            ->assertStatus(403);
    }

    public function test_external_webhook_generation_via_hmac_signature(): void
    {
        $payload = [
            'email' => 'webhook_user@test.com',
            'platform' => 'Steam',
            'limit' => 30
        ];
        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, 'super_secret_key_123');

        $response = $this->postJson('/api/webhook/generate-access', $payload, [
            'X-Webhook-Signature' => $signature
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token', 'access_url']);

        $this->assertDatabaseHas('access_grants', [
            'email' => 'webhook_user@test.com',
            'limit' => 30
        ]);
    }

    public function test_external_webhook_generation_via_static_secret(): void
    {
        $response = $this->postJson('/api/webhook/generate-access', [
            'email' => 'webhook_user2@test.com',
            'platform' => 'Steam',
            'limit' => 10
        ], [
            'X-Webhook-Secret' => 'super_secret_key_123'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_external_webhook_fails_with_invalid_credentials(): void
    {
        $this->postJson('/api/webhook/generate-access', [
            'email' => 'user@test.com',
            'platform' => 'Steam'
        ], [
            'X-Webhook-Secret' => 'wrong_secret'
        ])->assertStatus(401);
    }

    public function test_public_fetch_requires_token_when_portal_is_disabled(): void
    {
        // Settings has public_access_portal_enabled = 0
        $this->postJson('/api/public/fetch-code', [
            'email' => 'user@test.com',
            'platform' => 'Steam'
        ])->assertStatus(403);
    }

    public function test_public_fetch_succeeds_with_token_even_when_portal_is_disabled(): void
    {
        // 1. Register Gmail account to own the email
        GmailAccount::create([
            'email' => 'user@test.com',
            'is_active' => true,
            'access_token' => 'fake_access',
            'access_token_expires_at' => now()->addHour(),
            'refresh_token' => 'fake_refresh',
            'refresh_token_expires_at' => now()->addYear(),
        ]);

        // Mock GmailService so we don't hit external API
        $this->mock(\App\Infrastructure\Google\Services\GmailService::class, function ($mock) {
            $mock->shouldReceive('findCodeInLatestEmail')
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => '12345',
                    'date' => '2026-05-27 00:00:00'
                ]);
        });

        $grant = AccessGrant::create([
            'token' => 'tok_fetch_test',
            'email' => 'user@test.com',
            'platform' => 'Steam',
            'limit' => 10,
            'uses' => 0,
            'is_active' => true
        ]);

        $response = $this->postJson('/api/public/fetch-code', [
            'token' => 'tok_fetch_test'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', '12345');

        $this->assertEquals(1, $grant->fresh()->uses);
    }

    public function test_public_fetch_fails_when_code_email_is_outdated(): void
    {
        config(['guard.timeframe_limit' => 180]);

        GmailAccount::create([
            'email' => 'user_outdated@test.com',
            'is_active' => true,
            'access_token' => 'fake_access',
            'access_token_expires_at' => now()->addHour(),
            'refresh_token' => 'fake_refresh',
            'refresh_token_expires_at' => now()->addYear(),
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Steam',
            'regex' => '/\b[A-Z0-9]{5}\b/',
            'logo' => 'steam.png',
            'sender' => 'noreply@steampowered.com',
            'subject' => 'Steam Guard Code',
        ]);

        $this->mock(\App\Infrastructure\Google\Services\GmailService::class, function ($mock) {
            $mock->shouldReceive('findCodeInLatestEmail')
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => '54321',
                    'date' => now()->subMinutes(5)->toIso8601String()
                ]);
        });

        $grant = AccessGrant::create([
            'token' => 'tok_outdated_test',
            'email' => 'user_outdated@test.com',
            'platform' => 'Steam',
            'limit' => 10,
            'uses' => 0,
            'is_active' => true
        ]);

        // We pass the custom header 'X-Test-Time-Constraint' => true to enable the time check in test env
        $response = $this->postJson('/api/public/fetch-code', [
            'token' => 'tok_outdated_test'
        ], [
            'X-Test-Time-Constraint' => 'true'
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Security code is outdated. Please request a new code on the game client.');

        $this->assertEquals(0, $grant->fresh()->uses);
    }

    public function test_public_fetch_does_not_charge_again_for_same_code(): void
    {
        GmailAccount::create([
            'email' => 'user_double_fetch@test.com',
            'is_active' => true,
            'access_token' => 'fake_access',
            'access_token_expires_at' => now()->addHour(),
            'refresh_token' => 'fake_refresh',
            'refresh_token_expires_at' => now()->addYear(),
        ]);

        $this->mock(\App\Infrastructure\Google\Services\GmailService::class, function ($mock) {
            // First fetch: returns code '99999' (recent, e.g. 1 minute ago)
            // Second fetch: returns the same code '99999'
            $mock->shouldReceive('findCodeInLatestEmail')
                ->twice()
                ->andReturn([
                    'success' => true,
                    'data' => '99999',
                    'date' => now()->subMinute()->toIso8601String()
                ]);
        });

        $grant = AccessGrant::create([
            'token' => 'tok_double_fetch_test',
            'email' => 'user_double_fetch@test.com',
            'platform' => 'Steam',
            'limit' => 10,
            'uses' => 0,
            'is_active' => true
        ]);

        // First request: should succeed and charge
        $response1 = $this->postJson('/api/public/fetch-code', [
            'token' => 'tok_double_fetch_test'
        ]);
        $response1->assertStatus(200)->assertJsonPath('code', '99999');
        $this->assertEquals(1, $grant->fresh()->uses);

        // Second request: should succeed and NOT charge again
        $response2 = $this->postJson('/api/public/fetch-code', [
            'token' => 'tok_double_fetch_test'
        ]);
        $response2->assertStatus(200)->assertJsonPath('code', '99999');
        $this->assertEquals(1, $grant->fresh()->uses);
    }

    public function test_public_fetch_succeeds_unlimited_uses(): void
    {
        GmailAccount::create([
            'email' => 'user_unlimited@test.com',
            'is_active' => true,
            'access_token' => 'fake_access',
            'access_token_expires_at' => now()->addHour(),
            'refresh_token' => 'fake_refresh',
            'refresh_token_expires_at' => now()->addYear(),
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Steam',
            'regex' => '/\b[A-Z0-9]{5}\b/',
            'logo' => 'steam.png',
            'sender' => 'noreply@steampowered.com',
            'subject' => 'Steam Guard Code',
        ]);

        $this->mock(\App\Infrastructure\Google\Services\GmailService::class, function ($mock) {
            $mock->shouldReceive('findCodeInLatestEmail')
                ->twice()
                ->andReturn(
                    [
                        'success' => true,
                        'data' => 'ABCDE',
                        'date' => now()->toIso8601String()
                    ],
                    [
                        'success' => true,
                        'data' => 'FGHIJ',
                        'date' => now()->toIso8601String()
                    ]
                );
        });

        // Limit is null for unlimited
        $grant = AccessGrant::create([
            'token' => 'tok_unlimited_test',
            'email' => 'user_unlimited@test.com',
            'platform' => 'Steam',
            'limit' => null,
            'uses' => 0,
            'is_active' => true
        ]);

        // First fetch: should succeed and uses becomes 1, remains active
        $response1 = $this->postJson('/api/public/fetch-code', [
            'token' => 'tok_unlimited_test'
        ]);
        $response1->assertStatus(200)->assertJsonPath('code', 'ABCDE');
        $this->assertEquals(1, $grant->fresh()->uses);
        $this->assertTrue($grant->fresh()->is_active);

        // Second fetch: should succeed, uses becomes 2, remains active
        $response2 = $this->postJson('/api/public/fetch-code', [
            'token' => 'tok_unlimited_test'
        ]);
        $response2->assertStatus(200)->assertJsonPath('code', 'FGHIJ');
        $this->assertEquals(2, $grant->fresh()->uses);
        $this->assertTrue($grant->fresh()->is_active);
    }

    public function test_create_access_grant_with_expiration_validation(): void
    {
        $admin = \App\Models\User::factory()->create();
        $response = $this->actingAs($admin)->postJson('/api/admin/access-grants', [
            'email' => 'test@test.com',
            'platform' => 'Steam',
            'limit' => 5,
            'expires_at' => now()->addDay()->toIso8601String(),
        ]);
        $response->assertStatus(201);
        $this->assertNotNull($response->json('data.expires_at'));

        $response2 = $this->actingAs($admin)->postJson('/api/admin/access-grants', [
            'email' => 'test@test.com',
            'platform' => 'Steam',
            'limit' => 5,
            'expires_at' => now()->subDay()->toIso8601String(),
        ]);
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['expires_at']);
    }

    public function test_public_fetch_fails_when_access_token_is_expired(): void
    {
        GmailAccount::create([
            'email' => 'user_expired@test.com',
            'is_active' => true,
            'access_token' => 'fake_access',
            'access_token_expires_at' => now()->addHour(),
            'refresh_token' => 'fake_refresh',
            'refresh_token_expires_at' => now()->addYear(),
        ]);

        AccessGrant::create([
            'token' => 'tok_expired_test',
            'email' => 'user_expired@test.com',
            'platform' => 'Steam',
            'limit' => 5,
            'uses' => 0,
            'is_active' => true,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/api/public/fetch-code', [
            'token' => 'tok_expired_test'
        ]);
        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This access link has expired.');
    }

    public function test_public_verify_fails_when_access_token_is_expired(): void
    {
        AccessGrant::create([
            'token' => 'tok_verify_expired',
            'email' => 'user_expired@test.com',
            'platform' => 'Steam',
            'limit' => 5,
            'uses' => 0,
            'is_active' => true,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->getJson('/api/public/access-grant?token=tok_verify_expired');
        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This invitation link has expired.');
    }
}


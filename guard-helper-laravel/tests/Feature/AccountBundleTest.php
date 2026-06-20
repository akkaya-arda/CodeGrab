<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Models\AccessGrant;
use App\Models\AccountBundle;
use App\Models\PlatformGuardEmailFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccountBundleTest extends TestCase
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

        Setting::updateOrCreate(['key' => 'frontend_url'], ['value' => 'http://localhost:4200']);
        Setting::updateOrCreate(['key' => 'webhook_secret_key'], ['value' => 'super_secret_key_123']);
        Setting::updateOrCreate(['key' => 'telegram_chat_id'], ['value' => '987654321']);
        Setting::updateOrCreate(['key' => 'telegram_bot_token'], ['value' => 'fake_bot_token']);
    }

    public function test_admin_can_manage_account_bundles(): void
    {
        // 1. Create bundle
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/account-bundles', [
                'name' => 'Steam Level 20',
                'email' => 'arda@test.com',
                'login_username' => 'my_steam_login',
                'platform' => 'Steam',
                'password' => 'secret_password_123',
                'is_active' => true
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Steam Level 20')
            ->assertJsonPath('data.login_username', 'my_steam_login');

        $this->assertDatabaseHas('account_bundles', [
            'name' => 'Steam Level 20',
            'email' => 'arda@test.com',
            'login_username' => 'my_steam_login',
            'platform' => 'Steam'
        ]);

        $bundleId = $response->json('data.id');

        // 2. List bundles
        $listResponse = $this->actingAs($this->admin)
            ->getJson('/api/admin/account-bundles');
        
        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // 3. Update bundle
        $updateResponse = $this->actingAs($this->admin)
            ->putJson('/api/admin/account-bundles/' . $bundleId, [
                'name' => 'Steam Level 20 Updated',
                'email' => 'arda@test.com',
                'login_username' => 'my_steam_login_updated',
                'platform' => 'Steam',
                'password' => '', // keep existing password
                'is_active' => false
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Steam Level 20 Updated')
            ->assertJsonPath('data.login_username', 'my_steam_login_updated');
        
        $bundle = AccountBundle::find($bundleId);
        $this->assertEquals('secret_password_123', $bundle->password); // Decrypt check works

        // 4. Delete bundle
        $deleteResponse = $this->actingAs($this->admin)
            ->deleteJson('/api/admin/account-bundles/' . $bundleId);

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('account_bundles', ['id' => $bundleId]);
    }

    public function test_admin_can_bulk_generate_access_tokens(): void
    {
        $bundle = AccountBundle::create([
            'name' => 'Ubisoft Pack',
            'email' => 'ubisoft@test.com',
            'platform' => 'Steam',
            'password' => 'ubiPass1',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/access-grants/bulk', [
                'account_bundle_id' => $bundle->id,
                'quantity' => 15,
                'limit' => 5,
                'expires_at' => now()->addDays(5)->toIso8601String()
            ]);

        $response->assertStatus(201)
            ->assertJsonCount(15, 'data');

        $this->assertEquals(15, AccessGrant::where('account_bundle_id', $bundle->id)->count());
        $firstGrant = AccessGrant::where('account_bundle_id', $bundle->id)->first();
        $this->assertEquals('ubisoft@test.com', $firstGrant->email);
        $this->assertEquals(5, $firstGrant->limit);
        $this->assertNotNull($firstGrant->expires_at);
    }

    public function test_webhook_can_bulk_generate_access_tokens(): void
    {
        $bundle = AccountBundle::create([
            'name' => 'Epic Bundle',
            'email' => 'epic@test.com',
            'platform' => 'Steam',
            'password' => 'epicSecurePass',
            'is_active' => true
        ]);

        $payload = [
            'account_bundle_id' => $bundle->id,
            'quantity' => 5,
            'limit' => 10
        ];
        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, 'super_secret_key_123');

        $response = $this->postJson('/api/webhook/generate-access-bulk', $payload, [
            'X-Webhook-Signature' => $signature
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(5, 'tokens');

        $this->assertEquals(5, AccessGrant::where('account_bundle_id', $bundle->id)->count());
    }

    public function test_public_verify_returns_credentials_for_bundle(): void
    {
        $bundle = AccountBundle::create([
            'name' => 'Steam Starter',
            'email' => 'starter@test.com',
            'login_username' => 'my_starter_username',
            'platform' => 'Steam',
            'password' => 'startPasswordHere',
            'is_active' => true
        ]);

        $grant = AccessGrant::create([
            'account_bundle_id' => $bundle->id,
            'token' => 'tok_bundle_public',
            'email' => $bundle->email,
            'platform' => $bundle->platform,
            'limit' => 20,
            'uses' => 0,
            'is_active' => true
        ]);

        $response = $this->getJson('/api/public/access-grant?token=tok_bundle_public');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.account_bundle.email', 'starter@test.com')
            ->assertJsonPath('data.account_bundle.login_username', 'my_starter_username')
            ->assertJsonPath('data.account_bundle.password', 'startPasswordHere')
            ->assertJsonPath('data.account_bundle.platform', 'Steam');
    }

    public function test_telegram_webhook_bundles_command(): void
    {
        AccountBundle::create([
            'name' => 'TG Bundle 1',
            'email' => 'tg1@test.com',
            'platform' => 'Steam',
            'password' => 'pass1',
            'is_active' => true
        ]);

        $response = $this->postJson('/api/webhook/telegram/message', [
            'message' => [
                'chat' => ['id' => 987654321],
                'text' => '/bundles',
                'message_id' => 101
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_telegram_webhook_bulk_command(): void
    {
        $bundle = AccountBundle::create([
            'name' => 'TG Bulk Pack',
            'email' => 'tgbulk@test.com',
            'platform' => 'Steam',
            'password' => 'pass2',
            'is_active' => true
        ]);

        // Mock Telegram Document Upload API
        Http::fake([
            'https://api.telegram.org/botfake_bot_token/sendDocument' => Http::response(['ok' => true], 200),
            'https://api.telegram.org/botfake_bot_token/sendMessage' => Http::response(['ok' => true], 200),
        ]);

        // Bulk of 15 tokens (exceeds 10, should trigger sendDocument)
        $response = $this->postJson('/api/webhook/telegram/message', [
            'message' => [
                'chat' => ['id' => 987654321],
                'text' => "/bulk {$bundle->id} 15 5",
                'message_id' => 102
            ]
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendDocument') &&
                   $request->isMultipart();
        });
    }

    public function test_admin_can_bulk_generate_with_tag_and_prefix(): void
    {
        $bundle = AccountBundle::create([
            'name' => 'Tag and Prefix Pack',
            'email' => 'tagprefix@test.com',
            'platform' => 'Steam',
            'password' => 'pass123',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/access-grants/bulk', [
                'account_bundle_id' => $bundle->id,
                'quantity' => 5,
                'limit' => 2,
                'tag' => 'Summer-Batch',
                'prefix' => 'custom_pref_'
            ]);

        $response->assertStatus(201);
        $this->assertEquals(5, AccessGrant::where('tag', 'Summer-Batch')->count());
        $first = AccessGrant::where('tag', 'Summer-Batch')->first();
        $this->assertStringStartsWith('custom_pref_', $first->token);
    }

    public function test_admin_can_bulk_revoke_by_ids(): void
    {
        $bundle = AccountBundle::create([
            'name' => 'Revoke Pack',
            'email' => 'revoke@test.com',
            'platform' => 'Steam',
            'password' => 'pass123',
            'is_active' => true
        ]);

        $grant1 = AccessGrant::create([
            'account_bundle_id' => $bundle->id,
            'token' => 'tok_rev1',
            'email' => $bundle->email,
            'platform' => $bundle->platform,
            'is_active' => true
        ]);

        $grant2 = AccessGrant::create([
            'account_bundle_id' => $bundle->id,
            'token' => 'tok_rev2',
            'email' => $bundle->email,
            'platform' => $bundle->platform,
            'is_active' => true
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/access-grants/revoke-bulk', [
                'ids' => [$grant1->id, $grant2->id]
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('access_grants', ['id' => $grant1->id]);
        $this->assertDatabaseMissing('access_grants', ['id' => $grant2->id]);
    }

    public function test_admin_can_revoke_by_tag(): void
    {
        $bundle = AccountBundle::create([
            'name' => 'Revoke Tag Pack',
            'email' => 'revoketag@test.com',
            'platform' => 'Steam',
            'password' => 'pass123',
            'is_active' => true
        ]);

        AccessGrant::create([
            'account_bundle_id' => $bundle->id,
            'token' => 'tok_tag1',
            'email' => $bundle->email,
            'platform' => $bundle->platform,
            'tag' => 'Promo-Tag',
            'is_active' => true
        ]);

        AccessGrant::create([
            'account_bundle_id' => $bundle->id,
            'token' => 'tok_tag2',
            'email' => $bundle->email,
            'platform' => $bundle->platform,
            'tag' => 'Promo-Tag',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/access-grants/revoke-tag', [
                'tag' => 'Promo-Tag'
            ]);

        $response->assertStatus(200);
        $this->assertEquals(0, AccessGrant::where('tag', 'Promo-Tag')->count());
    }

    public function test_admin_can_retrieve_unique_tags(): void
    {
        $bundle = AccountBundle::create([
            'name' => 'Tags List Pack',
            'email' => 'tagslist@test.com',
            'platform' => 'Steam',
            'password' => 'pass123',
            'is_active' => true
        ]);

        AccessGrant::create([
            'account_bundle_id' => $bundle->id,
            'token' => 'tok_t1',
            'email' => $bundle->email,
            'platform' => $bundle->platform,
            'tag' => 'Tag-A',
            'is_active' => true
        ]);

        AccessGrant::create([
            'account_bundle_id' => $bundle->id,
            'token' => 'tok_t2',
            'email' => $bundle->email,
            'platform' => $bundle->platform,
            'tag' => 'Tag-B',
            'is_active' => true
        ]);

        AccessGrant::create([
            'account_bundle_id' => $bundle->id,
            'token' => 'tok_t3',
            'email' => $bundle->email,
            'platform' => $bundle->platform,
            'tag' => 'Tag-A',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/access-grants/tags');

        $response->assertStatus(200);
        $tags = $response->json('data');
        $this->assertCount(2, $tags);
        $this->assertContains('Tag-A', $tags);
        $this->assertContains('Tag-B', $tags);
    }
}

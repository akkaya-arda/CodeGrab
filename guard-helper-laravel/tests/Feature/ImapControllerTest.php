<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ImapAccount;
use App\Models\PlatformGuardEmailFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ImapControllerTest extends TestCase
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

        \App\Models\Setting::setValue('public_access_portal_enabled', '1');
    }

    public function test_authenticated_admin_can_list_imap_accounts(): void
    {
        ImapAccount::create([
            'email' => 'imap-test@domain.com',
            'host' => 'imap.domain.com',
            'port' => 993,
            'encryption' => 'ssl',
            'password' => 'secretPassword',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/email/imap/get-accounts');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // Assert password field is hidden for security in listing API
        $response->assertJsonMissing(['password']);
    }

    public function test_imap_password_is_encrypted_in_database(): void
    {
        $rawPassword = 'secretPassword';
        $account = ImapAccount::create([
            'email' => 'imap-encrypt@domain.com',
            'host' => 'imap.domain.com',
            'port' => 993,
            'encryption' => 'ssl',
            'password' => $rawPassword,
            'is_active' => true,
        ]);

        // Assert DB stores encrypted text
        $this->assertDatabaseHas('imap_accounts', [
            'email' => 'imap-encrypt@domain.com'
        ]);

        // Query raw value from DB directly (bypass Eloquent attribute getter)
        $rawDbValue = \DB::table('imap_accounts')->where('email', 'imap-encrypt@domain.com')->first()->password;
        
        $this->assertNotEquals($rawPassword, $rawDbValue);
        $this->assertEquals($rawPassword, Crypt::decryptString($rawDbValue));
    }

    public function test_public_can_get_platforms(): void
    {
        PlatformGuardEmailFilter::create([
            'name' => 'Steam',
            'regex' => '/\b[A-Z0-9]{5}\b/',
            'logo' => 'steam.png',
            'sender' => 'noreply@steampowered.com',
            'subject' => 'Steam Guard Code',
        ]);

        $response = $this->getJson('/api/public/platforms');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_public_fetch_code_fails_for_unregistered_email(): void
    {
        $response = $this->postJson('/api/public/fetch-code', [
            'email' => 'unknown@domain.com',
            'platform' => 'Steam'
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This email address is not registered in our system.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\GmailAccount;
use App\Models\OutlookAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class OAuthTokenSecurityTest extends TestCase
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

    public function test_gmail_and_outlook_tokens_are_encrypted_in_database(): void
    {
        $rawAccess = 'secret_access_token_123';
        $rawRefresh = 'secret_refresh_token_456';

        // 1. Create Gmail account
        $gmailAcc = GmailAccount::create([
            'id' => '12345',
            'email' => 'gmail-secure@domain.com',
            'access_token' => $rawAccess,
            'access_token_expires_at' => now()->addHour()->toDateTimeString(),
            'refresh_token' => $rawRefresh,
            'refresh_token_expires_at' => now()->addDays(30)->toDateTimeString(),
            'is_active' => true,
        ]);

        // Assert retrieved model values are automatically decrypted
        $this->assertEquals($rawAccess, $gmailAcc->access_token);
        $this->assertEquals($rawRefresh, $gmailAcc->refresh_token);

        // Assert database stores encrypted versions
        $rawDbGmail = \DB::table('gmail_accounts')->where('email', 'gmail-secure@domain.com')->first();
        $this->assertNotEquals($rawAccess, $rawDbGmail->access_token);
        $this->assertNotEquals($rawRefresh, $rawDbGmail->refresh_token);
        $this->assertEquals($rawAccess, Crypt::decryptString($rawDbGmail->access_token));
        $this->assertEquals($rawRefresh, Crypt::decryptString($rawDbGmail->refresh_token));


        // 2. Create Outlook account
        $outlookAcc = OutlookAccount::create([
            'email' => 'outlook-secure@domain.com',
            'access_token' => $rawAccess,
            'access_token_expires_at' => now()->addHour()->toDateTimeString(),
            'refresh_token' => $rawRefresh,
            'refresh_token_expires_at' => now()->addDays(30)->toDateTimeString(),
            'is_active' => true,
            'app_password' => 'dummy_password',
        ]);

        // Assert retrieved model values are automatically decrypted
        $this->assertEquals($rawAccess, $outlookAcc->access_token);
        $this->assertEquals($rawRefresh, $outlookAcc->refresh_token);

        // Assert database stores encrypted versions
        $rawDbOutlook = \DB::table('outlook_accounts')->where('email', 'outlook-secure@domain.com')->first();
        $this->assertNotEquals($rawAccess, $rawDbOutlook->access_token);
        $this->assertNotEquals($rawRefresh, $rawDbOutlook->refresh_token);
        $this->assertEquals($rawAccess, Crypt::decryptString($rawDbOutlook->access_token));
        $this->assertEquals($rawRefresh, Crypt::decryptString($rawDbOutlook->refresh_token));
    }

    public function test_existing_unencrypted_tokens_fallback_gracefully(): void
    {
        $rawAccess = 'plain_text_access_token_789';
        $rawRefresh = 'plain_text_refresh_token_abc';

        // Insert directly via Query Builder to bypass Eloquent encryption
        \DB::table('gmail_accounts')->insert([
            'id' => '67890',
            'email' => 'gmail-legacy@domain.com',
            'access_token' => $rawAccess,
            'access_token_expires_at' => now()->addHour()->toDateTimeString(),
            'refresh_token' => $rawRefresh,
            'refresh_token_expires_at' => now()->addDays(30)->toDateTimeString(),
            'is_active' => true,
        ]);

        // Retrieve via Eloquent
        $gmailAcc = GmailAccount::where('email', 'gmail-legacy@domain.com')->first();

        // It should fallback to plain text without throwing DecryptException
        $this->assertEquals($rawAccess, $gmailAcc->access_token);
        $this->assertEquals($rawRefresh, $gmailAcc->refresh_token);
    }

    public function test_gmail_and_outlook_apis_hide_tokens_from_json_responses(): void
    {
        // Setup records
        GmailAccount::create([
            'id' => '123',
            'email' => 'gmail-listing@domain.com',
            'access_token' => 'access123',
            'access_token_expires_at' => now()->addHour()->toDateTimeString(),
            'refresh_token' => 'refresh123',
            'refresh_token_expires_at' => now()->addDays(30)->toDateTimeString(),
            'is_active' => true,
        ]);

        OutlookAccount::create([
            'email' => 'outlook-listing@domain.com',
            'access_token' => 'access456',
            'access_token_expires_at' => now()->addHour()->toDateTimeString(),
            'refresh_token' => 'refresh456',
            'refresh_token_expires_at' => now()->addDays(30)->toDateTimeString(),
            'is_active' => true,
            'app_password' => 'dummy_password',
        ]);

        // Gmail list API
        $responseGmail = $this->actingAs($this->admin)
            ->getJson('/api/email/gmail/get-accounts');

        $responseGmail->assertStatus(200)
            ->assertJsonMissing(['access_token'])
            ->assertJsonMissing(['refresh_token']);

        // Outlook list API
        $responseOutlook = $this->actingAs($this->admin)
            ->getJson('/api/email/outlook/get-accounts');

        $responseOutlook->assertStatus(200)
            ->assertJsonMissing(['access_token'])
            ->assertJsonMissing(['refresh_token']);
    }
}

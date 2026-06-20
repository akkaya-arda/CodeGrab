<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PlatformGuardEmailFilter;
use App\Models\EmailPlatformAssignment;
use App\Models\GmailAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAssignmentTest extends TestCase
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

    public function test_admin_can_list_platforms(): void
    {
        PlatformGuardEmailFilter::create([
            'name' => 'Steam',
            'regex' => '/\b[A-Z0-9]{5}\b/u',
            'logo' => 'steam.png',
            'sender' => 'noreply@steampowered.com',
            'subject' => 'Steam Guard Code',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/platforms');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_create_custom_platform(): void
    {
        $payload = [
            'name' => 'Netflix',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/0/08/Netflix_2015_logo.svg',
            'sender' => 'info@account.netflix.com',
            'subject' => 'Netflix Code',
            'regex' => '/(?i:code).{1,50}?\b([0-9]{4})\b/su'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/platforms', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Netflix');

        $this->assertDatabaseHas('platforms', [
            'name' => 'Netflix',
            'sender' => 'info@account.netflix.com'
        ]);
    }

    public function test_admin_cannot_create_platform_with_invalid_regex(): void
    {
        $payload = [
            'name' => 'Netflix',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/0/08/Netflix_2015_logo.svg',
            'sender' => 'info@account.netflix.com',
            'subject' => 'Netflix Code',
            'regex' => '/invalid-regex-no-end-delimiter'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/platforms', $payload);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The provided regex pattern is invalid.');
    }

    public function test_admin_can_update_platform(): void
    {
        $platform = PlatformGuardEmailFilter::create([
            'name' => 'Old Name',
            'regex' => '/\b[0-9]{6}\b/u',
            'logo' => 'https://example.com/logo.svg',
            'sender' => 'old@sender.com',
        ]);

        $payload = [
            'name' => 'New Name',
            'logo' => 'https://example.com/logo-new.svg',
            'sender' => 'new@sender.com',
            'subject' => 'Updated Subject',
            'regex' => '/(?i:verification).{1,50}?\b([0-9]{6})\b/su'
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/platforms/{$platform->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('platforms', [
            'id' => $platform->id,
            'name' => 'New Name',
            'sender' => 'new@sender.com'
        ]);
    }

    public function test_admin_can_delete_platform(): void
    {
        $platform = PlatformGuardEmailFilter::create([
            'name' => 'Ubisoft',
            'regex' => '/\b[0-9]{6}\b/u',
            'logo' => 'https://example.com/logo.svg',
            'sender' => 'ubisoft@support.com',
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/platforms/{$platform->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('platforms', [
            'id' => $platform->id
        ]);
    }

    public function test_admin_can_test_regex(): void
    {
        $payload = [
            'regex' => '/(?i:code).{1,50}?\b([0-9]{6})\b/su',
            'body' => 'Here is your security verification code: 987654. Do not share.'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/platforms/test-regex', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('matched', true)
            ->assertJsonPath('code', '987654');
    }

    public function test_admin_can_save_and_retrieve_assignments(): void
    {
        $p1 = PlatformGuardEmailFilter::create(['name' => 'Steam', 'regex' => '/\b[A-Z0-9]{5}\b/u', 'sender' => 's1']);
        $p2 = PlatformGuardEmailFilter::create(['name' => 'Netflix', 'regex' => '/\b[0-9]{4}\b/u', 'sender' => 's2']);

        $email = 'gmail-assign@domain.com';

        // Set assignments
        $responseSet = $this->actingAs($this->admin)
            ->postJson('/api/admin/assignments', [
                'email' => $email,
                'platform_ids' => [$p1->id, $p2->id]
            ]);

        $responseSet->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('email_platform_assignments', ['email' => $email, 'platform_id' => $p1->id]);
        $this->assertDatabaseHas('email_platform_assignments', ['email' => $email, 'platform_id' => $p2->id]);

        // Get assignments
        $responseGet = $this->actingAs($this->admin)
            ->getJson("/api/admin/assignments/{$email}");

        $responseGet->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', [$p1->id, $p2->id]);
    }

    public function test_public_fetch_code_blocks_unassigned_platforms_and_allows_assigned(): void
    {
        // 1. Setup email accounts and platforms
        $gmailAcc = GmailAccount::create([
            'id' => '99999',
            'email' => 'gmail-assign-test@domain.com',
            'access_token' => 'token',
            'access_token_expires_at' => now()->addHour()->toDateTimeString(),
            'refresh_token' => 'refresh',
            'refresh_token_expires_at' => now()->addDays(30)->toDateTimeString(),
            'is_active' => true,
        ]);

        $steam = PlatformGuardEmailFilter::create(['name' => 'Steam', 'regex' => '/\b[A-Z0-9]{5}\b/u', 'sender' => 'noreply@steampowered.com']);
        $netflix = PlatformGuardEmailFilter::create(['name' => 'Netflix', 'regex' => '/\b[0-9]{4}\b/u', 'sender' => 'info@account.netflix.com']);

        // Assign only Steam to this Gmail account
        EmailPlatformAssignment::create([
            'email' => $gmailAcc->email,
            'platform_id' => $steam->id
        ]);

        // 2. Try fetching Netflix code (should be blocked with 403)
        $responseBlocked = $this->postJson('/api/public/fetch-code', [
            'email' => $gmailAcc->email,
            'platform' => 'Netflix'
        ]);

        $responseBlocked->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This email account is not authorized to fetch codes for Netflix.');

        // Verify a failed fetch log was recorded
        $this->assertDatabaseHas('guard_fetch_logs', [
            'email' => $gmailAcc->email,
            'platform' => 'Netflix',
            'status' => 'failed'
        ]);

        // Verify a system notification was created
        $this->assertDatabaseHas('notifications', [
            'type' => 'fetch_error',
            'title' => 'Interception Blocked: Netflix'
        ]);

        // 3. Try fetching Steam code (should bypass 403 assignment check and try fetching from Gmail Service)
        $responseAllowed = $this->postJson('/api/public/fetch-code', [
            'email' => $gmailAcc->email,
            'platform' => 'Steam'
        ]);

        // Since we are mocking nothing and there is no real Gmail connection, it will fail on GmailService,
        // but it should NOT return 403 Forbidden. It will return a 400 Refresh error or account error.
        $this->assertNotEquals(403, $responseAllowed->status());
    }

    public function test_public_get_platforms_filtering_by_email(): void
    {
        $gmailAcc = GmailAccount::create([
            'id' => '88888',
            'email' => 'gmail-filter-test@domain.com',
            'access_token' => 'token',
            'access_token_expires_at' => now()->addHour()->toDateTimeString(),
            'refresh_token' => 'refresh',
            'refresh_token_expires_at' => now()->addDays(30)->toDateTimeString(),
            'is_active' => true,
        ]);

        $steam = PlatformGuardEmailFilter::create(['name' => 'Steam', 'regex' => '/\b[A-Z0-9]{5}\b/u', 'sender' => 's1']);
        $netflix = PlatformGuardEmailFilter::create(['name' => 'Netflix', 'regex' => '/\b[0-9]{4}\b/u', 'sender' => 's2']);

        // 1. Without email: returns all
        $responseNoEmail = $this->getJson('/api/public/platforms');
        $responseNoEmail->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // 2. With non-existent email: returns 404
        $responseNonExistent = $this->getJson('/api/public/platforms?email=not-registered@domain.com');
        $responseNonExistent->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This email address is not registered in our system.');

        // 3. With registered email but no assignments: returns all (default)
        $responseNoAssign = $this->getJson("/api/public/platforms?email={$gmailAcc->email}");
        $responseNoAssign->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // 4. With registered email and Steam assigned: returns only Steam
        EmailPlatformAssignment::create([
            'email' => $gmailAcc->email,
            'platform_id' => $steam->id
        ]);

        $responseFilter = $this->getJson("/api/public/platforms?email={$gmailAcc->email}");
        $responseFilter->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Steam');
    }
}

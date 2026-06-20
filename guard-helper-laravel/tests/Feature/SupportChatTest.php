<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Models\SupportThread;
use App\Models\SupportMessage;
use App\Models\AccessGrant;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AccessGrant $grant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->grant = AccessGrant::create([
            'email' => 'guest@test.com',
            'platform' => 'Steam',
            'token' => 'invitation_token_123',
            'remaining_uses' => 5,
        ]);
    }

    public function test_guest_cannot_use_chat_if_support_portal_disabled(): void
    {
        Setting::setValue('support_portal_enabled', '0');
        Setting::setValue('support_mode', 'built_in');

        $response = $this->postJson('/api/public/support/messages', [
            'thread_token' => 'thread_abc',
            'message' => 'Hello support!',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Built-in support chat is disabled.');
    }

    public function test_guest_can_send_message_when_support_enabled(): void
    {
        Setting::setValue('support_portal_enabled', '1');
        Setting::setValue('support_mode', 'built_in');

        $response = $this->postJson('/api/public/support/messages', [
            'thread_token' => 'thread_abc',
            'message' => 'Hello support!',
            'token' => 'invitation_token_123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Message sent successfully.');

        $this->assertDatabaseHas('support_threads', [
            'token' => 'thread_abc',
            'user_email' => 'guest@test.com',
            'platform' => 'Steam',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('support_messages', [
            'sender' => 'user',
            'message' => 'Hello support!',
        ]);

        // First message must trigger support alert notification
        $this->assertDatabaseHas('notifications', [
            'type' => 'support_alert',
        ]);
    }

    public function test_subsequent_guest_messages_do_not_send_notification_spam(): void
    {
        Setting::setValue('support_portal_enabled', '1');
        Setting::setValue('support_mode', 'built_in');

        // First message
        $response = $this->postJson('/api/public/support/messages', [
            'thread_token' => 'thread_abc',
            'message' => 'First message',
        ]);

        $response->assertStatus(200);

        $this->assertEquals(1, Notification::where('type', 'support_alert')->count());

        // Second message
        $response2 = $this->postJson('/api/public/support/messages', [
            'thread_token' => 'thread_abc',
            'message' => 'Second message',
        ]);
        $response2->assertStatus(200);

        // Count should still be 1 (first message alert only)
        $this->assertEquals(1, Notification::where('type', 'support_alert')->count());
    }

    public function test_admin_can_list_threads(): void
    {
        $thread = SupportThread::create([
            'token' => 'thread_abc',
            'user_email' => 'guest@test.com',
            'platform' => 'Steam',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/support/threads');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.token', 'thread_abc');
    }

    public function test_admin_can_show_thread_with_messages(): void
    {
        $thread = SupportThread::create([
            'token' => 'thread_abc',
            'user_email' => 'guest@test.com',
            'platform' => 'Steam',
            'status' => 'open',
        ]);

        SupportMessage::create([
            'support_thread_id' => $thread->id,
            'sender' => 'user',
            'message' => 'Guest message',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/support/threads/{$thread->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.thread.token', 'thread_abc')
            ->assertJsonCount(1, 'data.messages')
            ->assertJsonPath('data.messages.0.message', 'Guest message');
    }

    public function test_admin_can_reply_and_resolve_thread(): void
    {
        $thread = SupportThread::create([
            'token' => 'thread_abc',
            'user_email' => 'guest@test.com',
            'platform' => 'Steam',
            'status' => 'open',
        ]);

        // Reply
        $responseReply = $this->actingAs($this->admin)
            ->postJson("/api/admin/support/threads/{$thread->id}/messages", [
                'message' => 'Admin reply',
            ]);

        $responseReply->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('support_messages', [
            'support_thread_id' => $thread->id,
            'sender' => 'admin',
            'message' => 'Admin reply',
        ]);

        // Resolve
        $responseResolve = $this->actingAs($this->admin)
            ->postJson("/api/admin/support/threads/{$thread->id}/close");

        $responseResolve->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals('resolved', $thread->fresh()->status);
    }
}

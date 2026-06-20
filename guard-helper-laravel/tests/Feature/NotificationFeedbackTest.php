<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Notification;
use App\Models\UserFeedback;
use App\Models\GuardFetchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFeedbackTest extends TestCase
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

    public function test_admin_can_retrieve_unread_notifications_count(): void
    {
        Notification::create([
            'type' => 'auth_error',
            'title' => 'Gmail Token Expired',
            'message' => 'Token has expired.',
            'is_read' => false
        ]);

        Notification::create([
            'type' => 'connection_error',
            'title' => 'IMAP Fail',
            'message' => 'IMAP host down.',
            'is_read' => true
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.count', 1);
    }

    public function test_admin_can_mark_notifications_as_read(): void
    {
        $notif = Notification::create([
            'type' => 'auth_error',
            'title' => 'Gmail Token Expired',
            'message' => 'Token has expired.',
            'is_read' => false
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/notifications/mark-as-read', [
                'id' => $notif->id
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(Notification::find($notif->id)->is_read);
    }

    public function test_public_can_submit_working_feedback(): void
    {
        $log = GuardFetchLog::create([
            'email' => 'user@domain.com',
            'account_type' => 'gmail',
            'platform' => 'Steam',
            'status' => 'success',
            'code' => 'ABCDE'
        ]);

        $response = $this->postJson('/api/public/feedback', [
            'email' => 'user@domain.com',
            'platform' => 'Steam',
            'is_working' => true,
            'comment' => 'Worked perfectly!',
            'log_id' => $log->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('user_feedbacks', [
            'email' => 'user@domain.com',
            'is_working' => true,
            'log_id' => $log->id
        ]);
    }

    public function test_negative_feedback_creates_system_notification(): void
    {
        $log = GuardFetchLog::create([
            'email' => 'user@domain.com',
            'account_type' => 'gmail',
            'platform' => 'Steam',
            'status' => 'success',
            'code' => 'ABCDE'
        ]);

        $response = $this->postJson('/api/public/feedback', [
            'email' => 'user@domain.com',
            'platform' => 'Steam',
            'is_working' => false,
            'comment' => 'Code was invalid!',
            'log_id' => $log->id
        ]);

        $response->assertStatus(200);

        // Assert notification created for admin review
        $this->assertDatabaseHas('notifications', [
            'type' => 'user_report',
            'title' => 'User reported broken code for Steam'
        ]);
    }
}

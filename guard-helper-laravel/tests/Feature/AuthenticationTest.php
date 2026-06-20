<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_profile_details()
    {
        $user = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'test-admin@guardhelper.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Admin Test',
                    'email' => 'test-admin@guardhelper.com',
                ]
            ]);
    }

    public function test_user_cannot_get_profile_without_authentication()
    {
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_update_profile_info_with_correct_password()
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old-email@guardhelper.com',
            'password' => Hash::make('correctpassword'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', [
            'name' => 'New Name',
            'email' => 'new-email@guardhelper.com',
            'current_password' => 'correctpassword',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Account settings updated successfully.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new-email@guardhelper.com',
        ]);
    }

    public function test_authenticated_user_cannot_update_profile_info_with_incorrect_password()
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old-email@guardhelper.com',
            'password' => Hash::make('correctpassword'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', [
            'name' => 'New Name',
            'email' => 'new-email@guardhelper.com',
            'current_password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The provided current password does not match our records.'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Old Name',
            'email' => 'old-email@guardhelper.com',
        ]);
    }

    public function test_authenticated_user_can_update_password()
    {
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@guardhelper.com',
            'password' => Hash::make('oldpassword'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', [
            'name' => 'Admin User',
            'email' => 'admin@guardhelper.com',
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_authenticated_user_cannot_update_password_with_mismatched_confirmation()
    {
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@guardhelper.com',
            'password' => Hash::make('oldpassword'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', [
            'name' => 'Admin User',
            'email' => 'admin@guardhelper.com',
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_reset_password_when_file_exists_and_email_matches()
    {
        $email = 'admin-reset@guardhelper.com';
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('originalpassword'),
        ]);

        $filePath = base_path('reset-password.txt');
        file_put_contents($filePath, $email);

        try {
            $response = $this->postJson('/api/auth/reset-password', [
                'email' => $email,
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Your password has been successfully reset to 12345678.'
                ]);

            $user->refresh();
            $this->assertTrue(Hash::check('12345678', $user->password));
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function test_user_cannot_reset_password_when_file_does_not_exist_or_email_mismatch()
    {
        $email = 'admin-reset-fail@guardhelper.com';
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('originalpassword'),
        ]);

        $filePath = base_path('reset-password.txt');
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // 1. File doesn't exist
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => $email,
        ]);
        $response->assertStatus(400);

        // 2. File exists but has wrong email
        file_put_contents($filePath, 'different-email@guardhelper.com');
        try {
            $response = $this->postJson('/api/auth/reset-password', [
                'email' => $email,
            ]);
            $response->assertStatus(400);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}

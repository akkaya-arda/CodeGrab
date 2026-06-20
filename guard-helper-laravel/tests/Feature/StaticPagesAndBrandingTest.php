<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Models\StaticPage;
use App\Models\AccessGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticPagesAndBrandingTest extends TestCase
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

    public function test_admin_can_save_branding_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/settings', [
                'system_name' => 'CyberGuard Portal',
                'theme_primary_color' => '#10b981',
                'theme_accent_color' => '#3b82f6',
                'system_slogan_title' => 'Secure Access Portal',
                'system_slogan_subtitle' => 'Get your codes instantly',
                'logo_enabled' => '0',
                'theme_font_family' => 'Outfit',
                'copyright_text' => 'Custom Copyright 2026',
                'hide_access_restricted_info' => '1',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals('CyberGuard Portal', Setting::getValue('system_name'));
        $this->assertEquals('#10b981', Setting::getValue('theme_primary_color'));
        $this->assertEquals('#3b82f6', Setting::getValue('theme_accent_color'));
        $this->assertEquals('Secure Access Portal', Setting::getValue('system_slogan_title'));
        $this->assertEquals('Get your codes instantly', Setting::getValue('system_slogan_subtitle'));
        $this->assertEquals('0', Setting::getValue('logo_enabled'));
        $this->assertEquals('Outfit', Setting::getValue('theme_font_family'));
        $this->assertEquals('Custom Copyright 2026', Setting::getValue('copyright_text'));
        $this->assertEquals('1', Setting::getValue('hide_access_restricted_info'));
    }

    public function test_admin_can_crud_static_pages(): void
    {
        // 1. Create (Store)
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/static-pages', [
                'title' => 'Terms of Service',
                'content' => '<p>These are the terms.</p>',
                'is_published' => true,
                'show_in_footer' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'title', 'slug', 'content']]);

        $pageId = $response->json('data.id');
        $this->assertDatabaseHas('static_pages', [
            'id' => $pageId,
            'title' => 'Terms of Service',
            'slug' => 'terms-of-service',
        ]);

        // 2. Read (Index)
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/static-pages');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // 3. Update
        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/static-pages/{$pageId}", [
                'title' => 'Updated Terms of Service',
                'slug' => 'terms-of-service',
                'content' => '<p>Updated content.</p>',
                'is_published' => true,
                'show_in_footer' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('static_pages', [
            'id' => $pageId,
            'title' => 'Updated Terms of Service',
            'show_in_footer' => false,
        ]);

        // 4. Delete
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/static-pages/{$pageId}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('static_pages', [
            'id' => $pageId,
        ]);
    }

    public function test_public_guest_can_list_and_view_pages(): void
    {
        $page1 = StaticPage::create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => 'Keep private.',
            'is_published' => true,
            'show_in_footer' => true,
        ]);

        $page2 = StaticPage::create([
            'title' => 'Internal Draft',
            'slug' => 'draft',
            'content' => 'Draft content.',
            'is_published' => false,
            'show_in_footer' => true,
        ]);

        // Public listing should only return published pages
        $response = $this->getJson('/api/public/static-pages');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'privacy-policy');

        // Public show page content
        $response = $this->getJson('/api/public/static-pages/privacy-policy');
        $response->assertStatus(200)
            ->assertJsonPath('data.content', 'Keep private.');

        // Draft page should return 404 for public guest
        $response = $this->getJson('/api/public/static-pages/draft');
        $response->assertStatus(404);
    }

    public function test_public_endpoints_return_custom_branding(): void
    {
        Setting::setValue('system_name', 'CyberGuard');
        Setting::setValue('theme_primary_color', '#22c55e');
        Setting::setValue('system_slogan_title', 'Cyber Grab');
        Setting::setValue('system_slogan_subtitle', 'Get 2FA now');
        Setting::setValue('logo_enabled', '0');
        Setting::setValue('theme_font_family', 'Cinzel');
        Setting::setValue('copyright_text', 'Custom Copyright Info');
        Setting::setValue('hide_access_restricted_info', '1');

        // Create active token grant
        $grant = AccessGrant::create([
            'email' => 'user@test.com',
            'platform' => 'Steam',
            'token' => 'inv_test_token',
            'limit' => 10,
            'uses' => 0,
            'is_active' => true,
        ]);

        // 1. Tokenless platform settings check
        $response = $this->getJson('/api/public/platforms');
        $response->assertStatus(200)
            ->assertJsonPath('system_name', 'CyberGuard')
            ->assertJsonPath('theme_primary_color', '#22c55e')
            ->assertJsonPath('system_slogan_title', 'Cyber Grab')
            ->assertJsonPath('system_slogan_subtitle', 'Get 2FA now')
            ->assertJsonPath('logo_enabled', false)
            ->assertJsonPath('theme_font_family', 'Cinzel')
            ->assertJsonPath('copyright_text', 'Custom Copyright Info')
            ->assertJsonPath('hide_access_restricted_info', true);

        // 2. Token verification check
        $response = $this->getJson('/api/public/access-grant?token=inv_test_token');
        $response->assertStatus(200)
            ->assertJsonPath('data.system_name', 'CyberGuard')
            ->assertJsonPath('data.theme_primary_color', '#22c55e')
            ->assertJsonPath('data.system_slogan_title', 'Cyber Grab')
            ->assertJsonPath('data.system_slogan_subtitle', 'Get 2FA now')
            ->assertJsonPath('data.logo_enabled', false)
            ->assertJsonPath('data.theme_font_family', 'Cinzel')
            ->assertJsonPath('data.copyright_text', 'Custom Copyright Info')
            ->assertJsonPath('data.hide_access_restricted_info', true);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    
    public function getSettings(Request $request)
    {
        $keys = [
            'telegram_enabled',
            'telegram_bot_token',
            'telegram_chat_id',
            'telegram_webhook_active',
            'smtp_enabled',
            'smtp_host',
            'smtp_port',
            'smtp_encryption',
            'smtp_username',
            'smtp_password',
            'smtp_from_address',
            'smtp_from_name',
            'smtp_to_address',
            'public_access_portal_enabled',
            'webhook_secret_key',
            'frontend_url',
            'support_portal_enabled',
            'support_mode',
            'support_custom_script',
            'system_name',
            'system_logo',
            'logo_enabled',
            'theme_primary_color',
            'theme_accent_color',
            'theme_font_family',
            'system_slogan_title',
            'system_slogan_subtitle',
            'copyright_text',
            'hide_access_restricted_info',
            'email_timeframe_limit',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::getValue($key, $this->getDefaultValue($key));
        }

        
        if (!empty($settings['smtp_password'])) {
            $settings['smtp_password'] = '********';
        }

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    
    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'telegram_enabled' => 'nullable|string',
            'telegram_bot_token' => 'nullable|string',
            'telegram_chat_id' => 'nullable|string',
            'telegram_webhook_active' => 'nullable|string',
            'smtp_enabled' => 'nullable|string',
            'smtp_host' => 'nullable|string',
            'smtp_port' => 'nullable|integer',
            'smtp_encryption' => 'nullable|string',
            'smtp_username' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_from_address' => 'nullable|string',
            'smtp_from_name' => 'nullable|string',
            'smtp_to_address' => 'nullable|string',
            'public_access_portal_enabled' => 'nullable|string',
            'webhook_secret_key' => 'nullable|string',
            'frontend_url' => 'nullable|string',
            'support_portal_enabled' => 'nullable|string',
            'support_mode' => 'nullable|string',
            'support_custom_script' => 'nullable|string',
            'system_name' => 'nullable|string',
            'system_logo' => 'nullable|string',
            'logo_enabled' => 'nullable|string',
            'theme_primary_color' => 'nullable|string',
            'theme_accent_color' => 'nullable|string',
            'theme_font_family' => 'nullable|string',
            'system_slogan_title' => 'nullable|string',
            'system_slogan_subtitle' => 'nullable|string',
            'copyright_text' => 'nullable|string',
            'hide_access_restricted_info' => 'nullable|string',
            'email_timeframe_limit' => 'nullable|integer',
        ]);

        foreach ($data as $key => $value) {
            
            if ($key === 'smtp_password' && $value === '********') {
                continue;
            }
            
            if (in_array($key, ['telegram_enabled', 'smtp_enabled', 'public_access_portal_enabled', 'telegram_webhook_active', 'support_portal_enabled', 'logo_enabled', 'hide_access_restricted_info'])) {
                $value = ($value === '1' || $value === 'true' || $value === true) ? '1' : '0';
            }
            Setting::setValue($key, $value ?? '');
        }

        return response()->json([
            'success' => true,
            'message' => 'System settings updated successfully.'
        ]);
    }

    
    public function testSmtp(Request $request)
    {
        $data = $request->validate([
            'smtp_host' => 'required|string',
            'smtp_port' => 'required|integer',
            'smtp_encryption' => 'nullable|string',
            'smtp_username' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_from_address' => 'required|string',
            'smtp_from_name' => 'nullable|string',
            'smtp_to_address' => 'required|string',
        ]);

        
        if ($data['smtp_password'] === '********') {
            $data['smtp_password'] = Setting::getValue('smtp_password');
        }

        try {
            
            \Illuminate\Support\Facades\Config::set('mail.mailers.test_smtp', [
                'transport' => 'smtp',
                'host' => $data['smtp_host'],
                'port' => (int) $data['smtp_port'],
                'encryption' => $data['smtp_encryption'] === 'none' ? null : $data['smtp_encryption'],
                'username' => $data['smtp_username'],
                'password' => $data['smtp_password'],
                'timeout' => 10, 
            ]);

            \Illuminate\Support\Facades\Config::set('mail.from.address', $data['smtp_from_address']);
            \Illuminate\Support\Facades\Config::set('mail.from.name', $data['smtp_from_name'] ?: 'Guard Helper Test');

            
            $notification = new \App\Models\Notification([
                'type' => 'test_alert',
                'title' => 'SMTP Test Connection Successful',
                'message' => 'Your SMTP credentials have been validated successfully and are ready for system alerts.',
            ]);

            \Illuminate\Support\Facades\Mail::mailer('test_smtp')
                ->to($data['smtp_to_address'])
                ->send(new \App\Mail\NotificationMail($notification));

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully!'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'SMTP Configuration Error: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function toggleTelegramWebhook(Request $request)
    {
        $request->validate([
            'activate' => 'required|boolean'
        ]);

        $activate = $request->input('activate');
        $botToken = Setting::getValue('telegram_bot_token');

        if (!$botToken) {
            return response()->json([
                'success' => false,
                'message' => 'Telegram Bot Token is not configured. Please fill in the Bot Token first.'
            ], 400);
        }

        
        $appUrl = config('app.url') ?: 'http://localhost:8000';
        $host = parse_url($appUrl, PHP_URL_HOST) ?: request()->getHost();

        $isLocalhost = ($host === 'localhost' || $host === '127.0.0.1' || \Illuminate\Support\Str::contains($host, 'localhost') || \Illuminate\Support\Str::contains($host, '127.0.0.1'));

        if ($activate && $isLocalhost) {
            return response()->json([
                'success' => false,
                'message' => 'Telegram Webhook cannot be activated on a local environment (localhost / 127.0.0.1) as Telegram requires a public, secure HTTPS endpoint to send callbacks.'
            ], 400);
        }

        if ($activate) {
            $webhookUrl = rtrim($appUrl, '/') . '/api/webhook/telegram/message';
            
            try {
                $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", [
                    'url' => $webhookUrl
                ]);

                if ($response->failed() || !$response->json('ok')) {
                    $description = $response->json('description') ?? 'Could not register Webhook with Telegram API.';
                    return response()->json([
                        'success' => false,
                        'message' => 'Telegram setWebhook Failed: ' . $description
                    ], 400);
                }

                Setting::setValue('telegram_webhook_active', '1');

                return response()->json([
                    'success' => true,
                    'message' => 'Telegram Webhook registered successfully! Webhook URL: ' . $webhookUrl
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reach Telegram API: ' . $e->getMessage()
                ], 500);
            }
        } else {
            try {
                $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/deleteWebhook");

                if ($response->failed() || !$response->json('ok')) {
                    $description = $response->json('description') ?? 'Could not remove Webhook with Telegram API.';
                    return response()->json([
                        'success' => false,
                        'message' => 'Telegram deleteWebhook Failed: ' . $description
                    ], 400);
                }

                Setting::setValue('telegram_webhook_active', '0');

                return response()->json([
                    'success' => true,
                    'message' => 'Telegram Webhook deactivated and unregistered successfully.'
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reach Telegram API: ' . $e->getMessage()
                ], 500);
            }
        }
    }

    private function getDefaultValue(string $key)
    {
        switch ($key) {
            case 'smtp_port':
                return 587;
            case 'smtp_encryption':
                return 'tls';
            case 'telegram_enabled':
            case 'smtp_enabled':
            case 'public_access_portal_enabled':
            case 'telegram_webhook_active':
            case 'support_portal_enabled':
                return '0';
            case 'support_mode':
                return 'built_in';
            case 'support_custom_script':
                return '';
            case 'system_name':
                return 'Raven';
            case 'system_logo':
                return '';
            case 'logo_enabled':
                return '1';
            case 'theme_primary_color':
                return '#4f46e5';
            case 'theme_accent_color':
                return '#6366f1';
            case 'theme_font_family':
                return 'Pacifico';
            case 'system_slogan_title':
                return 'Access Portal';
            case 'system_slogan_subtitle':
                return 'Retrieve your 2FA codes easily.';
            case 'copyright_text':
                return '';
            case 'hide_access_restricted_info':
                return '0';
            case 'email_timeframe_limit':
                return '1200';
            case 'webhook_secret_key':
                return \Illuminate\Support\Str::random(32);
            case 'frontend_url':
                return 'http://localhost:4200';
            default:
                return '';
        }
    }

    
    public function getOAuthConfig(Request $request)
    {
        $googleClientId = config('oauth.google.client_id') ?: env('GOOGLE_OAUTH_CLIENT_ID');
        $googleClientSecret = config('oauth.google.client_secret') ?: env('GOOGLE_OAUTH_CLIENT_SECRET');
        $googleRedirectUri = config('oauth.google.redirect_uri') ?: env('GOOGLE_OAUTH_REDIRECT_URI');

        $outlookTenant = config('oauth.outlook.tenant') ?: env('OUTLOOK_OAUTH_TENANT') ?: 'consumers';
        $outlookClientId = config('oauth.outlook.client_id') ?: env('OUTLOOK_OAUTH_CLIENT_ID');
        $outlookClientSecret = config('oauth.outlook.client_secret') ?: env('OUTLOOK_OAUTH_CLIENT_SECRET');
        $outlookRedirectUri = config('oauth.outlook.redirect_uri') ?: env('OUTLOOK_OAUTH_REDIRECT_URI');

        return response()->json([
            'success' => true,
            'data' => [
                'google_client_id' => $googleClientId ?: '',
                'google_client_secret_exists' => !empty($googleClientSecret),
                'google_redirect_uri' => $googleRedirectUri ?: url('/api/oauth/google/callback'),
                'google_is_configured' => !empty($googleClientId) && !empty($googleClientSecret),
                
                'outlook_tenant' => $outlookTenant,
                'outlook_client_id' => $outlookClientId ?: '',
                'outlook_client_secret_exists' => !empty($outlookClientSecret),
                'outlook_redirect_uri' => $outlookRedirectUri ?: url('/api/oauth/outlook/callback'),
                'outlook_is_configured' => !empty($outlookClientId) && !empty($outlookClientSecret),
            ]
        ]);
    }

    public function saveOAuthConfig(Request $request)
    {
        $data = $request->validate([
            'google_client_id' => 'nullable|string',
            'google_client_secret' => 'nullable|string',
            'google_redirect_uri' => 'nullable|string',
            'outlook_client_id' => 'nullable|string',
            'outlook_client_secret' => 'nullable|string',
            'outlook_redirect_uri' => 'nullable|string',
            'outlook_tenant' => 'nullable|string',
        ]);

        if (array_key_exists('google_client_id', $data)) {
            $this->updateEnvFile('GOOGLE_OAUTH_CLIENT_ID', $data['google_client_id'] ?? '');
        }
        if (isset($data['google_client_secret']) && $data['google_client_secret'] !== '') {
            $this->updateEnvFile('GOOGLE_OAUTH_CLIENT_SECRET', $data['google_client_secret']);
        }
        if (array_key_exists('google_redirect_uri', $data)) {
            $this->updateEnvFile('GOOGLE_OAUTH_REDIRECT_URI', $data['google_redirect_uri'] ?? '');
        }

        if (array_key_exists('outlook_client_id', $data)) {
            $this->updateEnvFile('OUTLOOK_OAUTH_CLIENT_ID', $data['outlook_client_id'] ?? '');
        }
        if (isset($data['outlook_client_secret']) && $data['outlook_client_secret'] !== '') {
            $this->updateEnvFile('OUTLOOK_OAUTH_CLIENT_SECRET', $data['outlook_client_secret']);
        }
        if (array_key_exists('outlook_redirect_uri', $data)) {
            $this->updateEnvFile('OUTLOOK_OAUTH_REDIRECT_URI', $data['outlook_redirect_uri'] ?? '');
        }
        if (array_key_exists('outlook_tenant', $data)) {
            $this->updateEnvFile('OUTLOOK_OAUTH_TENANT', $data['outlook_tenant'] ?? '');
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
        } catch (\Throwable $e) {
            // Ignore config clear errors
        }

        return response()->json([
            'success' => true,
            'message' => 'OAuth settings updated successfully.'
        ]);
    }

    private function updateEnvFile($key, $value)
    {
        $path = base_path('.env');

        if (!file_exists($path)) {
            if (file_exists(base_path('.env.example'))) {
                copy(base_path('.env.example'), $path);
            } else {
                touch($path);
            }
        }

        $content = file_get_contents($path);

        if (preg_match('/\s/', $value) || str_contains($value, '#') || str_contains($value, '$')) {
            $value = '"' . str_replace('"', '\\"', $value) . '"';
        }

        $quotedKey = preg_quote($key, '/');
        if (preg_match("/^(?:#\s*)?{$quotedKey}=/m", $content)) {
            $escapedValue = str_replace(['\\', '$'], ['\\\\', '\\$'], $value);
            $content = preg_replace("/^(?:#\s*)?{$quotedKey}=.*/m", "{$key}={$escapedValue}", $content);
        } else {
            $content .= "\n{$key}={$value}";
        }

        file_put_contents($path, $content);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
            
            $destinationPath = public_path('uploads');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $file->move($destinationPath, $filename);
            $logoPath = '/uploads/' . $filename;
            
            Setting::setValue('system_logo', $logoPath);

            return response()->json([
                'success' => true,
                'message' => 'Logo uploaded successfully.',
                'logo_url' => $logoPath
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No logo file provided.'
        ], 400);
    }
}

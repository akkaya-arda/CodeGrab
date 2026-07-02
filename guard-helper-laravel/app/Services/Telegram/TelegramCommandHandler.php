<?php

namespace App\Services\Telegram;

class TelegramCommandHandler
{
    public function __construct(private TelegramService $telegramService)
    {
    }

    public function handle(string $chatId, string $text, int $messageId = 0): void
    {
        $text = trim($text);

        if ($text === '/cancel' || $text === '/menu' || $text === '/start') {
            \Illuminate\Support\Facades\Cache::forget('tg_add_bundle_' . $chatId);
            if ($text === '/start' || $text === '/menu') {
                $this->sendMainMenu($chatId);
                return;
            }
            $this->telegramService->sendMessage($chatId, "❌ <b>Add Bundle Wizard cancelled.</b>");
            return;
        }

        if (\Illuminate\Support\Facades\Cache::has('tg_add_bundle_' . $chatId)) {
            $this->handleWizardCredentials($chatId, $text);
            return;
        }

        if ($text === '/start' || $text === '/menu') {
            $this->sendMainMenu($chatId);
            return;
        }

        if (str_starts_with($text, '/bundles')) {
            $this->listBundles($chatId);
            return;
        }

        if (str_starts_with($text, '/bulk')) {
            $this->handleBulk($chatId, $text, $messageId);
            return;
        }

        if (str_starts_with($text, '/generate') || str_starts_with($text, '/token')) {
            $this->handleGenerate($chatId, $text);
            return;
        }

        if (str_starts_with($text, '/addbundle')) {
            $this->handleAddBundle($chatId, $text);
            return;
        }

        $this->sendHelp($chatId);
    }

    public function sendMainMenu(string $chatId): void
    {
        $text = "🤖 <b>CodeGrab Admin Dashboard</b>\n"
              . "Manage your verification proxy nodes, credentials, and access settings dynamically.";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔑 Generate Token', 'callback_data' => 'menu:generate'],
                    ['text' => '📦 Bulk Generate', 'callback_data' => 'menu:bulk']
                ],
                [
                    ['text' => '📩 Fetch OTP Code', 'callback_data' => 'menu:fetch'],
                    ['text' => '🎟 Active Access Grants', 'callback_data' => 'menu:active']
                ],
                [
                    ['text' => '📦 Manage Bundles', 'callback_data' => 'menu:manage_bundles'],
                    ['text' => '📊 Statistics', 'callback_data' => 'menu:stats']
                ],
                [
                    ['text' => '⚙️ Settings', 'callback_data' => 'menu:settings']
                ]
            ]
        ];

        $this->telegramService->sendMessage($chatId, $text, $keyboard);
    }

    private function listBundles(string $chatId): void
    {
        $bundles = \App\Models\AccountBundle::where('is_active', true)->get();
        if ($bundles->isEmpty()) {
            $this->telegramService->sendMessage($chatId, "📦 <b>Account Bundles</b>\nNo active account bundles found in the database.");
            return;
        }

        $msg = "📦 <b>Active Account Bundles:</b>\n\n";
        foreach ($bundles as $bundle) {
            $msg .= "• <b>ID:</b> <code>{$bundle->id}</code> | <b>Name:</b> " . htmlspecialchars($bundle->name) . "\n";
            $msg .= "  🎮 Platform: <code>" . htmlspecialchars($bundle->platform) . "</code>\n";
            $msg .= "  📧 Email (Guard): <code>" . htmlspecialchars($bundle->email) . "</code>\n";
            if ($bundle->login_username) {
                $msg .= "  👤 Login User: <code>" . htmlspecialchars($bundle->login_username) . "</code>\n";
            }
            $msg .= "\n";
        }
        $this->telegramService->sendMessage($chatId, $msg);
    }

    private function handleBulk(string $chatId, string $text, int $messageId): void
    {
        $parts = preg_split('/\s+/', trim($text));
        if (count($parts) < 3) {
            $errorMsg = "⚠️ <b>Invalid Command</b>\n\n"
                       . "Usage: <code>/bulk &lt;bundle_id&gt; &lt;quantity&gt; [limit] [tag]</code>\n"
                       . "Example: <code>/bulk 1 50 15 Summer-Promo</code>";
            $this->telegramService->sendMessage($chatId, $errorMsg);
            return;
        }

        $bundleId = (int)$parts[1];
        $quantity = (int)$parts[2];

        if ($quantity < 1 || $quantity > 1000) {
            $this->telegramService->sendMessage($chatId, "⚠️ <b>Error</b>: Quantity must be between 1 and 1000.");
            return;
        }

        $bundle = \App\Models\AccountBundle::find($bundleId);
        if (!$bundle) {
            $this->telegramService->sendMessage($chatId, "⚠️ <b>Error</b>: Account Bundle with ID <code>{$bundleId}</code> not found.");
            return;
        }

        if (!$bundle->is_active) {
            $this->telegramService->sendMessage($chatId, "⚠️ <b>Error</b>: Account Bundle with ID <code>{$bundleId}</code> is currently inactive.");
            return;
        }

        $limitStr = isset($parts[3]) ? strtolower($parts[3]) : '20';
        if ($limitStr === 'unlimited' || $limitStr === '0' || $limitStr === 'inf' || $limitStr === 'infinite') {
            $limit = null;
        } else {
            $limit = (int)$limitStr;
        }

        $tag = isset($parts[4]) ? trim($parts[4]) : null;

        $frontendUrl = \App\Models\Setting::getValue('frontend_url', 'http://localhost:4200');
        $links = [];

        for ($i = 0; $i < $quantity; $i++) {
            $grant = \App\Models\AccessGrant::create([
                'account_bundle_id' => $bundle->id,
                'token' => \App\Models\AccessGrant::generateToken(),
                'email' => $bundle->email,
                'platform' => $bundle->platform,
                'tag' => $tag,
                'limit' => $limit,
                'uses' => 0,
                'is_active' => true,
            ]);
            $links[] = rtrim($frontendUrl, '/') . '/grab-code?token=' . $grant->token;
        }

        if ($quantity <= 10) {
            $successMsg = "✅ <b>Bulk Access Tokens Generated</b>\n"
                        . "📦 <b>Bundle</b>: " . htmlspecialchars($bundle->name) . " (ID: {$bundle->id})\n\n"
                        . "🔗 <b>Access Links:</b>\n"
                        . implode("\n", array_map(fn($l) => "<code>{$l}</code>", $links));
            $this->telegramService->sendMessage($chatId, $successMsg);
        } else {
            $fileContent = implode("\n", $links);
            $caption = "✅ <b>Bulk Access Tokens Generated</b>\n"
                     . "📦 <b>Bundle</b>: " . htmlspecialchars($bundle->name) . " (ID: {$bundle->id})\n"
                     . "🔢 <b>Quantity</b>: <code>{$quantity}</code> tokens.";

            $this->telegramService->sendDocument($chatId, $fileContent, 'tokens.txt', $caption, $messageId);
        }
    }

    private function handleGenerate(string $chatId, string $text): void
    {
        $parts = preg_split('/\s+/', trim($text));
        if (count($parts) < 3) {
            $errorMsg = "⚠️ <b>Invalid Command</b>\n\n"
                      . "Usage: <code>/generate &lt;email&gt; &lt;platform&gt; [limit] [tag]</code>\n"
                      . "Example: <code>/generate user@example.com Steam 15 Summer-Promo</code>";
            $this->telegramService->sendMessage($chatId, $errorMsg);
            return;
        }

        $email = $parts[1];
        $platformName = $parts[2];

        $limitStr = isset($parts[3]) ? strtolower($parts[3]) : '20';
        if ($limitStr === 'unlimited' || $limitStr === '0' || $limitStr === 'inf' || $limitStr === 'infinite') {
            $limit = null;
        } else {
            $limit = (int)$limitStr;
        }

        $tag = isset($parts[4]) ? trim($parts[4]) : null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->telegramService->sendMessage($chatId, "⚠️ <b>Error</b>: Please enter a valid email address.");
            return;
        }

        $platformExists = \App\Models\PlatformGuardEmailFilter::where('name', $platformName)->exists();
        if (!$platformExists) {
            $available = \App\Models\PlatformGuardEmailFilter::pluck('name')->toArray();
            $platformList = count($available) > 0 ? implode(', ', $available) : 'None';
            $this->telegramService->sendMessage($chatId, "⚠️ <b>Error</b>: Platform '" . htmlspecialchars($platformName) . "' is not registered.\nAvailable: " . htmlspecialchars($platformList));
            return;
        }

        $grant = \App\Models\AccessGrant::create([
            'token' => \App\Models\AccessGrant::generateToken(),
            'email' => $email,
            'platform' => $platformName,
            'tag' => $tag,
            'limit' => $limit,
            'uses' => 0,
            'is_active' => true,
        ]);

        $frontendUrl = \App\Models\Setting::getValue('frontend_url', 'http://localhost:4200');
        $accessLink = rtrim($frontendUrl, '/') . '/grab-code?token=' . $grant->token;

        $limitDisplay = $limit !== null ? "<code>{$limit}</code> times" : "<code>Unlimited</code>";

        $successMsg = "✅ <b>Access Token Generated</b>\n\n"
                    . "📧 <b>Email</b>: <code>" . htmlspecialchars($email) . "</code>\n"
                    . "🎮 <b>Platform</b>: <code>" . htmlspecialchars($platformName) . "</code>\n"
                    . "🔢 <b>Usage Limit</b>: {$limitDisplay}\n\n"
                    . "🔗 <b>Access Link</b>:\n" . htmlspecialchars($accessLink);

        $this->telegramService->sendMessage($chatId, $successMsg);
    }

    private function sendHelp(string $chatId): void
    {
        $text = "🤖 <b>CodeGrab Command Helper</b>\n\n"
              . "• Use /start or /menu to launch the interactive administration panel.\n"
              . "• Use <code>/addbundle Name | Email | Platform | Password | [Username]</code> to create a new bundle.\n"
              . "• Interact with the inline buttons to verify connections, fetch codes, audit access links, or tweak parameters.";

        $this->telegramService->sendMessage($chatId, $text);
    }

    private function handleAddBundle(string $chatId, string $text): void
    {
        $raw = trim(substr($text, 10));
        if (empty($raw)) {
            $this->telegramService->sendMessage($chatId, "⚠️ <b>Error</b>: Please provide bundle details.\nFormat: <code>/addbundle Name | Email | Platform | Password | [Username]</code>");
            return;
        }

        $parts = array_map('trim', explode('|', $raw));
        if (count($parts) < 4) {
            $this->telegramService->sendMessage($chatId, "⚠️ <b>Error</b>: Invalid format.\nFormat: <code>/addbundle Name | Email | Platform | Password | [Username]</code>\nExample: <code>/addbundle MySteam | test@mail.com | Steam | pass123</code>");
            return;
        }

        $name = $parts[0];
        $email = $parts[1];
        $platform = $parts[2];
        $password = $parts[3];
        $username = $parts[4] ?? null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->telegramService->sendMessage($chatId, "⚠️ <b>Error</b>: Please enter a valid email address.");
            return;
        }

        $bundle = \App\Models\AccountBundle::create([
            'name' => $name,
            'email' => $email,
            'platform' => $platform,
            'password' => $password,
            'login_username' => $username,
            'is_active' => true,
            'hide_email' => false
        ]);

        $msg = "✅ <b>Account Bundle Added Successfully!</b>\n\n"
             . "📦 <b>Name</b>: <code>" . htmlspecialchars($bundle->name) . "</code>\n"
             . "📧 <b>Email</b>: <code>" . htmlspecialchars($bundle->email) . "</code>\n"
             . "🎮 <b>Platform</b>: <code>" . htmlspecialchars($bundle->platform) . "</code>\n"
             . "👤 <b>User</b>: <code>" . htmlspecialchars($bundle->login_username ?? 'None') . "</code>";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '📦 Manage Bundles', 'callback_data' => 'menu:manage_bundles']],
                [['text' => '🏠 Main Menu', 'callback_data' => 'menu:home']]
            ]
        ];

        $this->telegramService->sendMessage($chatId, $msg, $keyboard);
    }

    private function handleWizardCredentials(string $chatId, string $text): void
    {
        $state = \Illuminate\Support\Facades\Cache::pull('tg_add_bundle_' . $chatId);
        if (!$state) {
            $this->telegramService->sendMessage($chatId, "⚠️ <b>Session expired</b>. Please start again.");
            return;
        }

        $parts = array_map('trim', explode('|', $text));
        if (count($parts) < 2) {
            $this->telegramService->sendMessage($chatId, "⚠️ <b>Error</b>: Invalid format.\nFormat: <code>Name | Password | [Username]</code>\nPlease click <b>➕ Add New Bundle</b> again to restart the wizard.");
            return;
        }

        $name = $parts[0];
        $password = $parts[1];
        $username = $parts[2] ?? null;

        $platformId = $state['platform_id'];
        $emailKey = $state['email_key'];

        $platform = \App\Models\PlatformGuardEmailFilter::find($platformId);
        $email = $this->resolveEmailFromKey($emailKey);

        if (!$platform || !$email) {
            $this->telegramService->sendMessage($chatId, "⚠️ <b>Error</b>: Invalid configuration. Please restart the wizard.");
            return;
        }

        $bundle = \App\Models\AccountBundle::create([
            'name' => $name,
            'email' => $email,
            'platform' => $platform->name,
            'password' => $password,
            'login_username' => $username,
            'is_active' => true,
            'hide_email' => false
        ]);

        $msg = "✅ <b>Account Bundle Added Successfully!</b>\n\n"
             . "📦 <b>Name</b>: <code>" . htmlspecialchars($bundle->name) . "</code>\n"
             . "📧 <b>Email</b>: <code>" . htmlspecialchars($bundle->email) . "</code>\n"
             . "🎮 <b>Platform</b>: <code>" . htmlspecialchars($bundle->platform) . "</code>\n"
             . "👤 <b>User</b>: <code>" . htmlspecialchars($bundle->login_username ?? 'None') . "</code>";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '📦 Manage Bundles', 'callback_data' => 'menu:manage_bundles']],
                [['text' => '🏠 Main Menu', 'callback_data' => 'menu:home']]
            ]
        ];

        $this->telegramService->sendMessage($chatId, $msg, $keyboard);
    }

    private function resolveEmailFromKey(string $key): ?string
    {
        $parts = explode('_', $key);
        if (count($parts) < 2) {
            return null;
        }

        $type = $parts[0];
        $id = (int)$parts[1];

        if ($type === 'gmail') {
            return \App\Models\GmailAccount::find($id)?->email;
        } elseif ($type === 'outlook') {
            return \App\Models\OutlookAccount::find($id)?->email;
        } elseif ($type === 'imap') {
            return \App\Models\ImapAccount::find($id)?->email;
        }

        return null;
    }
}

<?php

namespace App\Services\Telegram;

use App\Models\GmailAccount;
use App\Models\OutlookAccount;
use App\Models\ImapAccount;
use App\Models\PlatformGuardEmailFilter;
use App\Models\AccessGrant;
use App\Models\GuardFetchLog;
use App\Models\Setting;
use App\Models\Notification;
use Illuminate\Support\Carbon;

class TelegramMenuHandler
{
    public function __construct(private TelegramService $telegramService)
    {
    }

    public function handle(string $chatId, int $messageId, string $callbackQueryId, string $data): void
    {
        $this->telegramService->answerCallbackQuery($callbackQueryId);

        $parts = explode(':', $data);
        $action = $parts[0];

        if ($action === 'menu') {
            $page = $parts[1] ?? 'home';
            if ($page === 'home') {
                $this->showHome($chatId, $messageId);
            } elseif ($page === 'generate') {
                $this->showGenerateEmails($chatId, $messageId);
            } elseif ($page === 'fetch') {
                $this->showFetchEmails($chatId, $messageId);
            } elseif ($page === 'active') {
                $this->showActiveGrants($chatId, $messageId);
            } elseif ($page === 'stats') {
                $this->showStatistics($chatId, $messageId);
            } elseif ($page === 'settings') {
                $this->showSettings($chatId, $messageId);
            }
            return;
        }

        if ($action === 'gen_em') {
            $emailKey = $parts[1];
            $this->showGeneratePlatforms($chatId, $messageId, $emailKey);
            return;
        }

        if ($action === 'gen_pl') {
            $emailKey = $parts[1];
            $platformId = $parts[2];
            $this->showGenerateExpirations($chatId, $messageId, $emailKey, $platformId);
            return;
        }

        if ($action === 'gen_ex') {
            $emailKey = $parts[1];
            $platformId = $parts[2];
            $expiry = $parts[3];
            $this->showGenerateLimits($chatId, $messageId, $emailKey, $platformId, $expiry);
            return;
        }

        if ($action === 'gen_li') {
            $emailKey = $parts[1];
            $platformId = $parts[2];
            $expiry = $parts[3];
            $limit = $parts[4];
            $this->executeGenerateToken($chatId, $messageId, $emailKey, $platformId, $expiry, $limit);
            return;
        }

        if ($action === 'fetch_em') {
            $emailKey = $parts[1];
            $this->showFetchPlatforms($chatId, $messageId, $emailKey);
            return;
        }

        if ($action === 'fetch_pl') {
            $emailKey = $parts[1];
            $platformId = $parts[2];
            $this->executeFetchCode($chatId, $messageId, $emailKey, $platformId);
            return;
        }

        if ($action === 'revoke_tok') {
            $grantId = (int)$parts[1];
            $this->executeRevokeToken($chatId, $messageId, $grantId);
            return;
        }

        if ($action === 'toggle_light') {
            $current = Setting::getValue('light_mode', '0') === '1';
            Setting::setValue('light_mode', $current ? '0' : '1');
            $this->showSettings($chatId, $messageId);
            return;
        }

        if ($action === 'toggle_public') {
            $current = Setting::getValue('public_access_portal_enabled', '0') === '1';
            Setting::setValue('public_access_portal_enabled', $current ? '0' : '1');
            $this->showSettings($chatId, $messageId);
            return;
        }
    }

    private function showHome(string $chatId, int $messageId): void
    {
        $text = "🤖 <b>CodeGrab Admin Dashboard</b>\n"
              . "Manage your verification proxy nodes, credentials, and access settings dynamically.";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔑 Generate Token', 'callback_data' => 'menu:generate'],
                    ['text' => '📩 Fetch OTP Code', 'callback_data' => 'menu:fetch']
                ],
                [
                    ['text' => '🎟 Active Access Grants', 'callback_data' => 'menu:active']
                ],
                [
                    ['text' => '📊 Statistics', 'callback_data' => 'menu:stats'],
                    ['text' => '⚙️ Settings', 'callback_data' => 'menu:settings']
                ]
            ]
        ];

        $this->telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
    }

    private function showGenerateEmails(string $chatId, int $messageId): void
    {
        $accounts = $this->getRegisteredAccounts();
        if (empty($accounts)) {
            $this->showEmptyWarning($chatId, $messageId, "No email accounts registered. Connect a mailbox in the Admin panel first.");
            return;
        }

        $text = "🔑 <b>Generate Token - Step 1:</b> Select the email mailbox:";
        $keyboard = ['inline_keyboard' => []];

        foreach ($accounts as $acc) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "📧 {$acc['email']} ({$acc['type']})", 'callback_data' => "gen_em:{$acc['key']}"]
            ];
        }

        $keyboard['inline_keyboard'][] = [['text' => '🏠 Cancel', 'callback_data' => 'menu:home']];

        $this->telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
    }

    private function showGeneratePlatforms(string $chatId, int $messageId, string $emailKey): void
    {
        $platforms = PlatformGuardEmailFilter::all();
        if ($platforms->isEmpty()) {
            $this->showEmptyWarning($chatId, $messageId, "No platforms configured. Set up platform regex rules first.");
            return;
        }

        $text = "🔑 <b>Generate Token - Step 2:</b> Select platform:";
        $keyboard = ['inline_keyboard' => []];

        foreach ($platforms as $pl) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "🎮 {$pl->name}", 'callback_data' => "gen_pl:{$emailKey}:{$pl->id}"]
            ];
        }

        $keyboard['inline_keyboard'][] = [['text' => '🏠 Cancel', 'callback_data' => 'menu:home']];

        $this->telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
    }

    private function showGenerateExpirations(string $chatId, int $messageId, string $emailKey, string $platformId): void
    {
        $text = "🔑 <b>Generate Token - Step 3:</b> Select access link lifetime:";
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '⏱ 1 Hour', 'callback_data' => "gen_ex:{$emailKey}:{$platformId}:1h"]],
                [['text' => '📆 1 Day', 'callback_data' => "gen_ex:{$emailKey}:{$platformId}:1d"]],
                [['text' => '📅 7 Days', 'callback_data' => "gen_ex:{$emailKey}:{$platformId}:7d"]],
                [['text' => '♾ Never Expire', 'callback_data' => "gen_ex:{$emailKey}:{$platformId}:never"]],
                [['text' => '🏠 Cancel', 'callback_data' => 'menu:home']]
            ]
        ];

        $this->telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
    }

    private function showGenerateLimits(string $chatId, int $messageId, string $emailKey, string $platformId, string $expiry): void
    {
        $text = "🔑 <b>Generate Token - Step 4:</b> Select usage count limit:";
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '1 Use', 'callback_data' => "gen_li:{$emailKey}:{$platformId}:{$expiry}:1"]],
                [['text' => '5 Uses', 'callback_data' => "gen_li:{$emailKey}:{$platformId}:{$expiry}:5"]],
                [['text' => '10 Uses', 'callback_data' => "gen_li:{$emailKey}:{$platformId}:{$expiry}:10"]],
                [['text' => 'Unlimited', 'callback_data' => "gen_li:{$emailKey}:{$platformId}:{$expiry}:unlim"]],
                [['text' => '🏠 Cancel', 'callback_data' => 'menu:home']]
            ]
        ];

        $this->telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
    }

    private function executeGenerateToken(string $chatId, int $messageId, string $emailKey, string $platformId, string $expiry, string $limitCode): void
    {
        $email = $this->resolveEmailFromKey($emailKey);
        $platform = PlatformGuardEmailFilter::find($platformId);

        if (!$email || !$platform) {
            $this->showEmptyWarning($chatId, $messageId, "Invalid parameters detected. Token generation aborted.");
            return;
        }

        $expiresAt = null;
        if ($expiry === '1h') {
            $expiresAt = now()->addHour();
        } elseif ($expiry === '1d') {
            $expiresAt = now()->addDay();
        } elseif ($expiry === '7d') {
            $expiresAt = now()->addDays(7);
        }

        $limit = null;
        if ($limitCode !== 'unlim') {
            $limit = (int)$limitCode;
        }

        $grant = AccessGrant::create([
            'token' => AccessGrant::generateToken(),
            'email' => $email,
            'platform' => $platform->name,
            'limit' => $limit,
            'uses' => 0,
            'is_active' => true,
            'expires_at' => $expiresAt
        ]);

        $frontendUrl = Setting::getValue('frontend_url', 'http://localhost:4200');
        $accessLink = rtrim($frontendUrl, '/') . '/grab-code?token=' . $grant->token;

        $expiryDisplay = $expiresAt ? $expiresAt->toDateTimeString() : 'Never';
        $limitDisplay = $limit !== null ? "{$limit} fetches" : 'Unlimited';

        $text = "✅ <b>Access Token Generated Successfully!</b>\n\n"
              . "📧 <b>Email</b>: <code>{$email}</code>\n"
              . "🎮 <b>Platform</b>: <code>{$platform->name}</code>\n"
              . "📆 <b>Expires</b>: <code>{$expiryDisplay}</code>\n"
              . "🔢 <b>Limit</b>: <code>{$limitDisplay}</code>\n\n"
              . "🔗 <b>Access Link</b>:\n<code>{$accessLink}</code>";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🏠 Back to Menu', 'callback_data' => 'menu:home']]
            ]
        ];

        $this->telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
    }

    private function showFetchEmails(string $chatId, int $messageId): void
    {
        $accounts = $this->getRegisteredAccounts();
        if (empty($accounts)) {
            $this->showEmptyWarning($chatId, $messageId, "No email accounts registered. Connect a mailbox in the Admin panel first.");
            return;
        }

        $text = "📩 <b>Fetch OTP Code - Step 1:</b> Select the email mailbox:";
        $keyboard = ['inline_keyboard' => []];

        foreach ($accounts as $acc) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "📧 {$acc['email']}", 'callback_data' => "fetch_em:{$acc['key']}"]
            ];
        }

        $keyboard['inline_keyboard'][] = [['text' => '🏠 Cancel', 'callback_data' => 'menu:home']];

        $this->telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
    }

    private function showFetchPlatforms(string $chatId, int $messageId, string $emailKey): void
    {
        $email = $this->resolveEmailFromKey($emailKey);
        if (!$email) {
            $this->showEmptyWarning($chatId, $messageId, "Email account not found.");
            return;
        }

        $hasAssignments = \App\Models\EmailPlatformAssignment::where('email', $email)->exists();
        if ($hasAssignments) {
            $platformIds = \App\Models\EmailPlatformAssignment::where('email', $email)->pluck('platform_id')->toArray();
            $platforms = PlatformGuardEmailFilter::whereIn('id', $platformIds)->get();
        } else {
            $platforms = PlatformGuardEmailFilter::all();
        }

        if ($platforms->isEmpty()) {
            $this->showEmptyWarning($chatId, $messageId, "No platforms assigned or configured for this email.");
            return;
        }

        $text = "📩 <b>Fetch OTP Code - Step 2:</b> Select platform:";
        $keyboard = ['inline_keyboard' => []];

        foreach ($platforms as $pl) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "🎮 {$pl->name}", 'callback_data' => "fetch_pl:{$emailKey}:{$pl->id}"]
            ];
        }

        $keyboard['inline_keyboard'][] = [['text' => '🏠 Cancel', 'callback_data' => 'menu:home']];

        $this->telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
    }

    private function executeFetchCode(string $chatId, int $messageId, string $emailKey, string $platformId): void
    {
        $email = $this->resolveEmailFromKey($emailKey);
        $platform = PlatformGuardEmailFilter::find($platformId);

        if (!$email || !$platform) {
            $this->showEmptyWarning($chatId, $messageId, "Invalid parameters. OTP fetch cancelled.");
            return;
        }

        $parts = explode('_', $emailKey);
        $type = $parts[0];

        $this->telegramService->editMessageText($chatId, $messageId, "⏳ <b>Connecting and fetching code... please wait.</b>");

        $codeResult = null;
        try {
            $enableHeuristic = (bool)($platform->enable_heuristic ?? false);
            $strategy = $platform->grabbing_strategy ?? 'heuristic_first';

            if ($type === 'gmail') {
                $codeResult = resolve(\App\Infrastructure\Google\Services\GmailService::class)->findCodeInLatestEmail($email, $platform->sender, $platform->regex, $platform->subject, $enableHeuristic, $strategy);
            } elseif ($type === 'outlook') {
                $codeResult = resolve(\App\Infrastructure\Outlook\Services\OutlookService::class)->findCodeInLatestEmail($email, $platform->sender, $platform->regex, $platform->subject, $enableHeuristic, $strategy);
            } elseif ($type === 'imap') {
                $codeResult = resolve(\App\Infrastructure\Imap\Services\ImapService::class)->findCodeInLatestEmail($email, $platform->sender, $platform->regex, $platform->subject, $enableHeuristic, $strategy);
            }
        } catch (\Exception $e) {
            $codeResult = ['success' => false, 'message' => $e->getMessage()];
        }

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🔄 Retry Fetch', 'callback_data' => "fetch_pl:{$emailKey}:{$platformId}"]],
                [['text' => '🏠 Back to Menu', 'callback_data' => 'menu:home']]
            ]
        ];

        if ($codeResult && $codeResult['success']) {
            $emailDate = isset($codeResult['date']) && $codeResult['date'] ? Carbon::parse($codeResult['date']) : null;
            $isRecent = false;
            if ($emailDate) {
                $diff = now()->getTimestamp() - $emailDate->getTimestamp();
                $limit = (int)(Setting::getValue('email_timeframe_limit') ?? config('guard.timeframe_limit', 1200));
                if ($diff >= -30 && $diff <= $limit) {
                    $isRecent = true;
                }
            }

            $lastSuccessLog = GuardFetchLog::where('email', $email)
                ->where('platform', $platform->name)
                ->where('status', 'success')
                ->where('created_at', '>=', now()->subMinutes(15))
                ->latest()
                ->first();

            $isSameCode = $lastSuccessLog && ($lastSuccessLog->code === $codeResult['data']);

            if ($isRecent || $isSameCode) {
                if (!$isSameCode) {
                    $this->incrementFetchCount($type, $email);
                }

                GuardFetchLog::create([
                    'email' => $email,
                    'account_type' => $type,
                    'platform' => $platform->name,
                    'status' => 'success',
                    'code' => $codeResult['data'],
                    'grab_pattern' => $codeResult['grab_pattern'] ?? null,
                ]);

                $text = "🔑 <b>OTP Code Successfully Extracted!</b>\n\n"
                      . "📧 <b>Email</b>: <code>{$email}</code>\n"
                      . "🎮 <b>Platform</b>: <code>{$platform->name}</code>\n"
                      . "🔑 <b>Code</b>: <code>{$codeResult['data']}</code>\n"
                      . "🕒 <b>Extracted</b>: <code>" . now()->toTimeString() . "</code>";

                $this->telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
            } else {
                $errorMessage = 'Security code is outdated. Please request a new code on the game client.';
                $this->logFailure($email, $type, $platform->name, $errorMessage);
                $this->telegramService->editMessageText($chatId, $messageId, "❌ <b>Fetch Failed</b>: {$errorMessage}", $keyboard);
            }
        } else {
            $errorMessage = $codeResult['message'] ?? 'Unable to find verification email.';
            $this->logFailure($email, $type, $platform->name, $errorMessage);
            $this->telegramService->editMessageText($chatId, $messageId, "❌ <b>Fetch Failed</b>: " . htmlspecialchars($errorMessage), $keyboard);
        }
    }

    private function showActiveGrants(string $chatId, int $messageId): void
    {
        $grants = AccessGrant::where('is_active', true)->latest()->take(5)->get();

        if ($grants->isEmpty()) {
            $text = "🎟 <b>Active Access Grants:</b>\n\nNo active access grants found.";
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '🏠 Back to Menu', 'callback_data' => 'menu:home']]
                ]
            ];
            $this->telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
            return;
        }

        $text = "🎟 <b>Recent Active Access Grants (Last 5):</b>\n\n";
        $keyboard = ['inline_keyboard' => []];

        foreach ($grants as $grant) {
            $limitStr = $grant->limit !== null ? "{$grant->uses}/{$grant->limit}" : "{$grant->uses}/Unlimited";
            $text .= "• 🎮 <b>{$grant->platform}</b> | 📧 <code>{$grant->email}</code>\n"
                  . "  🔑 Uses: <code>{$limitStr}</code>\n\n";

            $keyboard['inline_keyboard'][] = [
                ['text' => "❌ Revoke {$grant->platform} ({$grant->email})", 'callback_data' => "revoke_tok:{$grant->id}"]
            ];
        }

        $keyboard['inline_keyboard'][] = [['text' => '🏠 Back to Menu', 'callback_data' => 'menu:home']];

        $this->telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
    }

    private function executeRevokeToken(string $chatId, int $messageId, int $grantId): void
    {
        $grant = AccessGrant::find($grantId);
        if ($grant) {
            $grant->update(['is_active' => false]);
        }

        $this->showActiveGrants($chatId, $messageId);
    }

    private function showStatistics(string $chatId, int $messageId): void
    {
        $totalGmail = GmailAccount::count();
        $totalOutlook = OutlookAccount::count();
        $totalImap = ImapAccount::count();
        $totalGrants = AccessGrant::where('is_active', true)->count();
        $todayFetches = GuardFetchLog::where('created_at', '>=', now()->startOfDay())->count();
        $todaySuccess = GuardFetchLog::where('created_at', '>=', now()->startOfDay())->where('status', 'success')->count();
        $todayFailed = GuardFetchLog::where('created_at', '>=', now()->startOfDay())->where('status', 'failed')->count();

        $text = "📊 <b>CodeGrab System Statistics</b>\n\n"
              . "📧 <b>Registered Mailboxes:</b>\n"
              . "• Gmail: <code>{$totalGmail}</code>\n"
              . "• Outlook: <code>{$totalOutlook}</code>\n"
              . "• IMAP: <code>{$totalImap}</code>\n\n"
              . "🎟 <b>Access Control:</b>\n"
              . "• Active Token Links: <code>{$totalGrants}</code>\n\n"
              . "📈 <b>Today's Interceptions:</b>\n"
              . "• Total Requests: <code>{$todayFetches}</code>\n"
              . "• Successful: <code>{$todaySuccess}</code>\n"
              . "• Failed: <code>{$todayFailed}</code>";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🏠 Back to Menu', 'callback_data' => 'menu:home']]
            ]
        ];

        $this->telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
    }

    private function showSettings(string $chatId, int $messageId): void
    {
        $lightMode = Setting::getValue('light_mode', '0') === '1';
        $publicAccess = Setting::getValue('public_access_portal_enabled', '0') === '1';

        $lightModeStatus = $lightMode ? '💡 ON (Lazy APIs & Aggressive Caching)' : '🔌 OFF (Default)';
        $publicAccessStatus = $publicAccess ? '🌐 OPEN (No Tokens Needed)' : '🔒 CLOSED (Tokens Required)';

        $text = "⚙️ <b>System Settings Dashboard</b>\n\n"
              . "💡 <b>Light Mode</b>:\n<code>{$lightModeStatus}</code>\n\n"
              . "🌐 <b>Public Access Portal</b>:\n<code>{$publicAccessStatus}</code>";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '💡 Toggle Light Mode', 'callback_data' => 'toggle_light']],
                [['text' => '🌐 Toggle Public Portal', 'callback_data' => 'toggle_public']],
                [['text' => '🏠 Back to Menu', 'callback_data' => 'menu:home']]
            ]
        ];

        $this->telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
    }

    private function showEmptyWarning(string $chatId, int $messageId, string $message): void
    {
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🏠 Back to Menu', 'callback_data' => 'menu:home']]
            ]
        ];
        $this->telegramService->editMessageText($chatId, $messageId, "⚠️ <b>Warning</b>\n\n" . $message, $keyboard);
    }

    private function getRegisteredAccounts(): array
    {
        $list = [];

        foreach (GmailAccount::all() as $acc) {
            $list[] = ['key' => "gmail_{$acc->id}", 'email' => $acc->email, 'type' => 'Gmail'];
        }
        foreach (OutlookAccount::all() as $acc) {
            $list[] = ['key' => "outlook_{$acc->id}", 'email' => $acc->email, 'type' => 'Outlook'];
        }
        foreach (ImapAccount::all() as $acc) {
            $list[] = ['key' => "imap_{$acc->id}", 'email' => $acc->email, 'type' => 'IMAP'];
        }

        return $list;
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
            return GmailAccount::find($id)?->email;
        } elseif ($type === 'outlook') {
            return OutlookAccount::find($id)?->email;
        } elseif ($type === 'imap') {
            return ImapAccount::find($id)?->email;
        }

        return null;
    }

    private function incrementFetchCount(string $type, string $email): void
    {
        if ($type === 'gmail') {
            $acc = GmailAccount::where('email', $email)->first();
        } elseif ($type === 'outlook') {
            $acc = OutlookAccount::where('email', $email)->first();
        } else {
            $acc = ImapAccount::where('email', $email)->first();
        }

        if ($acc) {
            $acc->increment('fetch_count');
            $acc->update(['last_used_at' => now()]);
        }
    }

    private function logFailure(string $email, string $type, string $platform, string $message): void
    {
        GuardFetchLog::create([
            'email' => $email,
            'account_type' => $type,
            'platform' => $platform,
            'status' => 'failed',
            'error_message' => $message
        ]);

        Notification::create([
            'type' => 'fetch_error',
            'title' => "Interception Failed: {$platform}",
            'message' => "Failed to fetch guard code for {$email} on {$platform}: {$message}"
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\AccessGrant;
use App\Models\PlatformGuardEmailFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramWebhookController extends Controller
{
    
    public function handle(Request $request)
    {
        $chatId = $request->input('message.chat.id');
        $text = $request->input('message.text');
        $messageId = $request->input('message.message_id');

        if (!$chatId || !$text) {
            return response()->json(['success' => true, 'message' => 'Not a text message.']);
        }

        
        $configuredChatId = Setting::getValue('telegram_chat_id');
        $botToken = Setting::getValue('telegram_bot_token');

        if (!$botToken) {
            Log::warning("[Telegram Webhook] Bot token not configured.");
            return response()->json(['success' => true]);
        }

        
        if (empty($configuredChatId) || trim($configuredChatId) !== trim((string)$chatId)) {
            Log::warning("[Telegram Webhook] Unauthorized chat ID: {$chatId}");
            $this->sendTelegramMessage($botToken, $chatId, "Access denied. Chat ID <b>" . htmlspecialchars($chatId) . "</b> is not authorized in Guard Helper settings.", $messageId);
            return response()->json(['success' => true]);
        }

        
        if (Str::startsWith($text, '/start') || Str::startsWith($text, '/help')) {
            $helpMsg = "<b>Guard Helper Access Bot</b>\n\n"
                     . "Commands:\n"
                     . "• <code>/generate &lt;email&gt; &lt;platform&gt; [limit] [tag]</code> - Generate single access token link\n"
                     . "• <code>/token &lt;email&gt; &lt;platform&gt; [limit] [tag]</code> - Same as generate\n"
                     . "• <code>/bundles</code> - List active Account Bundles with their IDs\n"
                     . "• <code>/bulk &lt;bundle_id&gt; &lt;quantity&gt; [limit] [tag]</code> - Bulk generate access links for a bundle\n"
                     . "• <code>/help</code> - Show this help message\n\n"
                     . "Examples:\n"
                     . "• <code>/generate user@example.com Steam 15 Summer-Promo</code>\n"
                     . "• <code>/bulk 1 50 5 Summer-Promo</code>";
            $this->sendTelegramMessage($botToken, $chatId, $helpMsg, $messageId);
            return response()->json(['success' => true]);
        }

        
        if (Str::startsWith($text, '/bundles')) {
            $bundles = \App\Models\AccountBundle::where('is_active', true)->get();
            if ($bundles->isEmpty()) {
                $this->sendTelegramMessage($botToken, $chatId, "📦 <b>Account Bundles</b>\nNo active account bundles found in the database.", $messageId);
                return response()->json(['success' => true]);
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
            $this->sendTelegramMessage($botToken, $chatId, $msg, $messageId);
            return response()->json(['success' => true]);
        }

        
        if (Str::startsWith($text, '/bulk')) {
            $parts = preg_split('/\s+/', trim($text));
            if (count($parts) < 3) {
                $errorMsg = "⚠️ <b>Invalid Command</b>\n\n"
                           . "Usage: <code>/bulk &lt;bundle_id&gt; &lt;quantity&gt; [limit] [tag]</code>\n"
                           . "Example: <code>/bulk 1 50 15 Summer-Promo</code>";
                $this->sendTelegramMessage($botToken, $chatId, $errorMsg, $messageId);
                return response()->json(['success' => true]);
            }

            $bundleId = (int) $parts[1];
            $quantity = (int) $parts[2];

            if ($quantity < 1 || $quantity > 1000) {
                $this->sendTelegramMessage($botToken, $chatId, "⚠️ <b>Error</b>: Quantity must be between 1 and 1000.", $messageId);
                return response()->json(['success' => true]);
            }

            $bundle = \App\Models\AccountBundle::find($bundleId);
            if (!$bundle) {
                $this->sendTelegramMessage($botToken, $chatId, "⚠️ <b>Error</b>: Account Bundle with ID <code>{$bundleId}</code> not found.", $messageId);
                return response()->json(['success' => true]);
            }

            if (!$bundle->is_active) {
                $this->sendTelegramMessage($botToken, $chatId, "⚠️ <b>Error</b>: Account Bundle with ID <code>{$bundleId}</code> is currently inactive.", $messageId);
                return response()->json(['success' => true]);
            }

            $limitStr = isset($parts[3]) ? strtolower($parts[3]) : '20';
            if ($limitStr === 'unlimited' || $limitStr === '0' || $limitStr === 'inf' || $limitStr === 'infinite') {
                $limit = null;
            } else {
                $limit = (int) $limitStr;
            }

            $tag = isset($parts[4]) ? trim($parts[4]) : null;

            $frontendUrl = Setting::getValue('frontend_url', 'http://localhost:4200');
            $links = [];

            for ($i = 0; $i < $quantity; $i++) {
                $grant = AccessGrant::create([
                    'account_bundle_id' => $bundle->id,
                    'token' => AccessGrant::generateToken(),
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
                $this->sendTelegramMessage($botToken, $chatId, $successMsg, $messageId);
            } else {
                
                $fileContent = implode("\n", $links);
                $caption = "✅ <b>Bulk Access Tokens Generated</b>\n"
                         . "📦 <b>Bundle</b>: " . htmlspecialchars($bundle->name) . " (ID: {$bundle->id})\n"
                         . "🔢 <b>Quantity</b>: <code>{$quantity}</code> tokens.";
                         
                try {
                    Http::attach('document', $fileContent, 'tokens.txt')
                        ->post("https://api.telegram.org/bot{$botToken}/sendDocument", [
                            'chat_id' => $chatId,
                            'caption' => $caption,
                            'parse_mode' => 'HTML',
                            'reply_to_message_id' => $messageId
                        ]);
                } catch (\Throwable $e) {
                    Log::error("[Telegram Webhook] Failed to send bulk document: " . $e->getMessage());
                    $this->sendTelegramMessage($botToken, $chatId, "⚠️ <b>Error</b>: Failed to upload generated tokens document file. " . htmlspecialchars($e->getMessage()), $messageId);
                }
            }

            return response()->json(['success' => true]);
        }

        
        if (Str::startsWith($text, '/generate') || Str::startsWith($text, '/token')) {
            $parts = preg_split('/\s+/', trim($text));
            if (count($parts) < 3) {
                $errorMsg = "⚠️ <b>Invalid Command</b>\n\n"
                          . "Usage: <code>/generate &lt;email&gt; &lt;platform&gt; [limit] [tag]</code>\n"
                          . "Example: <code>/generate user@example.com Steam 15 Summer-Promo</code>";
                $this->sendTelegramMessage($botToken, $chatId, $errorMsg, $messageId);
                return response()->json(['success' => true]);
            }

            $email = $parts[1];
            $platformName = $parts[2];
            
            $limitStr = isset($parts[3]) ? strtolower($parts[3]) : '20';
            if ($limitStr === 'unlimited' || $limitStr === '0' || $limitStr === 'inf' || $limitStr === 'infinite') {
                $limit = null;
            } else {
                $limit = (int) $limitStr;
            }

            $tag = isset($parts[4]) ? trim($parts[4]) : null;

            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->sendTelegramMessage($botToken, $chatId, "⚠️ <b>Error</b>: Please enter a valid email address.", $messageId);
                return response()->json(['success' => true]);
            }

            
            $platformExists = PlatformGuardEmailFilter::where('name', $platformName)->exists();
            if (!$platformExists) {
                $available = PlatformGuardEmailFilter::pluck('name')->toArray();
                $platformList = count($available) > 0 ? implode(', ', $available) : 'None';
                $this->sendTelegramMessage($botToken, $chatId, "⚠️ <b>Error</b>: Platform '" . htmlspecialchars($platformName) . "' is not registered.\nAvailable: " . htmlspecialchars($platformList), $messageId);
                return response()->json(['success' => true]);
            }

            
            $grant = AccessGrant::create([
                'token' => AccessGrant::generateToken(),
                'email' => $email,
                'platform' => $platformName,
                'tag' => $tag,
                'limit' => $limit,
                'uses' => 0,
                'is_active' => true,
            ]);

            $frontendUrl = Setting::getValue('frontend_url', 'http://localhost:4200');
            $accessLink = rtrim($frontendUrl, '/') . '/grab-code?token=' . $grant->token;

            $limitDisplay = $limit !== null ? "<code>{$limit}</code> times" : "<code>Unlimited</code>";

            $successMsg = "✅ <b>Access Token Generated</b>\n\n"
                        . "📧 <b>Email</b>: <code>" . htmlspecialchars($email) . "</code>\n"
                        . "🎮 <b>Platform</b>: <code>" . htmlspecialchars($platformName) . "</code>\n"
                        . "🔢 <b>Usage Limit</b>: {$limitDisplay}\n\n"
                        . "🔗 <b>Access Link</b>:\n" . htmlspecialchars($accessLink);

            $this->sendTelegramMessage($botToken, $chatId, $successMsg, $messageId);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => true]);
    }

    
    private function sendTelegramMessage(string $token, $chatId, string $text, $replyToMessageId = null)
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ];

            if ($replyToMessageId) {
                $payload['reply_to_message_id'] = $replyToMessageId;
            }

            Http::post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
        } catch (\Throwable $e) {
            Log::error("[Telegram Webhook] Failed to send message: " . $e->getMessage());
        }
    }
}

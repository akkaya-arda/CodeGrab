<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\PlatformGuardEmailFilter;
use App\Models\AccountBundle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class TelegramWebApiController extends Controller
{
    public function getPlatforms(Request $request)
    {
        $initData = $request->input('init_data');
        if (!$this->verifyInitData($initData)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized request.'], 403);
        }

        $platforms = PlatformGuardEmailFilter::select('id', 'name')->get();
        return response()->json(['success' => true, 'platforms' => $platforms]);
    }

    public function addBundle(Request $request)
    {
        $initData = $request->input('init_data');
        if (!$this->verifyInitData($initData)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized request.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'platform' => 'required|string|max:255',
            'password' => 'required|string',
            'login_username' => 'nullable|string|max:255'
        ]);

        $bundle = AccountBundle::create([
            'name' => $request->name,
            'email' => $request->email,
            'platform' => $request->platform,
            'password' => $request->password,
            'login_username' => $request->login_username,
            'is_active' => true,
            'hide_email' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bundle added successfully.',
            'bundle' => [
                'id' => $bundle->id,
                'name' => $bundle->name,
                'platform' => $bundle->platform
            ]
        ]);
    }

    private function verifyInitData(?string $initData): bool
    {
        if (empty($initData)) {
            return false;
        }

        $botToken = Setting::getValue('telegram_bot_token');
        $chatId = Setting::getValue('telegram_chat_id');
        if (!$botToken || !$chatId) {
            return false;
        }

        parse_str($initData, $params);
        if (!isset($params['hash']) || !isset($params['auth_date'])) {
            return false;
        }

        if (time() - (int)$params['auth_date'] > 86400) {
            return false;
        }

        $hash = $params['hash'];
        unset($params['hash']);

        ksort($params);

        $dataCheckStrings = [];
        foreach ($params as $key => $val) {
            $dataCheckStrings[] = "{$key}={$val}";
        }
        $dataCheckString = implode("\n", $dataCheckStrings);

        $secretKey = hash_hmac('sha256', $botToken, 'Webapps', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($calculatedHash, $hash)) {
            return false;
        }

        $userJson = json_decode($params['user'] ?? '', true);
        if (!$userJson || !isset($userJson['id']) || trim($userJson['id']) !== trim($chatId)) {
            return false;
        }

        return true;
    }
}

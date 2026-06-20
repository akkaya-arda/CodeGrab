<?php

namespace App\Infrastructure\Google\Services;

use App\Models\GmailAccount;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class GoogleAccountsService
{
    public function getAccountTokenData(string $email)
    {
        return GmailAccount::where('email', $email)->first();
    }
    public function getSavedAccounts()
    {
        $accounts = GmailAccount::all();
        $data = [];
        foreach ($accounts as $account) {
            $data[] = [$account, 'expired' => $account->refresh_token_expires_at > Carbon::now(), 'expires_in' => $account->refresh_token_expires_at - Carbon::now()];
        }

        return $data;
    }

    public function addAccount(GmailAccount $gmailAccount)
    {
        try {
            if ($gmailAccount->refresh_token_expires_at < Carbon::now()) {
                return [
                    'success' => false,
                    'message' => 'Refresh token already expired.',
                ];
            }

            $gmailAccount->save();
            return ['success' => true, 'message' => 'Google account added successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

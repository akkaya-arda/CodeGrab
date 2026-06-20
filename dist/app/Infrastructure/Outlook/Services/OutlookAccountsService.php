<?php

namespace App\Infrastructure\Outlook\Services;

use App\Models\OutlookAccount;
use Carbon\Carbon;
use Exception;

class OutlookAccountsService
{
    public function getAccountTokenData(string $email)
    {
        return OutlookAccount::where('email', $email)->first();
    }

    public function getSavedAccounts()
    {
        $accounts = OutlookAccount::all();
        $data = [];
        foreach ($accounts as $key => $account) {
            $data[] = [$account, 'expired' => $account->refresh_token_expires_at > Carbon::now()];
        }
    }

    public function addAccount(OutlookAccount $outlookAccount)
    {
        try {
            if ($outlookAccount->refresh_token_expires_at < Carbon::now()) {
                return [
                    'success' => false,
                    'message' => 'Refresh token already expired.'
                ];
            }

            $outlookAccount->save();
            return ['success' => true, 'message' => 'Outlook account added successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

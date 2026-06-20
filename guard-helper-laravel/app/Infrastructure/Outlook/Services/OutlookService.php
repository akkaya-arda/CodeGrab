<?php

namespace App\Infrastructure\Outlook\Services;

use App\Infrastructure\Outlook\OAuth\OutlookOAuthService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class OutlookService
{
    public function __construct(
        private OutlookOAuthService $outlookOAuthService,
        private OutlookAccountsService $outlookAccountsService
    ) {
    }

    private function getReadyAccessToken(string $email)
    {
        $account = $this->outlookAccountsService->getAccountTokenData($email);
        if (!$account) {
            return ['success' => false, 'message' => "Account with email '{$email}' not found in database."];
        }
        if (Carbon::parse($account->access_token_expires_at)->isPast()) {
            if (Carbon::parse($account->refresh_token_expires_at)->isPast()) {
                \App\Models\Notification::create([
                    'type' => 'auth_error',
                    'title' => 'Outlook Token Expired: ' . $email,
                    'message' => 'Refresh token has expired for ' . $email . '. Re-authorization is required.'
                ]);
                return ['success' => false, 'message' => 'Refresh token already expired. Please re-login.'];
            }

            $accessToken = $this->outlookOAuthService->getAccessTokenUsingRefreshToken($account->refresh_token);
            if (!$accessToken['success']) {
                $errDescription = $accessToken['error']['error_description'] ?? $accessToken['message'] ?? 'Unknown refresh error';
                \App\Models\Notification::create([
                    'type' => 'auth_error',
                    'title' => 'Outlook Refresh Failed: ' . $email,
                    'message' => 'Failed to refresh Outlook access token for ' . $email . ': ' . $errDescription . '. Re-authorization is required.'
                ]);
                return [
                    'success' => false,
                    'message' => 'Failed to refresh access token: ' . $errDescription,
                    'error' => $accessToken['error'] ?? null
                ];
            }
            $account->update([
                'access_token' => $accessToken['data']['access_token'],
                'access_token_expires_at' => Carbon::now()->addSeconds($accessToken['data']['expires_in'])->format('Y-m-d H:i:s'),
                'refresh_token' => $accessToken['data']['refresh_token'] ?? $account->refresh_token,
            ]);
            return ['success' => true, 'token' => $accessToken['data']['access_token']];
        }

        return ['success' => true, 'token' => $account->access_token];
    }

    public function getRecentEmailsFrom(string $forEmail, string $sender, int $limit = 5)
    {
        $tokenResult = $this->getReadyAccessToken($forEmail);
        if (!$tokenResult['success']) {
            return $tokenResult;
        }
        $accessToken = $tokenResult['token'];

        $timeframeLimit = (int) (\App\Models\Setting::getValue('email_timeframe_limit') ?? config('guard.timeframe_limit', 1200));
        $offsetBack = now()->subSeconds($timeframeLimit)->utc()->toIso8601String();
        $filter = "from/emailAddress/address eq '{$sender}' and receivedDateTime ge {$offsetBack}";

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Prefer' => 'outlook.body-content-type="text"',
            ])
            ->get('https://graph.microsoft.com/v1.0/me/messages', [
                '$filter' => $filter,
                '$top' => $limit,
                '$select' => 'body,receivedDateTime',
            ]);

        if ($response->failed()) {
            $errorData = $response->json();
            \Illuminate\Support\Facades\Log::error('Outlook API fetch failed: ' . json_encode($errorData));
            $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
            return [
                'success' => false,
                'message' => 'Failed to fetch emails from Outlook API: ' . $errorMessage,
                'error' => $errorData
            ];
        }

        $messages = $response->json()['value'] ?? [];

        usort($messages, function ($a, $b) {
            return strcmp($b['receivedDateTime'] ?? '', $a['receivedDateTime'] ?? '');
        });

        return [
            'success' => true,
            'messages' => $messages
        ];
    }

    public function findCodeInLatestEmail(
        string $forEmail,
        string $sender,
        ?string $expression,
        ?string $subject,
        bool $enableHeuristic = false,
        string $grabbingStrategy = 'heuristic_first'
    ) {
        $result = $this->getRecentEmailsFrom($forEmail, $sender, 10);
        if (!$result['success']) {
            return $result;
        }

        $messages = $result['messages'];
        if (empty($messages)) {
            return ['success' => false, 'message' => 'No security code was found. Please request a new code on the platform.'];
        }

        foreach ($messages as $msg) {
            $emailBody = $msg['body']['content'] ?? '';
            if ($emailBody !== '') {
                
                $emailBody = trim(preg_replace('/\s+/', ' ', $emailBody));
                
                $extractResult = \App\Services\CodeExtractor::extract($emailBody, $expression, $enableHeuristic, $grabbingStrategy);
                if ($extractResult !== null) {
                    return [
                        'success' => true,
                        'data' => $extractResult['code'],
                        'date' => $msg['receivedDateTime'] ?? null,
                        'grab_pattern' => $extractResult['pattern']
                    ];
                }
            }
        }

        return ['success' => false, 'message' => 'Code not found in any recent emails.'];
    }
}

<?php

namespace App\Infrastructure\Google\Services;

use App\Infrastructure\Google\OAuth\GoogleOAuthService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class GmailService
{
    public function __construct(
        private GoogleOAuthService $googleOAuthService,
        private GoogleAccountsService $googleAccountsService
    ) {
    }
    private function getReadyAccessToken(string $email): ?string
    {
        $account = $this->googleAccountsService->getAccountTokenData($email);
        if (!$account) {
            return null;
        }
        if (Carbon::parse($account->access_token_expires_at)->isPast()) {
            if (Carbon::parse($account->refresh_token_expires_at)->isPast()) {
                \App\Models\Notification::create([
                    'type' => 'auth_error',
                    'title' => 'Gmail Token Expired: ' . $email,
                    'message' => 'Refresh token has expired for ' . $email . '. Re-authorization is required.'
                ]);
                return null;
            }

            $accessToken = $this->googleOAuthService->getAccessTokenUsingRefreshToken($account->refresh_token);
            if (!$accessToken['success']) {
                \App\Models\Notification::create([
                    'type' => 'auth_error',
                    'title' => 'Gmail Refresh Failed: ' . $email,
                    'message' => 'Failed to refresh Gmail access token for ' . $email . '. Please re-authenticate.'
                ]);
                return null;
            }
            $account->update([
                'access_token' => $accessToken['data']['access_token'],
                'access_token_expires_at' => Carbon::now()->addSeconds($accessToken['data']['expires_in'])->format('Y-m-d H:i:s'),
            ]);
            return $accessToken['data']['access_token'];
        }

        return $account->access_token;
    }
    public function getRecentEmailsFrom(string $forEmail, string $sender, int $limit = 5)
    {
        $accessToken = $this->getReadyAccessToken($forEmail);
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Account refresh token is expired or invalid. Please re-login.'];
        }

        $timeframeLimit = (int) (\App\Models\Setting::getValue('email_timeframe_limit') ?? config('guard.timeframe_limit', 1200));
        $offsetTimestamp = now()->subSeconds($timeframeLimit)->timestamp;

        $query = 'from:' . $sender . ' after:' . $offsetTimestamp;

        $response = Http::withToken($accessToken)->get('https://www.googleapis.com/gmail/v1/users/' . $forEmail . '/messages', [
            'q' => $query,
            'maxResults' => $limit,
        ]);

        if ($response->failed()) {
            return [
                'success' => false,
                'message' => 'Failed to fetch messages list from Gmail API.',
                'error' => $response->json()
            ];
        }

        $messagesSummary = $response->json()['messages'] ?? [];
        $messages = [];

        foreach ($messagesSummary as $msgSummary) {
            $emailId = $msgSummary['id'];
            $emailDetail = Http::withToken($accessToken)->get('https://www.googleapis.com/gmail/v1/users/' . $forEmail . '/messages/' . $emailId);
            if ($emailDetail->successful()) {
                $detailJson = $emailDetail->json();
                $emailBody = $this->getMessageBody($detailJson['payload']);
                if ($emailBody) {
                    $internalDate = $detailJson['internalDate'] ?? null;
                    $formattedDate = $internalDate ? Carbon::createFromTimestampMs((int) $internalDate)->toIso8601String() : null;

                    $messages[] = [
                        'id' => $emailId,
                        'body' => $emailBody,
                        'date' => $formattedDate
                    ];
                }
            }
        }

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
            $emailBody = $msg['body'];

            $extractResult = \App\Services\CodeExtractor::extract($emailBody, $expression, $enableHeuristic, $grabbingStrategy);
            if ($extractResult !== null) {
                return [
                    'success' => true,
                    'data' => $extractResult['code'],
                    'date' => $msg['date'],
                    'grab_pattern' => $extractResult['pattern']
                ];
            }
        }

        return ['success' => false, 'message' => 'Code not found in any recent emails.'];
    }

    public function getMessageBody($payload)
    {
        $body = null;


        $plainText = $this->getPartByMimeType($payload, 'text/plain');
        if ($plainText !== null) {
            $body = $plainText;
        } else {

            $htmlText = $this->getPartByMimeType($payload, 'text/html');
            if ($htmlText !== null) {

                $htmlText = preg_replace('#<(style|script)[^>]*?>.*?</\1>#is', '', $htmlText);
                $body = html_entity_decode(strip_tags($htmlText));
            }
        }

        if ($body !== null) {

            return trim(preg_replace('/\s+/', ' ', $body));
        }

        return null;
    }

    private function getPartByMimeType($payload, $mimeType): ?string
    {
        if (isset($payload['body']['data']) && $payload['mimeType'] === $mimeType) {
            return $this->decodeGoogleBody($payload['body']['data']);
        }

        if (isset($payload['parts'])) {
            foreach ($payload['parts'] as $part) {
                $result = $this->getPartByMimeType($part, $mimeType);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    function decodeGoogleBody($data)
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}

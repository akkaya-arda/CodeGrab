<?php

namespace App\Infrastructure\Outlook\OAuth;

use Exception;
use Illuminate\Support\Facades\Http;

class OutlookOAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $tenant;
    public function __construct()
    {
        $this->clientId = config('oauth.outlook.client_id');
        $this->clientSecret = config('oauth.outlook.client_secret');
        $this->redirectUri = config('oauth.outlook.redirect_uri');
        $this->tenant = config('oauth.outlook.tenant');
    }
    public function getOutlookRedirectLink(array $state)
    {
        $state = base64_encode(json_encode($state));

        $scopes = implode(' ', [
            'https://graph.microsoft.com/mail.read',
            'https://graph.microsoft.com/user.read',
            'offline_access',
        ]);

        return "https://login.microsoftonline.com/{$this->tenant}/oauth2/v2.0/authorize"
            . "?client_id={$this->clientId}"
            . "&redirect_uri={$this->redirectUri}"
            . "&response_type=code"
            . "&scope=" . urlencode($scopes)
            . "&access_type=offline"
            . "&prompt=consent"
            . "&state={$state}";
    }

    public function getOutlookAccessAndRefreshToken(string $code)
    {
        try {
            $response = Http::asForm()->post("https://login.microsoftonline.com/{$this->tenant}/oauth2/v2.0/token", [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]);

            if ($response->failed())
                return ['success' => false, 'message' => 'Outlook access token request failed.', 'data' => $response->json()];

            return ['success' => true, 'message' => 'Outlook access token grabbed successfully.', 'data' => $response->json()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Outlook access token request failed.', 'data' => null];
        }
    }

    public function getOutlookUserInfoData($accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)->get('https://graph.microsoft.com/v1.0/me?$select=mail,userPrincipalName,otherMails');

            if ($response->failed())
                return ['success' => false, 'message' => 'Outlook user info request failed.', 'data' => $response->json()];

            return ['success' => true, 'message' => 'Outlook user info grabbed successfully.', 'data' => $response->json()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Outlook user info request failed.', 'data' => null];
        }
    }

    public function getAccessTokenUsingRefreshToken(string $refreshToken): array
    {
        try {
            $response = Http::asForm()->post("https://login.microsoftonline.com/{$this->tenant}/oauth2/v2.0/token", [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->failed()) {
                return ['success' => false, 'message' => 'Outlook access token refresh failed.', 'error' => $response->json()];
            }

            return ['success' => true, 'message' => 'Outlook access token refreshed successfully.', 'data' => $response->json()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Outlook access token refresh failed.', 'error' => $e->getMessage()];
        }
    }
}

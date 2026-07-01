<?php

namespace App\Infrastructure\Google\OAuth;

use Exception;
use Illuminate\Support\Facades\Http;

class GoogleOAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    public function __construct()
    {
        $this->clientId = config('oauth.google.client_id') ?: '';
        $this->clientSecret = config('oauth.google.client_secret') ?: '';
        $this->redirectUri = config('oauth.google.redirect_uri') ?: '';
    }

    public function getGoogleRedirectLink(array $state): string
    {
        $state = base64_encode(json_encode($state));

        $scopes = implode(' ', [
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/userinfo.email',
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth'
            . '?client_id=' . $this->clientId
            . '&redirect_uri=' . $this->redirectUri
            . '&response_type=code'
            . '&scope=' . urlencode($scopes)
            . '&access_type=offline'
            . '&prompt=consent'
            . '&state=' . $state;
    }

    public function getGoogleUserInfoData(string $accessToken): array
    {
        try {
            $response = Http::get('https://www.googleapis.com/oauth2/v3/userinfo', [
                'access_token' => $accessToken,
            ]);

            if ($response->failed()) {
                return ['success' => false, 'message' => 'Google user info request failed.', 'error' => $response->json()];
            }

            return ['success' => true, 'message' => 'Google user info grabbed successfully.', 'data' => $response->json()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Google user info request failed.', 'error' => $e->getMessage()];
        }
    }

    public function getGoogleAccessAndRefreshToken(string $code): array
    {
        try {
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if ($response->failed()) {
                return ['success' => false, 'message' => 'Google access token request failed.', 'error' => $response->json()];
            }

            return ['success' => true, 'message' => 'Google access token grabbed successfully.', 'data' => $response->json()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Google access token request failed.', 'error' => $e->getMessage()];
        }
    }

    public function getAccessTokenUsingRefreshToken(string $refreshToken): array
    {
        try {
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->failed()) {
                return ['success' => false, 'message' => 'Google access token request failed.', 'error' => $response->json()];
            }

            return ['success' => true, 'message' => 'Google access token grabbed successfully.', 'data' => $response->json()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Google access token request failed.', 'error' => $e->getMessage()];
        }
    }
}

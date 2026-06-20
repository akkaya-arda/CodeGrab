<?php

namespace App\Http\Controllers;

use App\Infrastructure\Google\OAuth\GoogleOAuthService;
use App\Infrastructure\Google\Services\GoogleAccountsService;
use App\Infrastructure\Outlook\OAuth\OutlookOAuthService;
use App\Infrastructure\Outlook\Services\OutlookAccountsService;
use App\Models\GmailAccount;
use App\Models\OutlookAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OAuthController extends Controller
{
    private GoogleOAuthService $googleOAuthService;
    private GoogleAccountsService $googleAccountsService;
    private OutlookOAuthService $outlookOAuthService;
    private OutlookAccountsService $outlookAccountsService;
    public function __construct(GoogleOAuthService $googleOAuthService, GoogleAccountsService $googleAccountsService, OutlookOAuthService $outlookOAuthService, OutlookAccountsService $outlookAccountsService)
    {
        $this->googleOAuthService = $googleOAuthService;
        $this->googleAccountsService = $googleAccountsService;
        $this->outlookOAuthService = $outlookOAuthService;
        $this->outlookAccountsService = $outlookAccountsService;
    }
    public function googleOAuthCallback(Request $request)
    {
        $data = $this->googleOAuthService->getGoogleAccessAndRefreshToken($request->code);
        if ($data['success']) {

            $userInfo = $this->googleOAuthService->getGoogleUserInfoData($data['data']['access_token']);
            $email = $userInfo['data']['email'];

            $accessTokenExpiration = Carbon::now()->addSeconds($data['data']['expires_in'])->format('Y-m-d H:i:s');
            $refreshTokenExpiration = Carbon::now()->addSeconds($data['data']['refresh_token_expires_in'])->format('Y-m-d H:i:s');

            $gmailAccount = GmailAccount::where('email', $email)->first();
            if ($gmailAccount) {
                $gmailAccount->access_token = $data['data']['access_token'];
                $gmailAccount->access_token_expires_at = $accessTokenExpiration;
                $gmailAccount->refresh_token = $data['data']['refresh_token'];
                $gmailAccount->refresh_token_expires_at = $refreshTokenExpiration;
                $gmailAccount->is_active = true;
                $gmailAccount->save();
                return response()->view('AccountStateUpdatedView');
            }

            $addResult = $this->googleAccountsService->addAccount(new GmailAccount([
                'email' => $email,
                'access_token' => $data['data']['access_token'],
                'access_token_expires_at' => $accessTokenExpiration,
                'refresh_token' => $data['data']['refresh_token'],
                'refresh_token_expires_at' => $refreshTokenExpiration,
                'is_active' => true,
            ]));

            if (!$addResult['success']) {
                return response()->view('AccountStateUpdateFailedView');
            }

            return response()->view('AccountStateUpdatedView');
        } else {
            return response()->view('AccountStateUpdatedView');
        }
    }

    public function outlookOAuthCallback(Request $request)
    {
        $error = $request->query('error');
        if ($error) {
            return view('OAuthErrorView', ['error' => $error, 'error_description' => $request->query('error_description')]);
        }

        $code = $request->query('code');
        $data = $this->outlookOAuthService->getOutlookAccessAndRefreshToken($code);
        if ($data['success']) {
            $userInfo = $this->outlookOAuthService->getOutlookUserInfoData($data['data']['access_token']);
            $email = $userInfo['data']['mail'];
            if (!isset($email)) {
                $email = $userInfo['data']['otherMails'][0];
            }

            if (!isset($email)) {
                return view('OAuthErrorView', ['error' => 'Outlook Email Error', 'error_description' => 'Outlook email not found. Please try again.']);
            }

            $accessTokenExpiration = Carbon::now()->addSeconds($data['data']['expires_in'])->format('Y-m-d H:i:s');
            $refreshTokenExpiration = Carbon::now()->addDays(90)->format('Y-m-d H:i:s');

            $outlookAccount = OutlookAccount::where('email', $email)->first();
            if ($outlookAccount) {
                $outlookAccount->update([
                    'access_token' => $data['data']['access_token'],
                    'refresh_token' => $data['data']['refresh_token'],
                    'refresh_token_expires_at' => $refreshTokenExpiration,
                    'access_token_expires_at' => $accessTokenExpiration,
                    'is_active' => true,
                ]);
                return response()->view('AccountStateUpdatedView');
            }

            $addResult = $this->outlookAccountsService->addAccount(new OutlookAccount([
                'email' => $email,
                'access_token' => $data['data']['access_token'],
                'access_token_expires_at' => $accessTokenExpiration,
                'refresh_token' => $data['data']['refresh_token'],
                'refresh_token_expires_at' => $refreshTokenExpiration,
                'is_active' => true,
            ]));

            if (!$addResult['success']) {
                return response()->view('AccountStateUpdateFailedView');
            }

            return response()->view('AccountStateUpdatedView');
        } else {
            return response()->view('AccountStateUpdatedView');
        }
    }

    public function getGoogleRedirectLink(Request $request)
    {
        $link = $this->googleOAuthService->getGoogleRedirectLink([]);
        return response()->json([
            'success' => true,
            'message' => 'Google OAuth link generated successfully.',
            'data' => $link,
        ]);
    }

    public function getOutlookRedirectLink(Request $request)
    {
        $link = $this->outlookOAuthService->getOutlookRedirectLink([]);
        return response()->json([
            'success' => true,
            'message' => 'Outlook OAuth link generated successfully.',
            'data' => $link
        ]);
    }
}

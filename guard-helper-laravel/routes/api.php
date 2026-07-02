<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\GmailController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\OutlookController;
use App\Http\Controllers\ImapController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\PublicGuardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\ExternalWebhookController;
use App\Http\Controllers\AccessGrantController;
use App\Http\Controllers\UserFeedbackController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AccountBundleController;
use App\Http\Controllers\PublicSupportController;
use App\Http\Controllers\AdminSupportController;
use App\Http\Controllers\StaticPageController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:global_api'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthenticationController::class, 'me']);
        Route::put('profile', [AuthenticationController::class, 'updateProfile'])->middleware('throttle:strict_action');
    });

    Route::post('auth/logout', [AuthenticationController::class, 'logout']);

    Route::prefix('oauth')->group(function () {
        Route::post('google/get-redirect-link', [OAuthController::class, 'getGoogleRedirectLink']);
        Route::post('outlook/get-redirect-link', [OAuthController::class, 'getOutlookRedirectLink']);
    });

    Route::prefix('email/gmail')->group(function () {
        Route::post('get-code-in-email/{platform}', [GmailController::class, 'findCodeInLatestEmail']);
        Route::get('get-accounts', [GmailController::class, 'getEmailAccounts']);
        Route::post('disable-account', [GmailController::class, 'disableAccount']);
        Route::post('enable-account', [GmailController::class, 'enableAccount']);
        Route::post('delete-account', [GmailController::class, 'deleteAccount']);
    });

    Route::prefix('email/outlook')->group(function () {
        Route::post('get-code-in-email/{platform}', [OutlookController::class, 'findCodeInLatestEmail']);
        Route::get('get-accounts', [OutlookController::class, 'getEmailAccounts']);
        Route::post('disable-account', [OutlookController::class, 'disableAccount']);
        Route::post('enable-account', [OutlookController::class, 'enableAccount']);
        Route::post('delete-account', [OutlookController::class, 'deleteAccount']);
    });

    Route::prefix('email/imap')->group(function () {
        Route::get('get-accounts', [ImapController::class, 'getEmailAccounts']);
        Route::post('add-account', [ImapController::class, 'addAccount']);
        Route::post('disable-account', [ImapController::class, 'disableAccount']);
        Route::post('enable-account', [ImapController::class, 'enableAccount']);
        Route::post('delete-account', [ImapController::class, 'deleteAccount']);
        Route::post('test-connection', [ImapController::class, 'testExistingAccountConnection']);
        Route::post('get-code-in-email/{platform}', [ImapController::class, 'findCodeInLatestEmail']);
    });

    Route::prefix('statistics')->group(function () {
        Route::get('summary', [StatisticsController::class, 'getSummary']);
        Route::get('logs', [StatisticsController::class, 'getLogs']);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('list', [NotificationController::class, 'getNotifications']);
        Route::get('unread-count', [NotificationController::class, 'getUnreadCount']);
        Route::post('mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('delete', [NotificationController::class, 'deleteNotification']);
    });

    Route::prefix('admin')->group(function () {
        Route::get('feedbacks', [UserFeedbackController::class, 'getFeedbacks']);

        Route::get('platforms', [PlatformController::class, 'index']);
        Route::post('platforms', [PlatformController::class, 'store']);
        Route::put('platforms/{id}', [PlatformController::class, 'update']);
        Route::delete('platforms/{id}', [PlatformController::class, 'destroy']);
        Route::post('platforms/logo', [PlatformController::class, 'uploadLogo']);
        Route::post('platforms/test-regex', [PlatformController::class, 'testRegex']);

        Route::get('assignments/{email}', [PlatformController::class, 'getAssignments']);
        Route::post('assignments', [PlatformController::class, 'saveAssignments']);

        Route::get('settings', [SettingsController::class, 'getSettings']);
        Route::post('settings', [SettingsController::class, 'saveSettings']);
        Route::post('settings/logo', [SettingsController::class, 'uploadLogo']);
        Route::post('settings/test-smtp', [SettingsController::class, 'testSmtp']);
        Route::post('settings/telegram/webhook/toggle', [SettingsController::class, 'toggleTelegramWebhook']);
        Route::get('settings/oauth-config', [SettingsController::class, 'getOAuthConfig']);
        Route::post('settings/oauth-config', [SettingsController::class, 'saveOAuthConfig']);

        Route::apiResource('static-pages', StaticPageController::class);

        Route::get('access-grants', [AccessGrantController::class, 'index']);
        Route::get('access-grants/emails', [AccessGrantController::class, 'getEmails']);
        Route::post('access-grants', [AccessGrantController::class, 'store']);
        Route::post('access-grants/bulk', [AccessGrantController::class, 'storeBulk']);
        Route::delete('access-grants/{id}', [AccessGrantController::class, 'destroy']);
        Route::post('access-grants/revoke-bulk', [AccessGrantController::class, 'revokeBulk']);
        Route::post('access-grants/revoke-tag', [AccessGrantController::class, 'revokeTag']);
        Route::get('access-grants/tags', [AccessGrantController::class, 'getTags']);

        Route::get('account-bundles', [AccountBundleController::class, 'index']);
        Route::post('account-bundles', [AccountBundleController::class, 'store']);
        Route::put('account-bundles/{id}', [AccountBundleController::class, 'update']);
        Route::delete('account-bundles/{id}', [AccountBundleController::class, 'destroy']);

        Route::get('support/threads', [AdminSupportController::class, 'index']);
        Route::get('support/threads/{id}', [AdminSupportController::class, 'show']);
        Route::post('support/threads/{id}/messages', [AdminSupportController::class, 'reply']);
        Route::post('support/threads/{id}/close', [AdminSupportController::class, 'close']);
    });
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthenticationController::class, 'login'])->middleware('throttle:strict_action');
    Route::post('reset-password', [AuthenticationController::class, 'resetPassword'])->middleware('throttle:strict_action');
});

Route::prefix('oauth')->group(function () {
    Route::get('google/callback', [OAuthController::class, 'googleOAuthCallback']);
    Route::get('outlook/callback', [OAuthController::class, 'outlookOAuthCallback']);
});

Route::prefix('public')->middleware('throttle:public_api')->group(function () {
    Route::get('platforms', [PublicGuardController::class, 'getPlatforms']);
    Route::post('fetch-code', [PublicGuardController::class, 'fetchGuardCode'])->middleware('throttle:strict_action');
    Route::post('feedback', [UserFeedbackController::class, 'submitFeedback'])->middleware('throttle:strict_action');
    Route::get('access-grant', [AccessGrantController::class, 'verifyPublic']);
    Route::post('support/messages', [PublicSupportController::class, 'sendMessage'])->middleware('throttle:strict_action');
    Route::get('support/threads/{threadToken}', [PublicSupportController::class, 'getThread']);
    Route::get('static-pages', [StaticPageController::class, 'publicIndex']);
    Route::get('static-pages/{slug}', [StaticPageController::class, 'publicShow']);
});

Route::prefix('webhook')->middleware('throttle:global_api')->group(function () {
    Route::post('telegram/message', [TelegramWebhookController::class, 'handle']);
    Route::post('generate-access', [ExternalWebhookController::class, 'generateAccess']);
    Route::post('generate-access-bulk', [ExternalWebhookController::class, 'generateAccessBulk']);
});

Route::prefix('telegram-api')->middleware('throttle:strict_action')->group(function () {
    Route::post('platforms', [App\Http\Controllers\TelegramWebApiController::class, 'getPlatforms']);
    Route::post('add-bundle', [App\Http\Controllers\TelegramWebApiController::class, 'addBundle']);
});

Route::get('get-time', function (Request $request) {
    return response()->json([
        'now' => Carbon::now()->toString(),
    ]);
})->middleware('throttle:public_api');
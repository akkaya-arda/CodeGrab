<?php

namespace App\Http\Controllers;

use App\Models\AccessGrant;
use Illuminate\Http\Request;

class AccessGrantController extends Controller
{

    public function index(Request $request)
    {
        $grants = AccessGrant::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $grants
        ]);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'account_bundle_id' => 'nullable|integer|exists:account_bundles,id',
            'email' => 'required_without:account_bundle_id|nullable|email',
            'platform' => 'required_without:account_bundle_id|nullable|string',
            'limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'tag' => 'nullable|string|max:255',
            'prefix' => 'nullable|string|max:50',
            'hide_email' => 'nullable|boolean',
        ]);

        $email = '';
        $platform = '';
        $bundleId = isset($data['account_bundle_id']) ? (int) $data['account_bundle_id'] : null;
        $hideEmail = false;

        if ($bundleId) {
            $bundle = \App\Models\AccountBundle::findOrFail($bundleId);
            $email = $bundle->email;
            $platform = $bundle->platform;
            $hideEmail = isset($data['hide_email']) ? (bool) $data['hide_email'] : $bundle->hide_email;
        } else {
            $email = trim($data['email']);
            $platform = trim($data['platform']);
            $hideEmail = isset($data['hide_email']) ? (bool) $data['hide_email'] : false;
        }

        $prefix = isset($data['prefix']) ? trim($data['prefix']) : null;
        $tag = isset($data['tag']) ? trim($data['tag']) : null;

        $grant = AccessGrant::create([
            'account_bundle_id' => $bundleId,
            'token' => AccessGrant::generateToken($prefix),
            'email' => $email,
            'platform' => $platform,
            'tag' => $tag,
            'limit' => (isset($data['limit']) && $data['limit'] !== '') ? (int) $data['limit'] : null,
            'uses' => 0,
            'is_active' => true,
            'expires_at' => (isset($data['expires_at']) && $data['expires_at'] !== '') ? \Carbon\Carbon::parse($data['expires_at']) : null,
            'hide_email' => $hideEmail,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Access token generated successfully.',
            'data' => $grant
        ], 201);
    }


    public function storeBulk(Request $request)
    {
        $data = $request->validate([
            'account_bundle_id' => 'required|integer|exists:account_bundles,id',
            'quantity' => 'required|integer|min:1|max:1000',
            'limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'tag' => 'nullable|string|max:255',
            'prefix' => 'nullable|string|max:50',
            'hide_email' => 'nullable|boolean',
        ]);

        $bundle = \App\Models\AccountBundle::findOrFail($data['account_bundle_id']);
        $grants = [];

        $limit = (isset($data['limit']) && $data['limit'] !== '') ? (int) $data['limit'] : null;
        $expiresAt = (isset($data['expires_at']) && $data['expires_at'] !== '') ? \Carbon\Carbon::parse($data['expires_at']) : null;
        $prefix = isset($data['prefix']) ? trim($data['prefix']) : null;
        $tag = isset($data['tag']) ? trim($data['tag']) : null;
        $hideEmail = isset($data['hide_email']) ? (bool) $data['hide_email'] : $bundle->hide_email;

        for ($i = 0; $i < (int) $data['quantity']; $i++) {
            $grants[] = AccessGrant::create([
                'account_bundle_id' => $bundle->id,
                'token' => AccessGrant::generateToken($prefix),
                'email' => $bundle->email,
                'platform' => $bundle->platform,
                'tag' => $tag,
                'limit' => $limit,
                'uses' => 0,
                'is_active' => true,
                'expires_at' => $expiresAt,
                'hide_email' => $hideEmail,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully generated {$data['quantity']} tokens.",
            'data' => $grants
        ], 201);
    }


    public function destroy($id)
    {
        $grant = AccessGrant::find($id);

        if (!$grant) {
            return response()->json([
                'success' => false,
                'message' => 'Access grant not found.'
            ], 404);
        }

        $grant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Access token revoked and deleted successfully.'
        ]);
    }


    public function revokeBulk(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:access_grants,id',
        ]);

        AccessGrant::whereIn('id', $data['ids'])->delete();

        return response()->json([
            'success' => true,
            'message' => 'Selected access tokens revoked and deleted successfully.'
        ]);
    }


    public function revokeTag(Request $request)
    {
        $data = $request->validate([
            'tag' => 'required|string|max:255',
        ]);

        AccessGrant::where('tag', $data['tag'])->delete();

        return response()->json([
            'success' => true,
            'message' => "All access tokens under tag '{$data['tag']}' revoked and deleted successfully."
        ]);
    }


    public function getTags()
    {
        $tags = AccessGrant::whereNotNull('tag')
            ->where('tag', '!=', '')
            ->distinct()
            ->pluck('tag')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => array_values($tags)
        ]);
    }


    public function verifyPublic(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token parameter is missing.'
            ], 400);
        }

        $grant = AccessGrant::where('token', $token)->first();

        if (!$grant) {
            return response()->json([
                'success' => false,
                'message' => 'This invitation link is invalid or does not exist.'
            ], 404);
        }

        if (!$grant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This invitation link has been deactivated.'
            ], 403);
        }

        if ($grant->expires_at && $grant->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'This invitation link has expired.'
            ], 403);
        }

        if ($grant->limit !== null && $grant->uses >= $grant->limit) {
            return response()->json([
                'success' => false,
                'message' => 'This invitation link has reached its usage limit.'
            ], 403);
        }

        $bundleData = null;
        $bundle = null;
        if ($grant->account_bundle_id) {
            $bundle = $grant->accountBundle;
        } else {
            $bundle = \App\Models\AccountBundle::where('email', $grant->email)
                ->where('platform', $grant->platform)
                ->where('is_active', true)
                ->first();
        }

        if ($bundle) {
            $bundleData = [
                'email' => $bundle->email,
                'login_username' => $bundle->login_username,
                'password' => $bundle->password,
                'platform' => $bundle->platform,
            ];
        }

        $hideEmail = (bool) $grant->hide_email;
        if ($bundle && $bundle->hide_email) {
            $hideEmail = true;
        }

        $responseEmail = $grant->email;
        if ($hideEmail) {
            $responseEmail = $this->maskEmail($responseEmail);
            if ($bundleData) {
                $bundleData['email'] = $this->maskEmail($bundleData['email']);
                /*if ($bundleData['login_username']) {
                    $bundleData['login_username'] = $this->maskEmail($bundleData['login_username']);
                }*/
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $grant->token,
                'email' => $responseEmail,
                'platform' => $grant->platform,
                'hide_email' => $hideEmail,
                'limit' => $grant->limit,
                'uses' => $grant->uses,
                'remaining' => $grant->limit !== null ? max(0, $grant->limit - $grant->uses) : null,
                'expires_at' => $grant->expires_at ? $grant->expires_at->toIso8601String() : null,
                'account_bundle' => $bundleData,
                'support_portal_enabled' => \App\Models\Setting::getValue('support_portal_enabled', '0') === '1',
                'support_mode' => \App\Models\Setting::getValue('support_mode', 'built_in'),
                'support_custom_script' => \App\Models\Setting::getValue('support_custom_script', ''),
                'system_name' => \App\Models\Setting::getValue('system_name', ''),
                'system_logo' => \App\Models\Setting::getValue('system_logo', ''),
                'logo_enabled' => \App\Models\Setting::getValue('logo_enabled', '1') === '1',
                'theme_primary_color' => \App\Models\Setting::getValue('theme_primary_color', '#4f46e5'),
                'theme_accent_color' => \App\Models\Setting::getValue('theme_accent_color', '#6366f1'),
                'theme_font_family' => \App\Models\Setting::getValue('theme_font_family', 'Pacifico'),
                'system_slogan_title' => \App\Models\Setting::getValue('system_slogan_title', 'Access Portal'),
                'system_slogan_subtitle' => \App\Models\Setting::getValue('system_slogan_subtitle', 'Retrieve your 2FA codes easily.'),
                'copyright_text' => \App\Models\Setting::getValue('copyright_text', ''),
                'hide_access_restricted_info' => \App\Models\Setting::getValue('hide_access_restricted_info', '0') === '1',
                'light_mode' => \App\Models\Setting::getValue('light_mode', '0') === '1',
                'public_portal_title' => \App\Models\Setting::getValue('public_portal_title') ?: \App\Models\Setting::getValue('system_name', 'Guard Helper'),
            ]
        ]);
    }

    private function maskEmail(?string $email): string
    {
        if (!$email) {
            return '';
        }

        if (str_contains($email, '@')) {
            $parts = explode('@', $email);
            $local = $parts[0];
            $domain = $parts[1] ?? '';

            if (strlen($local) <= 1) {
                return '*@' . $domain;
            } elseif (strlen($local) === 2) {
                return $local[0] . '*@' . $domain;
            } else {
                return $local[0] . '***' . $local[strlen($local) - 1] . '@' . $domain;
            }
        }

        if (strlen($email) <= 1) {
            return '*';
        } elseif (strlen($email) === 2) {
            return $email[0] . '*';
        } else {
            return $email[0] . '***' . $email[strlen($email) - 1];
        }
    }


    public function getEmails()
    {
        $gmails = \App\Models\GmailAccount::pluck('email')->toArray();
        $outlooks = \App\Models\OutlookAccount::pluck('email')->toArray();
        $imaps = \App\Models\ImapAccount::pluck('email')->toArray();

        $allEmails = array_values(array_unique(array_merge($gmails, $outlooks, $imaps)));

        return response()->json([
            'success' => true,
            'data' => $allEmails
        ]);
    }
}

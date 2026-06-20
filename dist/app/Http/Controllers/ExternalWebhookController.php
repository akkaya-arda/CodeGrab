<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\AccessGrant;
use App\Models\PlatformGuardEmailFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ExternalWebhookController extends Controller
{
    
    public function generateAccess(Request $request)
    {
        $secretKey = Setting::getValue('webhook_secret_key');

        if (empty($secretKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook secret key is not configured in settings.'
            ], 403);
        }

        
        $signature = $request->header('X-Webhook-Signature');
        $staticSecret = $request->header('X-Webhook-Secret');
        $authenticated = false;

        if ($signature) {
            
            $expectedSignature = hash_hmac('sha256', $request->getContent(), $secretKey);
            $authenticated = hash_equals($expectedSignature, $signature);
        } elseif ($staticSecret) {
            
            $authenticated = hash_equals($secretKey, $staticSecret);
        }

        if (!$authenticated) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized request. Invalid webhook signature or secret.'
            ], 401);
        }

        
        $data = $request->validate([
            'email' => 'required|email',
            'platform' => 'required|string',
            'limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'tag' => 'nullable|string|max:255',
            'prefix' => 'nullable|string|max:50',
            'hide_email' => 'nullable|boolean',
        ]);

        $platformName = trim($data['platform']);
        $email = trim($data['email']);
        $limit = array_key_exists('limit', $data) ? $data['limit'] : 20;
        $expiresAt = (isset($data['expires_at']) && $data['expires_at'] !== '') ? \Carbon\Carbon::parse($data['expires_at']) : null;
        $tag = isset($data['tag']) ? trim($data['tag']) : null;
        $prefix = isset($data['prefix']) ? trim($data['prefix']) : null;

        
        $platformExists = PlatformGuardEmailFilter::where('name', $platformName)->exists();
        if (!$platformExists) {
            $available = PlatformGuardEmailFilter::pluck('name')->toArray();
            return response()->json([
                'success' => false,
                'message' => "Platform '{$platformName}' is not registered in the system. Available platforms: " . implode(', ', $available)
            ], 400);
        }

        
        $grant = AccessGrant::create([
            'token' => AccessGrant::generateToken($prefix),
            'email' => $email,
            'platform' => $platformName,
            'tag' => $tag,
            'limit' => $limit,
            'uses' => 0,
            'is_active' => true,
            'expires_at' => $expiresAt,
            'hide_email' => isset($data['hide_email']) ? (bool) $data['hide_email'] : false,
        ]);

        $frontendUrl = Setting::getValue('frontend_url', 'http://localhost:4200');
        $accessLink = rtrim($frontendUrl, '/') . '/grab-code?token=' . $grant->token;

        return response()->json([
            'success' => true,
            'message' => 'Access token generated successfully.',
            'token' => $grant->token,
            'access_url' => $accessLink,
            'data' => [
                'id' => $grant->id,
                'email' => $grant->email,
                'platform' => $grant->platform,
                'limit' => $grant->limit,
                'uses' => $grant->uses,
                'is_active' => $grant->is_active,
                'created_at' => $grant->created_at,
            ]
        ]);
    }

    
    public function generateAccessBulk(Request $request)
    {
        $secretKey = Setting::getValue('webhook_secret_key');

        if (empty($secretKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook secret key is not configured in settings.'
            ], 403);
        }

        
        $signature = $request->header('X-Webhook-Signature');
        $staticSecret = $request->header('X-Webhook-Secret');
        $authenticated = false;

        if ($signature) {
            
            $expectedSignature = hash_hmac('sha256', $request->getContent(), $secretKey);
            $authenticated = hash_equals($expectedSignature, $signature);
        } elseif ($staticSecret) {
            
            $authenticated = hash_equals($secretKey, $staticSecret);
        }

        if (!$authenticated) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized request. Invalid webhook signature or secret.'
            ], 401);
        }

        
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
        $limit = array_key_exists('limit', $data) ? $data['limit'] : 20;
        $expiresAt = (isset($data['expires_at']) && $data['expires_at'] !== '') ? \Carbon\Carbon::parse($data['expires_at']) : null;
        $tag = isset($data['tag']) ? trim($data['tag']) : null;
        $prefix = isset($data['prefix']) ? trim($data['prefix']) : null;
        $hideEmail = isset($data['hide_email']) ? (bool) $data['hide_email'] : $bundle->hide_email;

        $grants = [];
        $frontendUrl = Setting::getValue('frontend_url', 'http://localhost:4200');

        for ($i = 0; $i < (int)$data['quantity']; $i++) {
            $grant = AccessGrant::create([
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

            $accessLink = rtrim($frontendUrl, '/') . '/grab-code?token=' . $grant->token;
            $grants[] = [
                'token' => $grant->token,
                'access_url' => $accessLink
            ];
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully generated {$data['quantity']} tokens.",
            'tokens' => $grants
        ]);
    }
}

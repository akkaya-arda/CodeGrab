<?php

namespace App\Http\Controllers;

use App\Infrastructure\Google\Services\GmailService;
use App\Models\GmailAccount;
use App\Models\PlatformGuardEmailFilter;
use Illuminate\Http\Request;

class GmailController extends Controller
{
    public function __construct(private GmailService $gmailService)
    {
    }
    public function findCodeInLatestEmail(Request $request, string $platform)
    {
        $platformData = PlatformGuardEmailFilter::where('name', $platform)->first();
        if (!$platformData) {
            return response()->json(['success' => 'false', 'message' => 'The specified platform doesn\'t exist.']);
        }

        $codeResult = $this->gmailService->findCodeInLatestEmail($request->post('to'), $platformData->sender, $platformData->regex, $platformData->subject);
        if ($codeResult['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Code successfully grabbed.',
                'code' => $codeResult['data'],
                'date' => $codeResult['date'] ?? null
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => $codeResult['message'],
            'error' => $codeResult['error'] ?? null
        ]);
    }
    public function getEmailAccounts(Request $request)
    {
        $accounts = GmailAccount::all();
        return response()->json([
            'success' => true,
            'message' => 'Email accounts listed successfully.',
            'data' => $accounts
        ]);
    }

    public function disableAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $gmailAccount = GmailAccount::where('email', $request->post('email'))->first();
        if (!$gmailAccount) {
            return response()->json([
                'success' => false,
                'message' => 'The specified email doesn\'t exist.'
            ]);
        }
        $gmailAccount->is_active = false;
        $gmailAccount->save();
        return response()->json([
            'success' => true,
            'message' => 'Email account disabled successfully.',
        ]);
    }

    public function enableAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $gmailAccount = GmailAccount::where('email', $request->post('email'))->first();
        if (!$gmailAccount) {
            return response()->json([
                'success' => false,
                'message' => 'The specified email doesn\'t exist.'
            ]);
        }
        $gmailAccount->is_active = true;
        $gmailAccount->save();
        return response()->json([
            'success' => true,
            'message' => 'Email account enabled successfully.',
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $gmailAccount = GmailAccount::where('email', $request->post('email'))->first();
        if (!$gmailAccount) {
            return response()->json([
                'success' => false,
                'message' => 'The specified email doesn\'t exist.'
            ]);
        }
        $gmailAccount->delete();
        return response()->json([
            'success' => true,
            'message' => 'Email account deleted successfully.',
        ]);
    }
}

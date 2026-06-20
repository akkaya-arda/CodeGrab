<?php

namespace App\Http\Controllers;

use App\Models\OutlookAccount;
use App\Models\PlatformGuardEmailFilter;
use App\Infrastructure\Outlook\Services\OutlookService;
use Illuminate\Http\Request;

class OutlookController extends Controller
{
    public function __construct(private OutlookService $outlookService)
    {
    }

    public function findCodeInLatestEmail(Request $request, string $platform)
    {
        $platformData = PlatformGuardEmailFilter::where('name', $platform)->first();
        if (!$platformData) {
            return response()->json(['success' => 'false', 'message' => 'The specified platform doesn\'t exist.']);
        }

        $codeResult = $this->outlookService->findCodeInLatestEmail($request->post('to'), $platformData->sender, $platformData->regex, $platformData->subject);
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
        $accounts = OutlookAccount::all();
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

        $outlookAccount = OutlookAccount::where('email', $request->post('email'))->first();
        if (!$outlookAccount) {
            return response()->json([
                'success' => false,
                'message' => 'The specified email doesn\'t exist.'
            ]);
        }
        $outlookAccount->is_active = false;
        $outlookAccount->save();
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

        $outlookAccount = OutlookAccount::where('email', $request->post('email'))->first();
        if (!$outlookAccount) {
            return response()->json([
                'success' => false,
                'message' => 'The specified email doesn\'t exist.'
            ]);
        }
        $outlookAccount->is_active = true;
        $outlookAccount->save();
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

        $outlookAccount = OutlookAccount::where('email', $request->post('email'))->first();
        if (!$outlookAccount) {
            return response()->json([
                'success' => false,
                'message' => 'The specified email doesn\'t exist.'
            ]);
        }
        $outlookAccount->delete();
        return response()->json([
            'success' => true,
            'message' => 'Email account deleted successfully.',
        ]);
    }
}

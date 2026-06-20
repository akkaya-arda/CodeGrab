<?php

namespace App\Http\Controllers;

use App\Infrastructure\Imap\Services\ImapService;
use App\Models\ImapAccount;
use App\Models\PlatformGuardEmailFilter;
use Illuminate\Http\Request;

class ImapController extends Controller
{
    public function __construct(private ImapService $imapService)
    {
    }

    public function getEmailAccounts(Request $request)
    {
        $accounts = ImapAccount::all();
        
        foreach ($accounts as $account) {
            unset($account->password);
        }
        return response()->json([
            'success' => true,
            'message' => 'IMAP accounts listed successfully.',
            'data' => $accounts
        ]);
    }

    public function addAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:imap_accounts,email',
            'host' => 'required|string',
            'port' => 'required|integer',
            'encryption' => 'required|in:ssl,tls,none',
            'password' => 'required|string',
        ]);

        
        $testResult = $this->imapService->testConnection([
            'email' => $request->post('email'),
            'host' => $request->post('host'),
            'port' => $request->post('port'),
            'encryption' => $request->post('encryption'),
            'password' => $request->post('password'),
        ]);

        if (!$testResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed. Unable to add account: ' . $testResult['message'],
            ], 400);
        }

        $account = ImapAccount::create([
            'email' => $request->post('email'),
            'host' => $request->post('host'),
            'port' => $request->post('port'),
            'encryption' => $request->post('encryption'),
            'password' => $request->post('password'), 
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'IMAP account verified and added successfully.',
            'data' => $account
        ]);
    }

    public function disableAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $account = ImapAccount::where('email', $request->post('email'))->first();
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'The specified IMAP email account doesn\'t exist.'
            ], 404);
        }

        $account->is_active = false;
        $account->save();

        return response()->json([
            'success' => true,
            'message' => 'IMAP account disabled successfully.',
        ]);
    }

    public function enableAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $account = ImapAccount::where('email', $request->post('email'))->first();
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'The specified IMAP email account doesn\'t exist.'
            ], 404);
        }

        $account->is_active = true;
        $account->save();

        return response()->json([
            'success' => true,
            'message' => 'IMAP account enabled successfully.',
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $account = ImapAccount::where('email', $request->post('email'))->first();
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'The specified IMAP email account doesn\'t exist.'
            ], 404);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'IMAP account deleted successfully.',
        ]);
    }

    public function testExistingAccountConnection(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $account = ImapAccount::where('email', $request->post('email'))->first();
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'The specified IMAP email account doesn\'t exist.'
            ], 404);
        }

        $testResult = $this->imapService->testConnection([
            'email' => $account->email,
            'host' => $account->host,
            'port' => $account->port,
            'encryption' => $account->encryption,
            'password' => $account->password, 
        ]);

        if ($testResult['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Connection test successful!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $testResult['message']
        ], 400);
    }

    public function findCodeInLatestEmail(Request $request, string $platform)
    {
        $request->validate([
            'to' => 'required|email'
        ]);

        $platformData = PlatformGuardEmailFilter::where('name', $platform)->first();
        if (!$platformData) {
            return response()->json(['success' => false, 'message' => 'The specified platform doesn\'t exist.'], 400);
        }

        $codeResult = $this->imapService->findCodeInLatestEmail(
            $request->post('to'),
            $platformData->sender,
            $platformData->regex,
            $platformData->subject
        );

        if ($codeResult['success']) {
            
            $account = ImapAccount::where('email', $request->post('to'))->first();
            if ($account) {
                $account->increment('fetch_count');
                $account->update(['last_used_at' => now()]);
            }

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
        ], 400);
    }
}

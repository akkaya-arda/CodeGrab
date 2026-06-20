<?php

namespace App\Http\Controllers;

use App\Models\AccountBundle;
use App\Models\PlatformGuardEmailFilter;
use Illuminate\Http\Request;

class AccountBundleController extends Controller
{
    
    public function index()
    {
        $bundles = AccountBundle::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $bundles
        ]);
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'login_username' => 'nullable|string|max:255',
            'platform' => 'required|string|max:255',
            'password' => 'required|string',
            'is_active' => 'nullable|boolean',
            'hide_email' => 'nullable|boolean',
        ]);

        
        $platformExists = PlatformGuardEmailFilter::where('name', $data['platform'])->exists();
        if (!$platformExists) {
            return response()->json([
                'success' => false,
                'message' => "Platform '{$data['platform']}' is not registered."
            ], 400);
        }

        $bundle = AccountBundle::create([
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'login_username' => isset($data['login_username']) ? trim($data['login_username']) : null,
            'platform' => trim($data['platform']),
            'password' => $data['password'], 
            'is_active' => $data['is_active'] ?? true,
            'hide_email' => isset($data['hide_email']) ? (bool)$data['hide_email'] : false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account bundle created successfully.',
            'data' => $bundle
        ], 201);
    }

    
    public function update(Request $request, $id)
    {
        $bundle = AccountBundle::find($id);

        if (!$bundle) {
            return response()->json([
                'success' => false,
                'message' => 'Account bundle not found.'
            ], 404);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'login_username' => 'nullable|string|max:255',
            'platform' => 'required|string|max:255',
            'password' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'hide_email' => 'nullable|boolean',
        ]);

        
        $platformExists = PlatformGuardEmailFilter::where('name', $data['platform'])->exists();
        if (!$platformExists) {
            return response()->json([
                'success' => false,
                'message' => "Platform '{$data['platform']}' is not registered."
            ], 400);
        }

        $bundle->name = trim($data['name']);
        $bundle->email = trim($data['email']);
        $bundle->login_username = isset($data['login_username']) ? trim($data['login_username']) : null;
        $bundle->platform = trim($data['platform']);
        $bundle->is_active = $data['is_active'] ?? $bundle->is_active;
        $bundle->hide_email = isset($data['hide_email']) ? (bool)$data['hide_email'] : $bundle->hide_email;

        if (isset($data['password']) && $data['password'] !== '') {
            $bundle->password = $data['password'];
        }

        $bundle->save();

        return response()->json([
            'success' => true,
            'message' => 'Account bundle updated successfully.',
            'data' => $bundle
        ]);
    }

    
    public function destroy($id)
    {
        $bundle = AccountBundle::find($id);

        if (!$bundle) {
            return response()->json([
                'success' => false,
                'message' => 'Account bundle not found.'
            ], 404);
        }

        $bundle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account bundle deleted successfully.'
        ]);
    }
}

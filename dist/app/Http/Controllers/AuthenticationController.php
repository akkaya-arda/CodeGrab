<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticationController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = $request->user();
            $token = $user->createToken('guard-helper')->plainTextToken;

            return response()->json([
                'token' => $token,
                'success' => true,
            ]);
        }

        return response()->json([
            'message' => 'Invalid credentials',
            'success' => false,
        ], 400);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'User logged out successfully',
            'success' => true,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'required|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        
        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided current password does not match our records.'
            ], 422);
        }

        
        $user->name = $request->name;
        $user->email = $request->email;

        
        if ($request->filled('new_password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Account settings updated successfully.',
            'data' => $user
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = trim($request->email);
        $filePath = base_path('reset-password.txt');

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'The verification file reset-password.txt was not found in the API root directory.'
            ], 400);
        }

        $fileContent = trim(file_get_contents($filePath));

        if (strcasecmp($fileContent, $email) !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'The email content in reset-password.txt does not match your email address.'
            ], 400);
        }

        $user = \App\Models\User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No administrator found with this email address.'
            ], 404);
        }

        $user->password = \Illuminate\Support\Facades\Hash::make('12345678');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Your password has been successfully reset to 12345678.'
        ]);
    }
}


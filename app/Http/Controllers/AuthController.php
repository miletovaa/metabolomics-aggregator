<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Exchange email/password credentials for a Sanctum API token.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => $user,
        ]);
    }

    /**
     * Revoke the token used to authenticate the current request.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}

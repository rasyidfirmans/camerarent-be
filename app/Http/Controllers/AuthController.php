<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'username' => 'required|string|max:255|unique:users',
            'phone_number' => 'required|string|min:8|max:255',
            'citizenship_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $validated['password'] = hash('sha256', $validated['password']);
        $uploadFolder = 'storage/images/ctz/';
        $image = $request->file('citizenship_image');
        $imageName = time() . '_' . $image->getClientOriginalName();
        $image->move($uploadFolder, $imageName);
        $validated['citizenship_image'] = 'images/citizenship/' . $imageName;

        $user = \App\Models\User::create($validated);

        $at_expiration = now()->addMinutes(60);
        $access_token = $user->createToken('access_token', ['access-token'], $at_expiration)->plainTextToken;
        $rt_expiration = now()->addDays(30);
        $refresh_token = $user->createToken('refresh_token', ['refresh-token'], $rt_expiration)->plainTextToken;

        return response()->json([
            'code' => 201,
            'message' => 'User registered successfully',
            'access_token' => $access_token,
            'refresh_token' => $refresh_token
        ], 201);
    }

    public function login(Request $request) {
        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        $user = User::where('username', $validated['username'])->first();
        if (!$user) {
            return response()->json([
                'code' => 404,
                'message' => 'User not found',
            ], 404);
        }
        if ($user && hash('sha256', $validated['password']) !== $user->password) {
            return response()->json([
                'code' => 401,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token_abilities = ['access-token'];
        if ($user && $user->is_admin === 1) {
            $token_abilities = ['access-token', 'access-admin'];
        }

        $at_expiration = now()->addMinutes(60);
        $access_token = $user->createToken('access_token', $token_abilities, $at_expiration)->plainTextToken;
        $rt_expiration = now()->addDays(30);
        $refresh_token = $user->createToken('refresh_token', ['refresh-token'], $rt_expiration)->plainTextToken;

        return response()->json([
            'code' => 200,
            'message' => 'User logged in successfully',
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
        ], 200);
    }

    public function logout(Request $request) {
        $user = $request->user();
        if ($user) {
            $user->tokens()->delete();
            return response()->json([
                'code' => 200,
                'message' => 'User logged out successfully',
            ], 200);
        }
    }
}



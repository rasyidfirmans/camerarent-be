<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function refresh(Request $request) {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized',
            ], 401);
        }

        $at_expiration = now()->addMinutes(60);
        $access_token = $user->createToken('access_token', ['access-token'], $at_expiration)->plainTextToken;

        return response()->json([
            'code' => 200,
            'message' => 'Tokens refreshed successfully',
            'access_token' => $access_token,
        ], 200);
    }
}

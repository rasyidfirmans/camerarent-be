<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
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

        $user = User::where('id', $user->id)->first();
        $token_abilities = ['access-token'];
        if ($user && $user->is_admin === 1) {
            $token_abilities = ['access-token', 'access-admin'];
        }

        $at_expiration = now()->addMinutes(60);
        $access_token = $user->createToken('access_token', $token_abilities, $at_expiration)->plainTextToken;

        return response()->json([
            'code' => 200,
            'message' => 'Tokens refreshed successfully',
            'access_token' => $access_token,
        ], 200);
    }
}

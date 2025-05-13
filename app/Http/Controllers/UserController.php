<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getCurrentUser(Request $request)
    {
        return response()->json([
            'code' => 200,
            'message' => 'User retrieved successfully',
            'data' => $request->user()->only([
                'name',
                'email',
            ]),
        ], 200);
    }
}

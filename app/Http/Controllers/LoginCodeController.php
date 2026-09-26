<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LoginCodeController extends Controller
{
    public function sendCode(Request $request)
    {
        return response()->json([
            'message' => 'Login code sent successfully.',
        ], 200);
    }
}
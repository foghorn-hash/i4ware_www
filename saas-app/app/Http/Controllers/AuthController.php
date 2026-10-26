<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function verifyAccount($token)
    {
        return response()->json(['status' => 'verified', 'token' => $token]);
    }

    public function showResetPasswordForm($token)
    {
        return response()->json(['status' => 'reset_form', 'token' => $token]);
    }
}

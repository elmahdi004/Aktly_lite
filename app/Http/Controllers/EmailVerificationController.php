<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailVerification;
use App\Models\User;
use Carbon\Carbon;

class EmailVerificationController extends Controller
{
    public function verify($token)
    {
        $verification = EmailVerification::where('token', $token)->first();
        if (!$verification) {
            return response()->json(['error' => 'Invalid or expired token'], 404);
        }
        if ($verification->verified_at) {
            return response()->json(['message' => 'Email already verified']);
        }
        $verification->verified_at = Carbon::now();
        $verification->save();
        $user = $verification->user;
        $user->email_verified_at = Carbon::now();
        $user->save();
        return response()->json(['message' => 'Email verified successfully']);
    }
}

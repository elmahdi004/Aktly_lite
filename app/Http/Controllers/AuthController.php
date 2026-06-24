<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $blockedEmailDomains = [
            // 'gmail.com',
            // 'googlemail.com',
            // 'yahoo.com',
            // 'yahoo.fr',
            // 'yahoo.co.uk',
            // 'hotmail.com',
            // 'hotmail.fr',
            // 'outlook.com',
            // 'outlook.fr',
            // 'live.com',
            // 'live.fr',
            // 'msn.com',
            // 'icloud.com',
            // 'me.com',
            // 'aol.com',
            // 'protonmail.com',
            // 'proton.me',
            // 'zoho.com',
            // 'gmx.com',
            // 'gmx.net',
            // 'mail.com',
        ];

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                function ($attribute, $value, $fail) use ($blockedEmailDomains) {
                    $value = strtolower(trim((string) $value));
                    $domain = str_contains($value, '@') ? substr(strrchr($value, '@'), 1) : '';
                    if ($domain && in_array($domain, $blockedEmailDomains, true)) {
                        $fail('Veuillez utiliser une adresse e-mail professionnelle.');
                    }
                },
            ],
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        //if($this->isAuthorize($request->email)) {
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'langue_user' => $request->langue_user ?? 'fr',
            ]);

            // Create email verification token
            $verificationToken = bin2hex(random_bytes(32));
            \App\Models\EmailVerification::create([
                'user_id' => $user->id,
                'token' => $verificationToken,
            ]);

            // Send verification email with frontend link
            $frontendUrl = env('APP_URL');
            $verificationUrl = $frontendUrl . '/verify-email/' . $verificationToken;
            \Mail::to($user->email)->send(new \App\Mail\EmailVerificationMail($verificationUrl, $request->langue_user ?? 'fr'));

            $token = $user->createToken('MyApp')->plainTextToken;

            return response()->json(['token' => $token, 'email_verification_sent' => true]);
       
    }

   public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        $allowedLanguages = ['fr', 'nl'];
        if ($user && $request->langue_user && in_array($request->langue_user, $allowedLanguages, true)) {
            $user->langue_user = $request->langue_user;
            $user->save();
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // if($user->status != 'accepted') {
        //     return response()->json(['message' => 'not verified'], 401);
        // }

        $token = $user->createToken('MyApp')->plainTextToken;
        $email_verified_at = DB::table('email_verifications')
            ->where('user_id', $user->id)
            ->value('verified_at');


        // ✅ Return token + credits
        return response()->json([
            'token' => $token,
            'credits' => $user->credits ?? 0,
            'email_verified_at' => $email_verified_at,
        ]);
    }


    public function logout(Request $request)
    {
        $request->user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateLanguage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $allowedLanguages = ['fr', 'nl'];
        $language = $request->input('language');
        if (!in_array($language, $allowedLanguages, true)) {
            return response()->json(['errors' => ['language' => ['Invalid language value.']]], 422);
        }
        $user = auth()->user();
        $user->langue_user = $language;
        $user->save();

        return response()->json(['message' => 'Langue sauvegardée avec succès !']);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8|confirmed',
        ], [
            'new_password.confirmed' => 'Le nouveau mot de passe et sa confirmation ne correspondent pas.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        if (!$user || !Hash::check($request->input('current_password'), $user->password)) {
            return response()->json(['errors' => ['current_password' => ['Ancien mot de passe incorrect.']]], 422);
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        return response()->json(['message' => 'Mot de passe mis à jour avec succès.']);
    }

    private function isAuthorize($email)
    {
        return \DB::table('authorize_users')->where('email', $email)->exists();
    }

    public function updateVeriffSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'veriff_session' => 'required|integer',
            'id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::find($request->input('id'));
        $user->veriff_id = $request->input('veriff_session');
        $user->save();

        return response()->json(['message' => 'ID Analyzer session updated successfully']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    // Step 1: Get Google redirect URL
    public function redirect()
    {
        $url = Socialite::driver('google')
                    ->stateless()
                    ->redirect()
                    ->getTargetUrl();

        return response()->json([
            'status'       => true,
            'redirect_url' => $url,
        ]);
    }

    // Step 2: Google calls this with ?code= automatically
    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')
                            ->stateless()
                            ->user();
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Google authentication failed.',
                'error'   => $e->getMessage(),
            ], 401);
        }
        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name'        => $googleUser->user['given_name']  ?? $googleUser->getName(),
                'password'          => bcrypt(Str::random(16)),
                'email_verified_at' => now(),
                'google_id'         => $googleUser->user['id'],
            ]
        );

        $token = $user->createToken('google-auth-token')->plainTextToken;

        return response()->json([
            'status' => true,
            'token'  => $token,
            'user'   => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'google_id'  => $user->google_id
            ],
        ]);
    }
}


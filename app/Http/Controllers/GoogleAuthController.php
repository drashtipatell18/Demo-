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
                'name'              => $googleUser->user['given_name'] ?? $googleUser->getName(),
                'password'          => bcrypt(Str::random(16)),
                'email_verified_at' => now(),
                'google_id'         => $googleUser->getId(),
            ]
        );

        $token = $user->createToken('google-auth-token')->plainTextToken;

        return response()->json([
            'status' => true,
            'token'  => $token,
            'user'   => [
                'id'        => $user->id,
                'FirstName'      => $user->name,
                'email'     => $user->email,
                'google_id' => $user->google_id,
            ],
        ]);
    }

    // Add this function in GoogleAuthController

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'user_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
            'image' => 'sometimes|image|max:2048',
        ]);

        $user = $request->user();

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('user_name')) {
            $user->user_name = $request->user_name;
        }

        if ($request->hasFile('image')) {
            // Delete old photo if exists
            if ($user->image && file_exists(public_path('images/Users/' . $user->image))) {
                unlink(public_path('images/Users/' . $user->image));
            }

            $file     = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/Users'), $filename);
            $user->image = $filename;
        }

        $user->save();

        return response()->json([
            'status'  => true,
            'message' => 'Profile updated successfully.',
            'user'    => [
                'id'        => $user->id,
                'FirstName'      => $user->name,
                'email'     => $user->email,
                'UserName'     => $user->user_name,
                'image'     => $user->image
                                ? url('images/Users/' . $user->image)
                                : null,
                'google_id' => $user->google_id,
            ],
        ]);
    }

    // Step 3: Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}

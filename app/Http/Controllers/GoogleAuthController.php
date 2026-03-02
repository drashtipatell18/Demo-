<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class GoogleAuthController extends Controller
{

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'fullName' => 'required|string|max:255',
            'email'    => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

         $imageName = null;
        if ($request->hasFile('image')) {
            $file      = $request->file('image');
            $imageName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/Users'), $imageName);
        }
        // First time → create, already exists → login
        $user = User::updateOrCreate(
            ['email' => $request->email],  // check by email
            [
                'name'       => $request->fullName,
                'user_name'  => $request->username,
                'image'             => $imageName,
                'password'   => Hash::make($request->new_password),
                'email_verified_at' => now(),
            ]
        );

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => $user->wasRecentlyCreated ? 'Account created successfully.' : 'Login successful.',

            'user'    => [
                'id'       => $user->id,
                'username' => $user->user_name,
                'fullName' => $user->name,
                'email'    => $user->email,
                'image'    => $user->image
                            ? url('images/Users/' . $user->image)
                            : null,
                'token'   => $token,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
       $validator = Validator::make($request->all(), [
            'name'  => 'sometimes|string|max:255',
            'user_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
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

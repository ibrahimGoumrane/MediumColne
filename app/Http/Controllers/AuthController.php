<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:4096', // Validate profile image (optional)
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed', // Ensure passwords match
        ]);

        // Default profile photo path
        $profilePhotoPath = '/storage/uploads/profile/default.png'; // Ensure this exists in storage/app/uploads/profile/

        // Check if 'profile_image' file is uploaded
        if ($request->hasFile('profile_image')) {
            $profilePhoto = $request->file('profile_image'); // Get uploaded file

            // Save the file to 'uploads/profile/' with a unique name
            $profilePhotoName = time() . '_' . $profilePhoto->getClientOriginalName(); // Unique file name
            $profilePhotoPath = $profilePhoto->storeAs('uploads/profile', $profilePhotoName, 'public'); // Efficient storage
        }

        // Create user with hashed password and the profile image
        $user = User::create([
            'first_name' => $fields['first_name'],
            'last_name' => $fields['last_name'],
            'email' => $fields['email'],
            'profile_image' => '/storage/'. $profilePhotoPath, // Path to profile image
            'password' => Hash::make($fields['password']), // Hash the password before storing
        ]);

        // Generate a token for the user
        $token = $user->createToken($request->first_name);

        // Return response with the user and token
        return [
            'user' => $user->load(['blogs', 'comments', 'likes']),
            'token' => $token->plainTextToken,
        ];
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return [
                'errors' => [
                    'email' => ['The provided credentials are incorrect.']
                ]
            ];
        }

        $token = $user->createToken($user->first_name);

        return [
            'user' => $user->load(['blogs', 'comments', 'likes']),
            'token' => $token->plainTextToken
        ];
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return [
            'message' => 'You are logged out.'
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Access\Gate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller implements HasMiddleware
{

    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return User::with(['blogs', 'comments', 'likes'])->latest()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json([
            'user' => $user->load(['blogs', 'comments', 'likes']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Authorize the user for updating the resource
        Gate::authorize('update', $user);

        // Validate the request data, including the profile image if provided
        $validated = $request->validate([
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id, // Ignore unique check for the current user
            'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:4096', // Profile image validation
        ]);

        // Handle profile image upload if present
        if ($request->hasFile('profile_image')) {
            $profileImage = $request->file('profile_image');

            // Generate a new filename for the uploaded file
            $profileImageName = 'uploads/profile/' . time() . '_' . $profileImage->getClientOriginalName();

            // Save the new profile image using the storage system
            Storage::disk('public')->put($profileImageName, file_get_contents($profileImage));

            // Optionally, delete the old profile image if it's not a default one
            if ($user->profile_image && $user->profile_image !== '/storage/uploads/profile/default.png') {
                Storage::disk('public')->delete($user->profile_image);
            }

            // Update the validated data with the new profile image path
            $validated['profile_image'] = '/storage/' . $profileImageName ;
        }

        // Update the user with the validated (and modified) data
        $user->update($validated);

        // Return a JSON response
        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user,
        ], 200);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        Gate::authorize('update', $user);
            $user->delete();
            return response()->json([
                'message' => 'User deleted successfully.',
            ], 200);
    }
}

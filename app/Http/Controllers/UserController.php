<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Access\Gate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

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
        Gate::authorize('update', $user);
        $validated= $request->validate([
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|unique:users',
        ]);
        $user->update($validated);
        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user->load(['blogs', 'comments', 'likes'])
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

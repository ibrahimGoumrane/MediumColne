<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class BlogController extends Controller implements HasMiddleware
{

    public static function middleware(){
    return [
        new Middleware('auth:sanctum', except: ['index', 'show'])
    ];
}

    public function index()
    {
        return Blog::with(['user', 'likes', 'comments'])->latest()->paginate(10);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|unique:blogs,title|max:255',
                'description' => 'nullable|string|max:500',
                'body' => 'required|string',
                'category_id' => 'array',
                'category_id.*' => 'exists:categories,id',
            ]);

            $user = $request->user();

            $blog = Blog::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'body' => $validated['body'],
                'creator_id' => $user->id,
            ]);

            if (!empty($validated['category_id'])) {
                $blog->categories()->sync($validated['category_id']);
            }

            return response()->json([
                'message' => 'Blog created successfully.',
                'blog' => $blog->load(['user', 'likes', 'comments','categories']),
            ], 201);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Blog $blog)
    {
        return response()->json([
            'blog' => $blog->load(['user', 'likes', 'comments','categories']),
        ]);
    }

    public function update(Request $request, Blog $blog)
    {
        Gate::authorize('update', $blog);

        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255|unique:blogs,title,' . $blog->id,
                'description' => 'nullable|string|max:500',
                'body' => 'required|string',
                'category_id' => 'array',
                'category_id.*' => 'exists:categories,id',
            ]);

            $blog->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'body' => $validated['body'],
            ]);

            if (!empty($validated['category_id'])) {
                $blog->categories()->sync($validated['category_id']);
            }

            return response()->json([
                'message' => 'Blog updated successfully.',
                'blog' => $blog->load(['user', 'likes', 'comments','categories']),
            ]);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Blog $blog)
    {
        Gate::authorize('modify', $blog);

        $blog->delete();

        return response()->json([
            'message' => 'Blog deleted successfully.',
        ]);
    }
}

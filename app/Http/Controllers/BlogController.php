<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;

class BlogController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show']),
        ];
    }

    public function index()
    {
            $blogs = Blog::with(['user', 'likes', 'comments',"categories"])->latest()->paginate(10);
            return response()->json([
                'message' => 'Blogs retrieved successfully.',
                'blog' => $blogs,
            ], 200);

    }

    public function store(Request $request)
    {
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
                'blog' => $blog->load(['user', 'likes', 'comments', 'categories']),
            ], 201);

    }

    public function show(Blog $blog)
    {
            return response()->json([
                'message' => 'Blog retrieved successfully.',
                'blog' => $blog->load(['user', 'likes', 'comments', 'categories']),
            ], 200);

    }

    public function update(Request $request, Blog $blog)
    {
            Gate::authorize('update', $blog);

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
                'blog' => $blog->load(['user', 'likes', 'comments', 'categories']),
            ], 200);

    }

    public function destroy(Blog $blog)
    {
            Gate::authorize('update', $blog);

            $blog->delete();

            return response()->json([
                'message' => 'Blog deleted successfully.',
            ], 200);
    }

    /**
     * Handle exceptions and format error responses.
     */

}

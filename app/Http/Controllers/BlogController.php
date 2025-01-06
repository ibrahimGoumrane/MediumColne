<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show','uploadImage']),
        ];
    }

    public function index()
    {
        $blogs = Blog::with(['user', 'categories'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Blogs retrieved successfully.',
            'blog' => $blogs,
        ], 200);

    }


    public function search(Request $request)
    {
        // Validate the input
        $validated = $request->validate([
            'query' => 'nullable|string',
            'currentPage' => 'nullable|integer|min:1',
        ]);

        $queryString = $validated['query'] ?? ''; // Default to empty string if no query provided
        $currentPage = $validated['currentPage'] ?? 1; // Default to page 1 if not provided

        // Get blogs that match the search query, split into 10 results per page
        $blogs = Blog::where('name', 'like', '%' . $queryString . '%')->latest()
            ->paginate(10, ['*'], $currentPage);

        // Return the paginated results and the current page
        return response()->json([
            'currentPage' => $blogs->currentPage(),
            'blogs' => $blogs->with(['user', 'likes',"categories"])->items(),
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|unique:blogs,title|max:255',
            'description' => 'nullable|string|max:500',
            'body' => 'required|string',
            'preview' => 'required|string',
            'categories' => 'required|array', // Ensure categories is an array
            'categories.*' => 'required|string|max:255', // Each category must be a string
        ]);

        $user = $request->user();

        $blog = Blog::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'body' => $validated['body'],
            'preview' => $validated['preview'],
            'creator_id' => $user->id,
        ]);

        // Process categories
        $categoryNames = array_map('trim', $validated['categories']); // Trim each category name
        $categoryIds = []; // Store category IDs here

        foreach ($categoryNames as $name) {
            $category = \App\Models\Category::firstOrCreate(['name' => $name]); // Check if category exists, otherwise create
            $categoryIds[] = $category->id; // Collect category ID
        }

        // Sync categories to the blog
        $blog->categories()->sync($categoryIds);

        return response()->json([
            'message' => 'Blog created successfully.',
            'blog' => $blog->load(['user', 'likes', 'categories']), // Load required relationships
        ], 201);
    }    public function uploadImage(Request $request)
    {
        // Validate the uploaded image
        $validated = $request->validate([
            'blog_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:4096', // Ensure it's a valid image
        ]);

        // Handle image upload
        if ($request->hasFile('blog_image')) {
            $blogImage = $request->file('blog_image');

            // Generate a unique name for the file
            $blogImageName = 'uploads/blogs/' . time() . '_' . $blogImage->getClientOriginalName();

            // Save the file to storage
            Storage::disk('public')->put($blogImageName, file_get_contents($blogImage));

        }

        // Return the updated blog and a success message
        return response()->json([
            'message' => 'Image saved successfully.',
            'url' => '/storage/'.$blogImageName
        ], 200);
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
            'preview' => 'required|string',
            'categories' => 'required|array', // Ensure categories field is an array
            'categories.*' => 'required|string|max:255', // Each category must be a string
        ]);

        // Update the blog details
        $blog->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'body' => $validated['body'],
            'preview' => $validated['preview'],
        ]);

        // Process categories
        $categoryNames = array_map('trim', $validated['categories']); // Trim whitespace from each category name
        $categoryIds = []; // Array to hold category IDs

        foreach ($categoryNames as $name) {
            $category = \App\Models\Category::firstOrCreate(['name' => $name]); // Check if a category exists or create a new one
            $categoryIds[] = $category->id; // Collect the category ID
        }

        // Sync the blog's categories
        $blog->categories()->sync($categoryIds); // Ensure only the provided categories are linked

        return response()->json([
            'message' => 'Blog updated successfully.',
            'blog' => $blog->load(['user', 'likes', 'categories']), // Load the necessary relationships
        ], 200);
    }

    public function destroy(Blog $blog)
    {
        Gate::authorize('update', $blog);
        if ($blog->preview) {
            $previewPath = str_replace('/storage/', '', $blog->preview); // Remove the '/storage/' prefix to get the relative path
            Storage::disk('public')->delete($previewPath);
        }
        $blog->delete();

        return response()->json([
                'message' => 'Blog deleted successfully.',
            ], 200);
    }


}

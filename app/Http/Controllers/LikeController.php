<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Recipe;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    // Toggle like (like jika belum, unlike jika sudah)
    public function toggle(Request $request, int $id)
    {
        $recipe = Recipe::find($id);

        if (!$recipe) {
            return response()->json(['message' => 'Resep tidak ditemukan'], 404);
        }

        $like = Like::where('user_id', $request->user()->id)
            ->where('recipe_id', $id)
            ->first();

        if ($like) {
            // Sudah like, maka unlike
            $like->delete();
            return response()->json([
                'message'     => 'Like dibatalkan',
                'likes_count' => $recipe->likes()->count(),
                'is_liked'    => false,  // <-- tambahan
            ]);
        } else {
            // Belum like, maka like
            Like::create([
                'user_id'   => $request->user()->id,
                'recipe_id' => $id,
            ]);
            return response()->json([
                'message'     => 'Resep berhasil di-like',
                'likes_count' => $recipe->likes()->count(),
                'is_liked'    => true,   // <-- tambahan
            ]);
        }
    }

    // Ambil semua resep yang di-like user yang sedang login
    public function favorites(Request $request)
    {
        $recipes = Recipe::with('user')
            ->withCount('likes')
            ->whereHas('likes', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->latest()
            ->paginate(10);

        // Semua resep di sini sudah pasti is_liked = true
        $recipes->getCollection()->transform(function ($recipe) {
            $recipe->is_liked = true;
            return $recipe;
        });

        return response()->json($recipes);
    }
}
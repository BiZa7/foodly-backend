<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    // Lihat semua resep
    public function index(Request $request)
    {

        $userId = $request->user()?->id;

        $sortColumn = match($request->kategori) {
            'kalori' => 'calories',
            'like'   => 'likes_count',
            default   => 'created_at',
        };

        $sortDirection = $request->sort === 'ascending' ? 'asc' : 'desc';

        $recipes = Recipe::with('user')
            ->withCount('likes')
            ->when($request->search, function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('ingredients', 'like', '%' . $request->search . '%');
            })
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

        $recipes->getCollection()->transform(function ($recipe) use ($userId) {
            $recipe->is_liked = $userId
                ? $recipe->likes()->where('user_id', $userId)->exists()
                : false;
            return $recipe;
        });

        return response()->json($recipes);
    }

    // Lihat detail resep
    public function show(Request $request, int $id)
    {

        $userId = $request->user()?->id;
        $recipe = Recipe::with('user')
            ->withCount('likes')
            ->find($id);

        if (!$recipe) {
            return response()->json(['message' => 'Resep tidak ditemukan'], 404);
        }
        $recipe->is_liked = $userId
            ? $recipe->likes()->where('user_id', $userId)->exists()
            : false;

        return response()->json($recipe);
    }

    // Buat resep baru
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'ingredients' => 'required|string',
            'steps'       => 'required|string',
            'image_url'   => 'nullable|url',
            'calories'    => 'nullable|integer',
        ]);

        $recipe = Recipe::create([
            'user_id'     => $request->user()->id,
            'title'       => $request->title,
            'description' => $request->description,
            'ingredients' => $request->ingredients,
            'steps'       => $request->steps,
            'image_url'   => $request->image_url,
            'calories'    => $request->calories,
        ]);

        return response()->json([
            'message' => 'Resep berhasil dibuat',
            'recipe'  => $recipe,
        ], 201);
    }

    // Edit resep
    public function update(Request $request, int $id)
    {
        $recipe = Recipe::find($id);

        if (!$recipe) {
            return response()->json(['message' => 'Resep tidak ditemukan'], 404);
        }

        // Pastikan hanya pemilik resep yang bisa edit
        if ($recipe->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'ingredients' => 'sometimes|string',
            'steps'       => 'sometimes|string',
            'image_url'   => 'nullable|url',
            'calories'    => 'nullable|integer',
        ]);

        $recipe->update($request->only([
            'title',
            'description',
            'ingredients',
            'steps',
            'image_url',
            'calories'
        ]));

        return response()->json([
            'message' => 'Resep berhasil diupdate',
            'recipe'  => $recipe,
        ]);
    }

    // Hapus resep
    public function destroy(Request $request, int $id)
    {
        $recipe = Recipe::find($id);

        if (!$recipe) {
            return response()->json(['message' => 'Resep tidak ditemukan'], 404);
        }

        // Pastikan hanya pemilik resep yang bisa hapus
        if ($recipe->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        $recipe->delete();

        return response()->json(['message' => 'Resep berhasil dihapus']);
    }

    // Resep milik user (My Recipes)
    public function myRecipes(Request $request)
    {
        $recipes = Recipe::with('user')
            ->where('user_id', $request->user()->id)
            ->withCount('likes')
            ->when($request->search, function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('ingredients', 'like', '%' . $request->search . '%');
            })
            ->latest()
            ->paginate(10);

        return response()->json($recipes);
    }

    // Top 5 resep terbaik dalam 7 hari terakhir
    public function topRecipes()
    {
        $recipes = Recipe::with('user')
            ->withCount(['likes' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(7));
            }])
            ->orderBy('likes_count', 'desc')
            ->take(5)
            ->get();

        return response()->json($recipes);
    }
}

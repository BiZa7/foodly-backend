<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\LikeController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Public - lihat resep
Route::get('/recipes',          [RecipeController::class, 'index']);
Route::get('/recipes/top/best', [RecipeController::class, 'topRecipes']); 
Route::get('/recipes/{id}',     [RecipeController::class, 'show']);       

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Resep
    Route::post('/recipes',        [RecipeController::class, 'store']);
    Route::put('/recipes/{id}',    [RecipeController::class, 'update']);
    Route::delete('/recipes/{id}', [RecipeController::class, 'destroy']);

    // Like
    Route::post('/recipes/{id}/like', [LikeController::class, 'toggle']);
    Route::get('/user/favorites', [LikeController::class, 'favorites']);
});
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    PostController
};

Route::prefix('friendflow')->group(function(){

    Route::prefix('auth')->group(function(){
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });


    Route::prefix('posts')->middleware('auth:sanctum')->group(function() {
        Route::get('/', [PostController::class, 'index']);// Récupérer toutes les publications
        Route::post('/', [PostController::class, 'store']); // Créer une nouvelle publication
        Route::put('{id}', [PostController::class, 'update']); // Mettre à jour une publication
        Route::delete('{id}', [PostController::class, 'destroy']); // Supprimer une publication
        Route::post('{id}/like', [PostController::class, 'like']); // Ajouter ou retirer un like
    });
});
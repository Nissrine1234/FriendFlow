<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    UserController,
    PostController,
    FriendController,
    InvitationController

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
        // Route::get('/friends', [PostController::class, 'friendsPosts']); // Uniquement les posts des amis
        Route::put('{id}', [PostController::class, 'update']); // Mettre à jour une publication
        Route::delete('{id}', [PostController::class, 'destroy']); // Supprimer une publication
        Route::post('{id}/like', [PostController::class, 'like']); // Ajouter ou retirer un like
    });

    Route::prefix('users')->middleware('auth:sanctum')->group(function() {
        Route::get('/', [UserController::class, 'index']);
        // Route::get('/current', [UserController::class, 'getCurrentUser']);
        Route::get('/search', [UserController::class, 'search']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::get('/by-username/{username}', [UserController::class, 'showByUsername']);
        Route::post('/{id}', [UserController::class, 'update']);
        Route::post('/{id}/uploadProfile', [UserController::class, 'uploadProfile']);
        Route::prefix('{username}/friends')->group(function () {
            Route::get('/', [FriendController::class, 'getUserFriends']); // Nouvelle route
        });
    });
    
    Route::prefix('friends')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [FriendController::class, 'getAmis']);
        Route::delete('/{id}', [FriendController::class, 'supprimerAmi']);
        Route::get('/est-ami/{id}', [FriendController::class, 'estAmi']);

        
    });

    Route::prefix('invitations')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [InvitationController::class, 'getInvitations']); // Liste des invitations reçues en attente
        Route::get('/history', [InvitationController::class, 'getInvitationsHistory']); // Historique des invitations
        Route::post('/', [InvitationController::class, 'sendInvitation']); // Envoyer une invitation
        Route::delete('/{id}/cancel', [InvitationController::class, 'cancelInvitation']);// Annuler une invitation
        Route::post('/{id}/accept', [InvitationController::class, 'acceptInvitation']); // Accepter une invitation
        Route::post('/{id}/reject', [InvitationController::class, 'rejectInvitation']); // Refuser une invitation


    });
});

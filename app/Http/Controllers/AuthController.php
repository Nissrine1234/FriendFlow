<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur; // Utilisation du modèle Utilisateur
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:utilisateurs,email',
            'nomUtilisateur' => 'required|string|max:255|unique:utilisateurs,nomUtilisateur',
            'date_de_naissance' => 'required|date|before:-18 years', // Validation de date
            'genre' => 'required|in:Homme,Femme',
            'mot_de_passe' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]+$/',
            'photo_profil' =>'nullable|string',
            // statut a une valeur par défaut donc non obligatoire
        ], [
            'nomUtilisateur.unique' => 'Ce nom d\'utilisateur est déjà pris. Veuillez en choisir un autre.',
            'nomUtilisateur.required' => 'Le nom d\'utilisateur est obligatoire.',
            'email.unique' => 'Cet email a déjà un compte.',
            'email.required' => 'L\'email est obligatoire.',
            'mot_de_passe.regex' => 'Le mot de passe doit contenir au moins 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire.',
            'date_de_naissance.before' => 'Vous devez avoir au moins 18 ans pour vous inscrire',
            'date_de_naissance.required' => 'La date de naissance est obligatoire.',
            'genre.in' => 'Le genre doit être soit Homme soit Femme.',
            'genre.required' => 'Le genre est obligatoire.',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $photoProfil = $request->photo_profil ?? 
        ($request->genre === 'Homme' 
            ? '/icons/profile grand male gris.png' 
            : '/icons/profile grand female gris.png');
        
        $user = Utilisateur::create([
            'email' => $request->email,
            'nomUtilisateur' => $request->nomUtilisateur,
            'date_de_naissance' => $request->date_de_naissance,
            'genre' => $request->genre,
            'mot_de_passe' => Hash::make($request->mot_de_passe),
            'photo_profil' => $photoProfil,
            'statut' => 'online' 

            // statut prendra la valeur par défaut 'offline'
        ]);
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie',
            'user' => $user->makeHidden(['mot_de_passe', 'created_at', 'updated_at']),
            'token' => $token
        ], 201);
    }
/**
 * Connexion d'un utilisateur
 */
public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'identifiant' => 'required',
        'mot_de_passe' => 'required'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    $user = Utilisateur::where('email', $request->identifiant)
              ->orWhere('nomUtilisateur', $request->identifiant)
              ->first();

    if (!$user) {
        Log::warning('User not found', ['identifiant' => $request->identifiant]);
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    if (!Hash::check($request->mot_de_passe, $user->mot_de_passe)) {
        Log::warning('Password mismatch', ['user_id' => $user->id]);
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    try {
        // Supprime les anciens tokens
        $user->tokens()->delete();
        
        // Crée un nouveau token
        $token = $user->createToken('auth_token')->plainTextToken;
        
        // Met à jour le statut
        $user->update(['statut' => 'online']);
        
        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user->makeHidden(['mot_de_passe', 'created_at', 'updated_at'])
        ], 200);

    } catch (\Exception $e) {
        Log::error('Token creation failed', [
            'error' => $e->getMessage(),
            'stack' => $e->getTraceAsString(),
            'user_id' => $user->id
        ]);
        
        return response()->json([
            'message' => 'Authentication error',
            'debug' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {

        // Met à jour le statut avant de supprimer le token
        $request->user()->update(['statut' => 'offline']);   
        
        $request->user()->currentAccessToken()->delete();
        
        
        return response()->json([
            'message' => 'Déconnexion réussie'
        ], 200);
    }
}
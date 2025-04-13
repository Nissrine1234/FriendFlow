<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        \Log::info('Données reçues pour inscription', $request->all());

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'nomUtilisateur' => 'required|string|max:255|unique:users,nomUtilisateur',
            'mot_de_passe' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            \Log::warning('Échec de validation', $validator->errors()->toArray());
            return response()->json([
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 400);
        }

        $password = trim($request->mot_de_passe);
        $hashedPassword = Hash::make($password);

        \Log::info('Mot de passe hashé : ' . $hashedPassword);

        $user = User::create([
            'email' => $request->email,
            'nomUtilisateur' => $request->nomUtilisateur,
            'mot_de_passe' => $hashedPassword,
        ]);

        \Log::info('Utilisateur créé', ['user_id' => $user->id]);

        return response()->json(['message' => 'Inscription réussie', 'user' => $user], 201);
    }

    /**
     * Connexion d'un utilisateur (email ou nomUtilisateur)
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'identifiant' => 'required', // peut être email ou nomUtilisateur
                'mot_de_passe' => 'required'
            ]);

            if ($validator->fails()) {
                \Log::warning('Validation échouée', ['errors' => $validator->errors()]);
                return response()->json(['message' => 'Champs invalides', 'errors' => $validator->errors()], 400);
            }

            // Recherche utilisateur par email ou nomUtilisateur
            $user = User::where('email', $request->identifiant)
                        ->orWhere('nomUtilisateur', $request->identifiant)
                        ->first();

            if (!$user || !Hash::check($request->mot_de_passe, $user->mot_de_passe)) {
                \Log::warning('Échec de connexion', ['identifiant' => $request->identifiant]);
                return response()->json(['message' => 'Identifiants incorrects'], 401);
            }

            \Log::info('Connexion réussie', ['user_id' => $user->id]);

            // Générer un token si tu utilises Laravel Sanctum ou Passport
            $token = $user->createToken('friendflow_token')->plainTextToken;

            return response()->json([
                'message' => 'Connexion réussie',
                'user' => $user,
                'token' => $token
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la connexion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur serveur',
                'debug' => env('APP_DEBUG', false) ? $e->getMessage() : null
            ], 500);
        }
    }
        /**
     * Déconnexion de l'utilisateur
     */
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->tokens()->delete();
            return response()->json(['message' => 'Déconnexion réussie'], 200);
        }
    
        return response()->json(['message' => 'Aucun utilisateur authentifié'], 401);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // ✅ Liste des utilisateurs (actuel)
    public function index()
    {
        $utilisateurs = Utilisateur::all();
        return response()->json($utilisateurs);
    }

    // ✅ Récupérer par ID (actuel)
    public function show($id)
    {
        $utilisateur = Utilisateur::findOrFail($id);
        return response()->json($utilisateur);
    }

    // ➕ Nouvelle méthode: Récupérer par nom d'utilisateur
    public function showByUsername($username)
{
    $utilisateur = Utilisateur::where('nomUtilisateur', $username)
        ->select([
            'id',
            'nomUtilisateur',
            'email',
            'photo_profil',
            'date_de_naissance',
            'genre',
            'created_at'
        ])
        ->firstOrFail();

    return response()->json($utilisateur);
}


    // ✅ Mise à jour (actuel)
    public function update(Request $request, $id)
    {
        $utilisateur = Utilisateur::findOrFail($id);

        if (auth()->id() !== $utilisateur->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'nomUtilisateur' => 'sometimes|string|max:255|unique:utilisateurs,nomUtilisateur,'.$id,
            'email' => 'sometimes|email|unique:utilisateurs,email,'.$id,
            'date_de_naissance' => 'sometimes|date',
            'photo_profil' => 'nullable|string',
            'genre' => 'sometimes|in:Homme,Femme',
        ]);

        $utilisateur->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'utilisateur' => $utilisateur->only([
                'id', 'nomUtilisateur', 'email', 'photo_profil', 'date_de_naissance', 'genre'
            ])
        ]);
    }

    // ✅ Recherche (actuel)
    public function search(Request $request)
    {
        $query = $request->input('query');

        $resultats = Utilisateur::where('nomUtilisateur', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%')
                    ->select(['id', 'nomUtilisateur', 'photo_profil'])
                    ->paginate(10);

        return response()->json($resultats);
    }

    // ✅ Upload photo (actuel)
    public function uploadProfile(Request $request, $id)
    {
        $user = Utilisateur::findOrFail($id);

        $request->validate([
            'photo_profil' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('photo_profil')) {
            // Supprimer l'ancienne photo si elle existe
            if ($user->photo_profil) {
                $oldPath = str_replace('/storage/', '', $user->photo_profil);
                Storage::disk('public')->delete($oldPath);
            }

            $file = $request->file('photo_profil');
            $filename = 'profile_'.$user->id.'_'.time().'.'.$file->getClientOriginalExtension();
            $cleanFilename = preg_replace('/[^A-Za-z0-9\-._]/', '', $filename);
            $path = $file->storeAs('profiles', $cleanFilename, 'public');
            
            $user->photo_profil = '/storage/'.$path;
            $user->save();
        }

        return response()->json([
            'photo_profil' => $user->photo_profil
        ]);
    }
}
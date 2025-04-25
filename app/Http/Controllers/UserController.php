<?php

namespace App\Http\Controllers;
use App\Models\Utilisateur;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $utilisateurs = Utilisateur::all();
        return response()->json($utilisateurs);
    }
    public function show($id)
    {
        $utilisateur = Utilisateur::findOrFail($id);
        return response()->json($utilisateur);
    }
    // public function getCurrentUser()
    // {
    //     $user = auth()->user();
    //     return response()->json($user);
    // }
        public function update(Request $request, $id)
    {
        $utilisateur = Utilisateur::findOrFail($id);

        // Optionnel : vérifier que c’est bien l’utilisateur connecté
        if (auth()->id() !== $utilisateur->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'nomUtilisateur' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:utilisateurs,email,' . $id,
            'date_de_naissance' => 'sometimes|date',
            'photo_profil' => 'nullable|url',
            'genre' => 'required|in:Homme,Femme',
        ]);

        $utilisateur->update($validated);

        return response()->json(['message' => 'Profil mis à jour avec succès.', 'utilisateur' => $utilisateur]);
    }
    public function search(Request $request)
    {
        $query = $request->input('query');

        $resultats = Utilisateur::where('nomUtilisateur', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->get();

        return response()->json($resultats);
    }

    public function uploadProfile(Request $request, $id)
    {
        $user = Utilisateur::findOrFail($id);

        $request->validate([
            'photo_profil' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('photo_profil')) {
            $file = $request->file('photo_profil');
            
            // Génère un nom de fichier propre
            $filename = 'profile_'.$user->id.'_'.time().'.'.$file->getClientOriginalExtension();
            
            // Supprime les caractères problématiques
            $cleanFilename = preg_replace('/[^A-Za-z0-9\-._]/', '', $filename);
            
            // Stocke le fichier
            $path = $file->storeAs('profiles', $cleanFilename, 'public');
            
            // Met à jour le chemin dans la base de données
            $user->photo_profil = '/storage/'.$path;
            $user->save();
        }

        return response()->json($user);
    }



}

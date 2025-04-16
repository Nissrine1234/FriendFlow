<?php

namespace App\Http\Controllers;

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



}

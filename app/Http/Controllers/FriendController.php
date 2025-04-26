<?php

namespace App\Http\Controllers;

use App\Models\Ami;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendController extends Controller
{
    // ✅ Obtenir la liste des amis (actuel)
    public function getAmis()
    {
        $utilisateur_id = Auth::id();

        $amis_1 = Ami::where('utilisateur_1_id', $utilisateur_id)
                    ->with('utilisateur2')
                    ->get()
                    ->pluck('utilisateur2');

        $amis_2 = Ami::where('utilisateur_2_id', $utilisateur_id)
                    ->with('utilisateur1')
                    ->get()
                    ->pluck('utilisateur1');

        $amis = $amis_1->merge($amis_2);

        return response()->json($amis->map(function ($ami) {
            return [
                'id' => $ami->id,
                'nomUtilisateur' => $ami->nomUtilisateur,
                'photo_profil' => $ami->photo_profil,
            ];
        }));
    }

    // ✅ Supprimer un ami (actuel)
    public function supprimerAmi($ami_id)
    {
        $utilisateur_id = Auth::id();

        $ami = Ami::where(function($query) use ($utilisateur_id, $ami_id) {
                    $query->where('utilisateur_1_id', $utilisateur_id)
                          ->where('utilisateur_2_id', $ami_id);
                })->orWhere(function($query) use ($utilisateur_id, $ami_id) {
                    $query->where('utilisateur_1_id', $ami_id)
                          ->where('utilisateur_2_id', $utilisateur_id);
                })->first();

        if (!$ami) {
            return response()->json(['message' => 'Relation d\'amitié non trouvée'], 404);
        }

        $ami->delete();

        return response()->json(['message' => 'Ami supprimé avec succès']);
    }

    // ✅ Vérifier si un utilisateur est déjà un ami (actuel)
    public function estAmi($id)
    {
        $utilisateur_id = Auth::id();

        $existe = Ami::where(function($q) use ($utilisateur_id, $id) {
            $q->where('utilisateur_1_id', $utilisateur_id)
              ->where('utilisateur_2_id', $id);
        })->orWhere(function($q) use ($utilisateur_id, $id) {
            $q->where('utilisateur_1_id', $id)
              ->where('utilisateur_2_id', $utilisateur_id);
        })->exists();

        return response()->json(['amis' => $existe]);
    }

    // ➕ Nouvelle méthode: Récupérer les amis d'un utilisateur spécifique
    public function getUserFriends($username)
    {
        $user = Utilisateur::where('nomUtilisateur', $username)->firstOrFail();
        $utilisateur_id = $user->id;

        $amis_1 = Ami::where('utilisateur_1_id', $utilisateur_id)
                    ->with('utilisateur2')
                    ->get()
                    ->pluck('utilisateur2');

        $amis_2 = Ami::where('utilisateur_2_id', $utilisateur_id)
                    ->with('utilisateur1')
                    ->get()
                    ->pluck('utilisateur1');

        $amis = $amis_1->merge($amis_2);

        return response()->json($amis->map(function ($ami) {
            return [
                'id' => $ami->id,
                'nomUtilisateur' => $ami->nomUtilisateur,
                'photo_profil' => $ami->photo_profil,
            ];
        }));
    }
}
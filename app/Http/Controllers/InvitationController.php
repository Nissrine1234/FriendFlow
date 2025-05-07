<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use App\Models\Ami;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    /**
     * Récupère les invitations reçues (en attente)
     */
    public function getInvitations()
    {
        $user = Auth::user();
        
        $invitations = Invitation::with(['expediteur' => function($query) {
                $query->select('id', 'nomUtilisateur', 'photo_profil');
            }])
            ->where('destinataire_id', $user->id)
            ->where('statut', 'en_attente')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($invitations);
    }

    /**
     * Récupère l'historique des invitations (acceptées/refusées)
     */
    public function getInvitationsHistory()
    {
        $user = Auth::user();
        
        $invitations = Invitation::with(['expediteur', 'destinataire'])
            ->where(function($query) use ($user) {
                $query->where('expediteur_id', $user->id)
                      ->orWhere('destinataire_id', $user->id);
            })
            ->whereIn('statut', ['acceptee', 'refusee'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($invitations);
    }

    /**
     * Accepter une invitation
     */
    public function acceptInvitation($id)
    {
        $user = Auth::user();
        $invitation = Invitation::findOrFail($id);

        // Vérification que l'utilisateur est bien le destinataire
        if ($invitation->destinataire_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Vérification que l'invitation est bien en attente
        if ($invitation->statut !== 'en_attente') {
            return response()->json(['message' => 'Cette invitation a déjà été traitée'], 400);
        }

        // Vérifier si les utilisateurs sont déjà amis
        $dejaAmis = Ami::where(function($query) use ($user, $invitation) {
            $query->where('utilisateur_1_id', $invitation->expediteur_id)
                ->where('utilisateur_2_id', $invitation->destinataire_id);
        })->orWhere(function($query) use ($user, $invitation) {
            $query->where('utilisateur_1_id', $invitation->destinataire_id)
                ->where('utilisateur_2_id', $invitation->expediteur_id);
        })->exists();

        // Mise à jour de l'invitation
        $invitation->update(['statut' => 'acceptee']);

        // Création de la relation d'amitié seulement si elle n'existe pas déjà
        if (!$dejaAmis) {
            Ami::create([
                'utilisateur_1_id' => $invitation->expediteur_id,
                'utilisateur_2_id' => $invitation->destinataire_id
            ]);
        }

        return response()->json([
            'message' => 'Invitation acceptée avec succès',
            'invitation' => $invitation->load(['expediteur', 'destinataire'])
        ]);
    }

    /**
     * Refuser une invitation
     */
    public function rejectInvitation($id)
    {
        $user = Auth::user();
        $invitation = Invitation::findOrFail($id);

        // Vérification que l'utilisateur est bien le destinataire
        if ($invitation->destinataire_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Vérification que l'invitation est bien en attente
        if ($invitation->statut !== 'en_attente') {
            return response()->json(['message' => 'Cette invitation a déjà été traitée'], 400);
        }

        // Mise à jour de l'invitation
        $invitation->update(['statut' => 'refusee']);

        return response()->json([
            'message' => 'Invitation refusée avec succès',
            'invitation' => $invitation->load(['expediteur', 'destinataire'])
        ]);
    }

    /**
     * Envoyer une invitation
     */
    public function sendInvitation(Request $request)
    {
        $user = Auth::user();
        $destinataireId = $request->destinataire_id;
    
        // Vérification que l'utilisateur ne s'envoie pas une invitation à lui-même
        if ($user->id == $destinataireId) {
            return response()->json(['message' => 'Vous ne pouvez pas vous envoyer une invitation à vous-même'], 400);
        }
    
        // Vérification que l'utilisateur existe
        $destinataire = Utilisateur::find($destinataireId);
        if (!$destinataire) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }
    
        // Vérifier si les utilisateurs sont déjà amis
        $dejaAmis = Ami::where(function($query) use ($user, $destinataireId) {
            $query->where('utilisateur_1_id', $user->id)
                  ->where('utilisateur_2_id', $destinataireId);
        })->orWhere(function($query) use ($user, $destinataireId) {
            $query->where('utilisateur_1_id', $destinataireId)
                  ->where('utilisateur_2_id', $user->id);
        })->exists();
    
        if ($dejaAmis) {
            return response()->json(['message' => 'Vous êtes déjà amis avec cet utilisateur'], 400);
        }
    
        // Vérification qu'une invitation n'existe pas déjà
        $existingInvitation = Invitation::where(function($query) use ($user, $destinataireId) {
            $query->where('expediteur_id', $user->id)
                  ->where('destinataire_id', $destinataireId);
        })->orWhere(function($query) use ($user, $destinataireId) {
            $query->where('expediteur_id', $destinataireId)
                  ->where('destinataire_id', $user->id);
        })->where('statut', 'en_attente')->first();
    
        if ($existingInvitation) {
            return response()->json(['message' => 'Une invitation existe déjà entre ces utilisateurs'], 400);
        }
    
        // Création de l'invitation
        $invitation = Invitation::create([
            'expediteur_id' => $user->id,
            'destinataire_id' => $destinataireId,
            'statut' => 'en_attente'
        ]);
    
        return response()->json([
            'message' => 'Invitation envoyée avec succès',
            'invitation' => $invitation->load('destinataire')
        ], 201);
    }
    /**
 * Annuler une invitation envoyée
 */
public function cancelInvitation($id)
{
    $user = Auth::user();
    $invitation = Invitation::findOrFail($id);

    // Vérifier que l'utilisateur est bien l'expéditeur
    if ($invitation->expediteur_id !== $user->id) {
        return response()->json(['message' => 'Vous ne pouvez annuler que vos propres invitations'], 403);
    }

    // Vérifier que l'invitation est toujours en attente
    if ($invitation->statut !== 'en_attente') {
        return response()->json(['message' => 'Cette invitation a déjà été traitée'], 400);
    }

    // Supprimer l'invitation
    $invitation->delete();

    return response()->json([
        'message' => 'Invitation annulée avec succès'
    ]);
}
}
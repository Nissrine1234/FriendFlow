<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\Like;
use App\Models\Ami;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index()
    {
        // 1. On récupère l'utilisateur connecté
        $user = Auth::user();
        
        // 2. On récupère les IDs de tous ses amis
        $amisIds = Ami::where('utilisateur_1_id', $user->id)
            ->orWhere('utilisateur_2_id', $user->id)
            ->get()
            ->map(function ($ami) use ($user) {
                // Pour chaque relation d'amitié, on identifie qui est l'ami
                return $ami->utilisateur_1_id == $user->id 
                    ? $ami->utilisateur_2_id  // Si l'user est utilisateur_1, l'ami est utilisateur_2
                    : $ami->utilisateur_1_id; // Sinon l'ami est utilisateur_1
            });
        
        // 3. On ajoute l'ID de l'utilisateur courant pour qu'il voie aussi ses propres posts
        $amisIds->push($user->id);
        
        // 4. On récupère les publications avec les mêmes relations qu'avant
        $publications = Publication::with(['utilisateur', 'likes.utilisateur'])
            ->whereIn('utilisateur_id', $amisIds) // Seule ligne modifiée par rapport à ta version originale
            ->latest()
            ->get();
            
        // 5. On retourne le résultat comme avant
        return response()->json($publications);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'contenu' => 'nullable|string',
            'media_url' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048', // Changé de media_url_url à media_url
        ]);
    
        // Vérification des champs fournis
        $hasContenu = !empty($validated['contenu']);
        $hasMedia = $request->hasFile('media_url'); // Vérifie si un fichier a été uploadé
    
        // Validation XOR (soit l'un, soit l'autre)
        if (!$hasContenu && !$hasMedia) {
            return response()->json([
                'message' => 'Vous devez fournir soit du contenu, soit un média'
            ], 422);
        }
    
        if ($hasContenu && $hasMedia) {
            return response()->json([
                'message' => 'Vous ne pouvez pas publier à la fois du contenu et un média'
            ], 422);
        }
    
        $publicationData = [
            'utilisateur_id' => Auth::id(),
            'likes' => 0,
            'description' => $validated['description'] ?? null,
            'contenu' => $hasContenu ? $validated['contenu'] : null,
        ];
    
        // Gestion de l'upload de fichier
        if ($hasMedia) {
            $path = $request->file('media_url')->store('publications', 'public');
            $publicationData['media_url'] = Storage::url($path); // Stocke le chemin d'accès
        } else {
            $publicationData['media_url'] = null;
        }
    
        $publication = Publication::create($publicationData);
    
        return response()->json($publication, 201);
    }
    

    public function update(Request $request, $id)
    {
        $publication = Publication::findOrFail($id);

        if ($publication->utilisateur_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'description' => 'required|string',
            'contenu' => 'required|string',
            'media_url' => 'required|url',
        ]);

        $publication->update([
            'description' => $request->description,
            'contenu' => $request->contenu,
            'media_url' => $request->media_url,
        ]);

        return response()->json($publication);
    }


    public function destroy($id)
    {
        $publication = Publication::findOrFail($id);

        if ($publication->utilisateur_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $publication->delete();

        return response()->json(['message' => 'Publication supprimée']);
    }

    
    public function like($id)
    {
        $user = Auth::user();
        $userId = $user->id;
        $publication = Publication::findOrFail($id);
    
        $like = Like::where('utilisateur_id', $userId)
                    ->where('publication_id', $id)
                    ->first();
    
        if ($like) {
            $like->delete();
            $message = 'Like retiré';
            $liked = false;
        } else {
            Like::create([
                'utilisateur_id' => $userId,
                'publication_id' => $id,
            ]);
            $message = 'Like ajouté';
            $liked = true;
        }
    
        // Mettre à jour le compteur dans la table publications
        $totalLikes = Like::where('publication_id', $id)->count();
        $publication->likes = $totalLikes;
        $publication->save();
        
        // Récupérer la publication mise à jour avec toutes les relations
        $updatedPublication = Publication::with(['utilisateur', 'likes'])->findOrFail($id);
        
        // Ajouter explicitement is_liked à la publication
        $updatedPublication->is_liked = $liked;
        
        return response()->json([
            'message' => $message,
            'liked' => $liked,
            'publication' => $updatedPublication
        ]);
    }
        
}

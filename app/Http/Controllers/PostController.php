<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index()
    {
        $publications = Publication::with('utilisateur')->latest()->paginate(10); // tu peux ajuster la pagination
        return response()->json($publications);
    }


    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            'contenu' => 'required|string',
            'media_url' => 'required|url',
        ]);

        $publication = Publication::create([
            'utilisateur_id' => Auth::id(),
            'likes' => 0,
            'description' => $request->description,
            'contenu' => $request->contenu,
            'media_url' => $request->media_url,
        ]);

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
        
        // Vérifie si la publication existe
        $publication = Publication::find($id);
        if (!$publication) {
            return response()->json(['message' => 'Publication non trouvée'], 404);
        }
    
        // Vérifie si l'utilisateur a déjà liké cette publication
        $like = Like::where('utilisateur_id', $user->id)
                    ->where('publication_id', $id)
                    ->first();
    
        if ($like) {
            // Si oui, on enlève le like (unlike)
            $like->delete();
            return response()->json([
                'message' => 'Like retiré.',
                'liked' => false,
                'likes_count' => $publication->likes()->count()
            ]);
        } else {
            // Sinon, on ajoute un nouveau like
            Like::create([
                'utilisateur_id' => $user->id,
                'publication_id' => $id,
            ]);
            return response()->json([
                'message' => 'Like ajouté.',
                'liked' => true,
                'likes_count' => $publication->likes()->count()
            ]);
        }
    }
}

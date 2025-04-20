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
        $publications = Publication::with(['utilisateur', 'likes'])->get();
    
        return response()->json($publications);
    }
    


    public function store(Request $request)
    {
        $request->validate([
            'description' => 'nullable|string',
            'contenu' => 'required_without:media_url|string|nullable',
            'media_url' => 'required_without:contenu|url|nullable',
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
            $publication = Publication::findOrFail($id);
        
            $like = Like::where('utilisateur_id', $user->id)
                        ->where('publication_id', $id)
                        ->first();
        
            if ($like) {
                $like->delete();
        
                // Met à jour le compteur manuellement
                $publication->likes = max(0, $publication->likes - 1);
                $publication->save();
        
                $message = 'Like retiré';
                $liked = false;
            } else {
                Like::create([
                    'utilisateur_id' => $user->id,
                    'publication_id' => $id,
                ]);
        
                $publication->likes += 1;
                $publication->save();
        
                $message = 'Like ajouté';
                $liked = true;
            }
        
            return response()->json([
                'message' => $message,
                'liked' => $liked,
                'total_likes' => $publication->likes
            ]);
        }
        
}

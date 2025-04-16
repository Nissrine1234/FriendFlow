<?php

namespace App\Http\Controllers;

use App\Models\Publication;
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
        $publication = Publication::findOrFail($id);
        $userId = Auth::id();

        // Vérifier si l'utilisateur a déjà liké la publication
        $sessionKey = "liked_{$userId}_{$id}";
        if (session($sessionKey)) {
            // Unlike
            $publication->likes = max(0, $publication->likes - 1);
            session()->forget($sessionKey);
        } else {
            // Like
            $publication->likes += 1;
            session([$sessionKey => true]);
        }

        $publication->save();

        return response()->json(['likes' => $publication->likes]);
    }
//     public function like($id)
// {
//     $publication = Publication::findOrFail($id);

//     $userId = Auth::id();

//     // Si l'utilisateur a déjà liké → unlike
//     if ($publication->liked_by && in_array($userId, $publication->liked_by)) {
//         $publication->liked_by = array_filter($publication->liked_by, function ($id) use ($userId) {
//             return $id != $userId;
//         });
//         $publication->likes--;
//     } else {
//         // Sinon, il like
//         $publication->liked_by = array_merge($publication->liked_by ?? [], [$userId]);
//         $publication->likes++;
//     }

//     $publication->save();

//     return response()->json([
//         'likes' => $publication->likes,
//         'liked_by' => $publication->liked_by
//     ]);
// }





}

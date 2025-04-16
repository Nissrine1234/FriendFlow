<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    use HasFactory;

    protected $table = 'publications';
    protected $appends = ['likes_count'];

    protected $fillable = [
        'utilisateur_id',
        'likes',
        'description',
        'contenu',
        'media_url',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class);
    }
    public function likes()
    {
        return $this->hasMany(Like::class);
    }
    // Pour obtenir le nombre de likes
    public function getLikesCountAttribute()
    {
        return $this->likes()->count();
    }

    public function utilisateurs_ayant_aime()
    {
        return $this->belongsToMany(Utilisateur::class, 'likes', 'publication_id', 'utilisateur_id');
    }

}

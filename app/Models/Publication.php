<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Publication extends Model
{
    use HasFactory;

    protected $table = 'publications';
    protected $appends = ['temps_depuis_creation'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    


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
    // Dans Publication.php
    public function likes()
    {
        return $this->hasMany(Like::class, 'publication_id');
    }

    public function utilisateurs_ayant_aime()
    {
        return $this->belongsToMany(Utilisateur::class, 'likes', 'publication_id', 'utilisateur_id');
    }

    public function getTempsDepuisCreationAttribute()
    {
        $created = Carbon::parse($this->created_at);
        $now = Carbon::now();
    
        if ($created->diffInSeconds($now) < 1) {
            return "à l'instant";
        }
    
        $seconds = $created->diffInSeconds($now);
    
        if ($seconds < 60) {
            return "il y a " . (int)$seconds . " seconde" . ($seconds > 1 ? "s" : "");
        }
    
        $minutes = $created->diffInMinutes($now);
        if ($minutes < 60) {
            return "il y a " . (int)$minutes . " minute" . ($minutes > 1 ? "s" : "");
        }
    
        $hours = $created->diffInHours($now);
        if ($hours < 24) {
            return "il y a " . (int)$hours . " heure" . ($hours > 1 ? "s" : "");
        }
    
        $days = $created->diffInDays($now);
        if ($days < 7) {
            return "il y a " . (int)$days . " jour" . ($days > 1 ? "s" : "");
        }
    
        $weeks = floor($days / 7);
        if ($weeks < 5) {
            return "il y a " . (int)$weeks . " semaine" . ($weeks > 1 ? "s" : "");
        }
    
        $months = $created->diffInMonths($now);
        if ($months < 12) {
            return "il y a " . (int)$months . " mois";
        }
    
        $years = $created->diffInYears($now);
        return "il y a " . (int)$years . " an" . ($years > 1 ? "s" : "");
    }
    


}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Utilisateur extends Authenticatable
{
    use HasFactory, Notifiable,HasApiTokens;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'nomUtilisateur',
        'email',
        'date_de_naissance',
        'mot_de_passe',
        'genre',
        'photo_profil',
        'statut',
    ];

    protected $hidden = [
        'mot_de_passe',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'date_de_naissance' => 'date',
            'mot_de_passe' => 'hashed',
        ];
    }

    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function publications_aimees()
    {
        return $this->belongsToMany(Publication::class, 'likes', 'utilisateur_id', 'publication_id');
    }

    public function amisDemandesEnvoyees()
    {
        return $this->hasMany(Ami::class, 'utilisateur_1_id');
    }

    public function amisDemandesRecues()
    {
        return $this->hasMany(Ami::class, 'utilisateur_2_id');
    }

    // Pour obtenir tous les amis (acceptés)
    public function amis()
    {
        return $this->belongsToMany(User::class, 'amis', 'utilisateur_1_id', 'utilisateur_2_id')
                    ->wherePivot('statut', 'accepte')
                    ->withTimestamps()
                    ->union(
                        $this->belongsToMany(User::class, 'amis', 'utilisateur_2_id', 'utilisateur_1_id')
                            ->wherePivot('statut', 'accepte')
                            ->withTimestamps()
                    );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Utilisateur extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'nomUtilisateur',
        'email',
        'date_de_naissance',
        'mot_de_passe',
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
}

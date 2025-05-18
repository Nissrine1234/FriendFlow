<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ami extends Model
{
    use HasFactory;

    protected $table = 'amis';
    
    protected $fillable = [
        'utilisateur_1_id',
        'utilisateur_2_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function utilisateur1()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_1_id');
    }

    public function utilisateur2()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_2_id');
    }

    public static function areFriends($user1Id, $user2Id)
    {
        return self::where(function($query) use ($user1Id, $user2Id) {
            $query->where('utilisateur_1_id', $user1Id)
                  ->where('utilisateur_2_id', $user2Id);
        })->orWhere(function($query) use ($user1Id, $user2Id) {
            $query->where('utilisateur_1_id', $user2Id)
                  ->where('utilisateur_2_id', $user1Id);
        })->where('statut', 'accepte')->exists();
    }
}

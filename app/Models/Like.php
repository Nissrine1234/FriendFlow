<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class);
    }

    public function publication()
    {
        return $this->belongsTo(Publication::class);
    }

}

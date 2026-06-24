<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Depositaire extends Model
{
    protected $fillable = [
        'nom',
        'prenom',
        'date_naissance',
        'lieu_naissance',
        'numero_registre_national',
        'domicile',
        'carte_identite',
        'numero_membre',
        'nom_cabinet',
        'gsm',
        'fonction',
        'type',
        'demande_id',
    ];

    public function demande()
    {
        return $this->belongsTo(Demande::class, 'demande_id');
    }
}

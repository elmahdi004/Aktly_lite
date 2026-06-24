<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Demande extends Model
{
    /** @use HasFactory<\Database\Factories\DemandeFactory> */
    use HasFactory;
    use HasUuids;
    protected $fillable = ['depositaire', 'nb_total_actions', 'user_id', 'bce_data', 'enterprise_number', 'langue_entreprise', 'cession_parts_service','dirigeants_service', 'address_service','status', 'montant', 'veriff_session','date_assemblee','date_changement','in_enterprise','GSM','veriff_url','pub_text','fait_a','nb_total_actions','verification_id', 'progress', 'extrait_pub_text', 'pdf_fields'];
    protected $guarded = [];

    protected $casts = [
        'pdf_fields' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($demande) {
            if (empty($demande->id)) {
                $demande->id = Str::uuid(); 
            }
        });
    }
    public $incrementing = false;
    protected $keyType = 'string';
    public function dirigeants()
    {
        return $this->hasMany(DirigeantsService::class,'demande_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class,'demande_id');
    }
    public function shareholders(){
        return $this->hasMany(ShareHolder::class,'demande_id');
    }
    public function address(){
        return $this->hasOne(AddressService::class,'demande_id');
    }
    public function depositaire(){
        return $this->hasOne(Depositaire::class,'demande_id');
    }
    public function verification()
    {
        return $this->belongsTo(Verification::class,'verification_id');
    }
}

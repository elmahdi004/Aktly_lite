<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddressService extends Model
{
    /** @use HasFactory<\Database\Factories\AddressServiceFactory> */
    use HasFactory;
    protected $table = 'address_service';
    protected $fillable = ['an_address', 'nv_address', 'demande_id', 'rue', 'numero', 'box', 'zip_code', 'localite', 'pays'];
    public function demande(){
        return $this->belongsTo(Demande::class,'demande_id');
    }
}

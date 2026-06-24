<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    protected $guarded = [];
    public function demandes(){
        return $this->hasMany(Demande::class,'verification_id');
    }
        public function dirigeantsServices(){
        return $this->hasMany(DirigeantsService::class,'verification_id'); 
        }
        public function shareholders(){
        return $this->hasMany(ShareHolder::class,'verification_id');
        }
}

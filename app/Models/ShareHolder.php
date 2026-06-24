<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShareHolder extends Model
{
    /** @use HasFactory<\Database\Factories\ShareHolderFactory> */
    use HasFactory;
    protected $guarded = [];
    public function demande(){
        return $this->belongsTo(Demande::class,'demande_id');
    }
    public function verification()
    {
        return $this->belongsTo(Verification::class,'verification_id');
    }
}

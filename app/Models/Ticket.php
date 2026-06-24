<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;
    protected $guarded = [];
    protected $fillable = [
        'demande_id',
        'type',
        'description',
        'nom',
        'email',
        'file_path',
    ];
    public function demande()
    {
        return $this->belongsTo(Demande::class,'demande_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $demande_id
 * @property string|null $surname
 * @property string|null $givenName
 * @property string|null $function
 * @property string|null $rc
 * @property string|null $carte_identite
 * @property int|bool|null $is_modified
 */
class DirigeantsService extends Model
{
    /** @use HasFactory<\Database\Factories\DirigeantsServiceFactory> */
    use HasFactory;
    protected $guarded = [];
    public function demande()
    {
        return $this->belongsTo(Demande::class,'demande_id');
    }
    public function verification()
    {
        return $this->belongsTo(Verification::class,'verification_id');
    }
}

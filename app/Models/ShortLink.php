<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortLink extends Model
{
    protected $table = 'short_links';
    protected $fillable = ['code', 'url'];
}

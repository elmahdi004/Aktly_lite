<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShortLink;

class ShortLinkController extends Controller
{
    
    // Redirection
    public function redirect($code)
    {
        $link = ShortLink::where('code', $code)->firstOrFail();
        return redirect($link->url);
    }
}

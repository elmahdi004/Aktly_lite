<?php

namespace App\Services;
use App\Models\ShortLink;
use Illuminate\Support\Str;

class ShortLinkService {
    public function store($link)
    {
        if (empty($link) || !filter_var($link, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid URL'], 400);
        }

        // Générer un code unique
        $code = Str::random(3) . '-' . Str::random(3) . '-' . Str::random(3);

        // Sauvegarder
        $short = ShortLink::create([
            'code' => $code,
            'url' => $link
        ]);

        // Retourner le lien court
        return response()->json([
            'short_url' => url('/s/' . $short->code)
        ]);
    }
}
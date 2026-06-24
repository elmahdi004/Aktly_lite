<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NominatimProxyController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->query('q');
        if (!$query) {
            return response()->json(['error' => 'Missing query'], 400);
        }

        $response = Http::timeout(10)
            ->withHeaders([
                'User-Agent' => 'Legakte/1.0 (contact@legakte.be)',
                'Referer' => config('app.url'),
            ])
            ->get('https://nominatim.openstreetmap.org/search', [
            'format' => 'json',
            'addressdetails' => 1,
            'countrycodes' => 'be',
            'limit' => 5,
            'q' => $query,
        ]);

        return response()->json($response->json(), $response->status());
    }

    public function reverse(Request $request)
    {
        $lat = $request->query('lat');
        $lon = $request->query('lon');
        if (!$lat || !$lon) {
            return response()->json(['error' => 'Missing coordinates'], 400);
        }

        $response = Http::timeout(10)
            ->withHeaders([
                'User-Agent' => 'Legakte/1.0 (contact@legakte.be)',
                'Referer' => config('app.url'),
            ])
            ->get('https://nominatim.openstreetmap.org/reverse', [
            'format' => 'json',
            'addressdetails' => 1,
            'lat' => $lat,
            'lon' => $lon,
        ]);

        return response()->json($response->json(), $response->status());
    }
}

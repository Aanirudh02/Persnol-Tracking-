<?php

namespace App\Http\Controllers;

use App\Services\Maps\MapProviderInterface;
use Illuminate\Http\Request;

class GeoController extends Controller
{
    public function search(Request $request, MapProviderInterface $maps)
    {
        $q = trim((string) $request->get('q', ''));
        if (strlen($q) < 3) {
            return response()->json([]);
        }

        return response()->json($maps->searchPlaces($q));
    }

    public function route(Request $request, MapProviderInterface $maps)
    {
        $waypoints = $request->input('waypoints', []);
        if (is_string($waypoints)) {
            $waypoints = json_decode($waypoints, true) ?? [];
        }

        $distance = $maps->routeDistanceKm($waypoints);

        return response()->json([
            'distance_km' => $distance,
        ]);
    }
}

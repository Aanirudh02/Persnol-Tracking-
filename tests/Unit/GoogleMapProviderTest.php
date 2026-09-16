<?php

namespace Tests\Unit;

use App\Services\Maps\GoogleMapProvider;
use App\Services\Maps\OsmMapProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleMapProviderTest extends TestCase
{
    public function test_google_mode_falls_back_to_osm_without_an_api_key(): void
    {
        Config::set('services.maps.google_key', null);
        Cache::flush();
        Http::fake([
            'https://photon.komoot.io/*' => Http::response([
                'features' => [[
                    'properties' => ['name' => 'DMart Coimbatore'],
                    'geometry' => ['coordinates' => [77.04, 11.03]],
                ]],
            ]),
            'https://nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $results = (new GoogleMapProvider(new OsmMapProvider))->searchPlaces('DMart Coimbatore');

        $this->assertSame('DMart Coimbatore', $results[0]['label']);
    }

    public function test_google_route_distance_calculates_via_directions_api(): void
    {
        Config::set('services.maps.google_key', 'fake-key');
        Cache::flush();

        Http::fake([
            'https://maps.googleapis.com/maps/api/directions/json*' => Http::response([
                'status' => 'OK',
                'routes' => [
                    [
                        'legs' => [
                            ['distance' => ['value' => 12500]],
                            ['distance' => ['value' => 3500]],
                        ],
                    ],
                ],
            ]),
        ]);

        $waypoints = [
            ['lat' => 11.0168, 'lng' => 76.9558],
            ['lat' => 11.0250, 'lng' => 76.9600],
            ['lat' => 11.0350, 'lng' => 76.9700],
        ];

        $km = (new GoogleMapProvider(new OsmMapProvider))->routeDistanceKm($waypoints);

        $this->assertSame(16.0, $km);
    }
}

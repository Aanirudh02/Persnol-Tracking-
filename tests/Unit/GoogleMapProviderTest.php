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
}

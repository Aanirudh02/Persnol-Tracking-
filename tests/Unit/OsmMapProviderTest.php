<?php

namespace Tests\Unit;

use App\Services\Maps\OsmMapProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OsmMapProviderTest extends TestCase
{
    public function test_poi_search_normalizes_locality_and_prioritizes_matching_dmart_result(): void
    {
        Cache::forget('geo:search:v4:'.md5(mb_strtolower('DMart Chinniyampalayam, Tamil Nadu')));
        Http::fake([
            'https://photon.komoot.io/*' => Http::response([
                'features' => [[
                    'properties' => [
                        'name' => 'DMart Chinniyampalayam',
                        'city' => 'Coimbatore',
                        'state' => 'Tamil Nadu',
                    ],
                    'geometry' => ['coordinates' => [77.04, 11.03]],
                ]],
            ]),
            'https://nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $results = app(OsmMapProvider::class)->searchPlaces('Chinniampalayam DMart, Tamilnadu');

        $this->assertSame('DMart Chinniyampalayam, Coimbatore, Tamil Nadu', $results[0]['label']);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'photon.komoot.io'));
    }

    public function test_provider_failure_returns_empty_results(): void
    {
        Http::fake([
            'https://photon.komoot.io/*' => Http::response([], 503),
            'https://nominatim.openstreetmap.org/*' => Http::response([], 503),
        ]);

        $this->assertSame([], app(OsmMapProvider::class)->searchPlaces('unknown place Coimbatore'));
    }
}

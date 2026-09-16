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

    public function test_local_landmarks_resolve_ngp_kalapatti_mahil_nehru(): void
    {
        Cache::flush();
        Http::fake([
            'https://photon.komoot.io/*' => Http::response([]),
            'https://nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $provider = app(OsmMapProvider::class);

        $ngp = $provider->searchPlaces('ngp college');
        $this->assertNotEmpty($ngp);
        $this->assertStringContainsString('N.G.P.', $ngp[0]['label']);

        $kalapati = $provider->searchPlaces('kalapati');
        $this->assertNotEmpty($kalapati);
        $this->assertStringContainsString('Kalapatti', $kalapati[0]['label']);

        $mahil = $provider->searchPlaces('mahil pharmacy');
        $this->assertNotEmpty($mahil);
        $this->assertStringContainsString('Mahil', $mahil[0]['label']);

        $nehru = $provider->searchPlaces('nehru nagar');
        $this->assertNotEmpty($nehru);
        $this->assertStringContainsString('Nehru Nagar', $nehru[0]['label']);
    }
}

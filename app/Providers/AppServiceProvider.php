<?php

namespace App\Providers;

use App\Services\DailyPromptService;
use App\Services\Maps\GoogleMapProvider;
use App\Services\Maps\MapProviderInterface;
use App\Services\Maps\OsmMapProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MapProviderInterface::class, function () {
            return match (config('services.maps.provider', 'osm')) {
                'google' => new GoogleMapProvider(new OsmMapProvider),
                default => new OsmMapProvider,
            };
        });
    }

    public function boot(): void
    {
        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        View::composer('components.app-layout', function ($view): void {
            $prompts = [];
            if (auth()->check()) {
                $prompts = app(DailyPromptService::class)->getActivePrompts(auth()->id());
            }
            $view->with('globalPrompts', $prompts);
        });
    }
}

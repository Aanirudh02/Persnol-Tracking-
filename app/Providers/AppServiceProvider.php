<?php

namespace App\Providers;

use App\Services\Maps\GoogleMapProvider;
use App\Services\Maps\MapProviderInterface;
use App\Services\Maps\OsmMapProvider;
use App\Services\DailyPromptService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MapProviderInterface::class, function () {
            return match (config('services.maps.provider', 'osm')) {
                'google' => new GoogleMapProvider,
                default => new OsmMapProvider,
            };
        });
    }

    public function boot(): void
    {
        View::composer('components.app-layout', function ($view) {
            $prompts = [];
            if (auth()->check()) {
                $prompts = app(DailyPromptService::class)->getActivePrompts(auth()->id());
            }
            $view->with('globalPrompts', $prompts);
        });
    }
}

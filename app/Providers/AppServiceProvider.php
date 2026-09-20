<?php

namespace App\Providers;

use App\Models\ActivityCategory;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Services\DailyPromptService;
use App\Services\Maps\MapProviderInterface;
use App\Services\Maps\OsmMapProvider;
use App\Services\OptionsService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MapProviderInterface::class, OsmMapProvider::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.custom');

        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        View::composer(['components.app-layout', 'components.quick-add-modal'], function ($view): void {
            $prompts = [];
            $quickAddOptions = [
                'expenseCategories' => collect(),
                'incomeCategories' => collect(),
                'activityCategories' => collect(),
                'paymentMethods' => [],
                'incomeSources' => [],
            ];

            if (auth()->check()) {
                $prompts = app(DailyPromptService::class)->getActivePrompts(auth()->id());
                $options = app(OptionsService::class);
                $quickAddOptions = [
                    'expenseCategories' => ExpenseCategory::query()->orderBy('name')->get(),
                    'incomeCategories' => IncomeCategory::query()->orderBy('name')->get(),
                    'activityCategories' => ActivityCategory::query()->orderBy('name')->get(),
                    'paymentMethods' => $options->names('payment_method'),
                    'incomeSources' => $options->names('income_source'),
                ];
            }

            $view->with([
                'globalPrompts' => $prompts,
                'quickAddOptions' => $quickAddOptions,
            ]);
        });
    }
}

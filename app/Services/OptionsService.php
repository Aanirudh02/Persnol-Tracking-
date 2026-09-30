<?php

namespace App\Services;

use App\Models\LookupOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class OptionsService
{
    public function for(string $type, ?int $userId = null): Collection
    {
        $userId = $userId ?? auth()->id();
        $cacheKey = "lookup_options:{$userId}:{$type}";

        $rows = Cache::remember($cacheKey, 300, function () use ($type, $userId) {
            return LookupOption::query()
                ->where('type', $type)
                ->where('is_active', true)
                ->where(function ($q) use ($userId) {
                    $q->whereNull('user_id')->orWhere('user_id', $userId);
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'user_id', 'type', 'name', 'icon', 'color', 'sort_order', 'is_active', 'is_system'])
                ->map(fn (LookupOption $opt) => $opt->toArray())
                ->values()
                ->all();
        });

        // Guard against corrupted cache (e.g. previously stored Eloquent models)
        if (! is_array($rows)) {
            Cache::forget($cacheKey);
            $rows = LookupOption::query()
                ->where('type', $type)
                ->where('is_active', true)
                ->where(function ($q) use ($userId) {
                    $q->whereNull('user_id')->orWhere('user_id', $userId);
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'user_id', 'type', 'name', 'icon', 'color', 'sort_order', 'is_active', 'is_system'])
                ->map(fn (LookupOption $opt) => $opt->toArray())
                ->values()
                ->all();
            Cache::put($cacheKey, $rows, 300);
        }

        return collect($rows);
    }

    public function names(string $type, ?int $userId = null): array
    {
        return $this->for($type, $userId)->pluck('name')->all();
    }

    public function create(string $type, string $name, ?int $userId = null, array $extra = []): LookupOption
    {
        $userId = $userId ?? auth()->id();

        $option = LookupOption::create(array_merge([
            'user_id' => $userId,
            'type' => $type,
            'name' => $name,
            'sort_order' => 99,
            'is_active' => true,
            'is_system' => false,
        ], $extra));

        LookupOption::clearCache($userId, $type);
        Cache::forget("lookup_options:{$userId}:{$type}");
        Cache::forget("lookup_options::{$type}");

        return $option;
    }

    public function ensureClassifications(?int $userId = null): Collection
    {
        $existing = $this->for('expense_classification', $userId);
        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $defaults = [
            ['name' => 'Necessary', 'color' => '#10b981', 'icon' => '🟢', 'sort_order' => 1],
            ['name' => 'Unnecessary', 'color' => '#f59e0b', 'icon' => '🟡', 'sort_order' => 2],
            ['name' => 'Luxury', 'color' => '#8b5cf6', 'icon' => '🟣', 'sort_order' => 3],
            ['name' => 'Emergency', 'color' => '#ef4444', 'icon' => '🔴', 'sort_order' => 4],
        ];

        foreach ($defaults as $def) {
            LookupOption::create([
                'user_id' => $userId,
                'type' => 'expense_classification',
                'name' => $def['name'],
                'color' => $def['color'],
                'icon' => $def['icon'],
                'sort_order' => $def['sort_order'],
                'is_active' => true,
                'is_system' => false,
            ]);
        }

        LookupOption::clearCache($userId, 'expense_classification');

        return $this->for('expense_classification', $userId);
    }
}

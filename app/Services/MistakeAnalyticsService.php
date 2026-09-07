<?php

namespace App\Services;

use App\Models\Mistake;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MistakeAnalyticsService
{
    public function getAnalytics(int $userId): array
    {
        $startOfMonth = Carbon::today()->startOfMonth()->toDateString();

        $totalMistakes = Mistake::where('user_id', $userId)->count();
        $thisMonth = Mistake::where('user_id', $userId)->where('date', '>=', $startOfMonth)->count();
        $resolvedCount = Mistake::where('user_id', $userId)->whereIn('status', ['Resolved', 'Learned'])->count();
        $openCount = Mistake::where('user_id', $userId)->whereIn('status', ['Open', 'Working On It'])->count();

        // Severity breakdown
        $severityCounts = Mistake::where('user_id', $userId)
            ->select('severity', DB::raw('count(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity')
            ->toArray();

        // Repeated categories
        $categoryBreakdown = Mistake::where('mistakes.user_id', $userId)
            ->join('mistake_categories', 'mistakes.category_id', '=', 'mistake_categories.id')
            ->select('mistake_categories.name', DB::raw('count(*) as count'))
            ->groupBy('mistake_categories.name')
            ->orderByDesc('count')
            ->get();

        // Identify repeated mistakes (categories with > 1 mistake)
        $repeatedCategories = $categoryBreakdown->filter(fn ($item) => $item->count > 1);

        return [
            'total' => $totalMistakes,
            'this_month' => $thisMonth,
            'resolved' => $resolvedCount,
            'open' => $openCount,
            'resolution_rate' => $totalMistakes > 0 ? round(($resolvedCount / $totalMistakes) * 100, 1) : 100,
            'severities' => $severityCounts,
            'categories' => $categoryBreakdown,
            'repeated_categories' => $repeatedCategories,
        ];
    }
}

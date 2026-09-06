<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\FoodEntry;
use App\Models\FuelEntry;
use App\Models\Income;
use App\Models\Mistake;
use App\Models\ScooterTrip;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DayTimelineService
{
    /**
     * Chronological day stream with expenses grouped and linked foods nested.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forDate(int $userId, string $date): array
    {
        $record = DailyRecord::where('user_id', $userId)->where('record_date', $date)->first();
        $items = [];

        if ($record && $record->wake_up_time) {
            $items[] = [
                'time' => Carbon::parse($record->wake_up_time)->format('h:i A'),
                'raw_time' => $record->wake_up_time,
                'type' => 'wakeup',
                'icon' => '☀️',
                'title' => 'Woke Up',
                'desc' => $record->sleep_duration_hours ? "Slept ~{$record->sleep_duration_hours} hrs" : 'Wake up recorded',
                'badge' => 'Morning',
                'badge_color' => 'bg-amber-100 text-amber-800',
                'color' => 'amber',
                'children' => [],
            ];
        }

        foreach (Activity::where('user_id', $userId)->where('date', $date)->get() as $a) {
            $items[] = [
                'time' => $a->start_time ? Carbon::parse($a->start_time)->format('h:i A') : '--:--',
                'raw_time' => $a->start_time ?? '00:00:00',
                'type' => 'activity',
                'icon' => '🎓',
                'title' => $a->title,
                'desc' => ($a->duration_minutes ? "{$a->duration_minutes}m" : '').($a->location ? " @ {$a->location}" : ''),
                'badge' => 'Activity',
                'badge_color' => 'bg-indigo-100 text-indigo-800',
                'color' => 'indigo',
                'children' => [],
            ];
        }

        $linkedFoodIds = [];
        $parentExpenses = Expense::where('user_id', $userId)
            ->where('date', $date)
            ->whereNull('parent_id')
            ->with(['subItems', 'foodEntries.category'])
            ->orderBy('time')
            ->get();

        foreach ($parentExpenses as $e) {
            $children = [];
            foreach ($e->subItems as $sub) {
                $children[] = [
                    'title' => $sub->description,
                    'desc' => '₹'.number_format($sub->totalAmount(), 2),
                ];
            }
            foreach ($e->foodEntries as $f) {
                $linkedFoodIds[] = $f->id;
                $children[] = [
                    'title' => $f->item_name.($f->is_snack ? ' (snack)' : ''),
                    'desc' => '₹'.number_format($f->totalAmount(), 2).($f->category ? ' · '.$f->category->name : ''),
                ];
            }

            $items[] = [
                'time' => $e->time ? Carbon::parse($e->time)->format('h:i A') : '--:--',
                'raw_time' => $e->time ?? '00:00:00',
                'type' => 'expense',
                'icon' => '💰',
                'title' => "Expense: {$e->description}",
                'desc' => '₹'.number_format($e->totalAmount(), 2).' via '.$e->payment_method,
                'badge' => 'Finance',
                'badge_color' => 'bg-rose-100 text-rose-800',
                'color' => 'rose',
                'url' => route('expenses.show', $e),
                'children' => $children,
            ];
        }

        foreach (FoodEntry::where('user_id', $userId)->where('date', $date)->with('category')->orderBy('time')->get() as $f) {
            if (in_array($f->id, $linkedFoodIds, true)) {
                continue;
            }
            $items[] = [
                'time' => $f->time ? Carbon::parse($f->time)->format('h:i A') : '--:--',
                'raw_time' => $f->time ?? '00:00:00',
                'type' => 'food',
                'icon' => $f->is_snack ? '☕' : '🍔',
                'title' => $f->item_name,
                'desc' => '₹'.number_format($f->totalAmount(), 2).($f->location ? " @ {$f->location}" : ''),
                'badge' => $f->is_snack ? 'Snack' : 'Meal',
                'badge_color' => 'bg-orange-100 text-orange-800',
                'color' => 'orange',
                'children' => [],
            ];
        }

        foreach (ScooterTrip::where('user_id', $userId)->where('date', $date)->get() as $t) {
            $items[] = [
                'time' => $t->start_time ? Carbon::parse($t->start_time)->format('h:i A') : '--:--',
                'raw_time' => $t->start_time ?? '00:00:00',
                'type' => 'scooter',
                'icon' => '🛵',
                'title' => $t->title ?? 'Scooter Trip',
                'desc' => "{$t->distance_km} km",
                'badge' => 'Trip',
                'badge_color' => 'bg-cyan-100 text-cyan-800',
                'color' => 'cyan',
                'children' => [],
            ];
        }

        foreach (FuelEntry::where('user_id', $userId)->where('date', $date)->get() as $fuel) {
            $items[] = [
                'time' => $fuel->time ? Carbon::parse($fuel->time)->format('h:i A') : '--:--',
                'raw_time' => $fuel->time ?? '00:00:00',
                'type' => 'fuel',
                'icon' => '⛽',
                'title' => 'Petrol: ₹'.number_format((float) $fuel->amount, 2),
                'desc' => "{$fuel->litres} L",
                'badge' => 'Fuel',
                'badge_color' => 'bg-emerald-100 text-emerald-800',
                'color' => 'emerald',
                'children' => [],
            ];
        }

        foreach (Mistake::where('user_id', $userId)->where('date', $date)->get() as $m) {
            $items[] = [
                'time' => $m->time ? Carbon::parse($m->time)->format('h:i A') : '--:--',
                'raw_time' => $m->time ?? '00:00:00',
                'type' => 'mistake',
                'icon' => '⚠️',
                'title' => "Mistake: {$m->title}",
                'desc' => "Severity: {$m->severity}",
                'badge' => 'Mistake',
                'badge_color' => 'bg-red-100 text-red-800',
                'color' => 'red',
                'children' => [],
            ];
        }

        if ($record && $record->sleep_time) {
            $items[] = [
                'time' => Carbon::parse($record->sleep_time)->format('h:i A'),
                'raw_time' => $record->sleep_time,
                'type' => 'sleep',
                'icon' => '🌙',
                'title' => 'Went to Sleep',
                'desc' => $record->day_rating ? "Rating: {$record->day_rating}/5 stars" : 'Sleep recorded',
                'badge' => 'Night',
                'badge_color' => 'bg-purple-100 text-purple-800',
                'color' => 'purple',
                'children' => [],
            ];
        }

        usort($items, fn ($a, $b) => strcmp((string) $a['raw_time'], (string) $b['raw_time']));

        return $items;
    }

    /**
     * @return array{income: float, expenses: float, fuel: float, snack_spend: float, snack_count: int, net: float}
     */
    public function dayWorkings(int $userId, string $date): array
    {
        $income = (float) Income::where('user_id', $userId)->where('date', $date)->sum('amount');
        $expenses = (float) Expense::where('user_id', $userId)->where('date', $date)->whereNull('parent_id')->where('is_voluntary', false)->sum(DB::raw('amount + gst_amount'));
        $fuel = (float) FuelEntry::where('user_id', $userId)->where('date', $date)->sum('amount');
        $snackSpend = (float) FoodEntry::where('user_id', $userId)->where('date', $date)->where('is_snack', true)->sum(DB::raw('amount + gst_amount'));
        $snackCount = (int) FoodEntry::where('user_id', $userId)->where('date', $date)->where('is_snack', true)->count();

        return [
            'income' => $income,
            'expenses' => $expenses,
            'fuel' => $fuel,
            'snack_spend' => $snackSpend,
            'snack_count' => $snackCount,
            'net' => $income - $expenses - $fuel,
        ];
    }
}

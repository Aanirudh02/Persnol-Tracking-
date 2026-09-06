<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\FoodEntry;
use App\Models\Mistake;
use App\Models\ScooterTrip;
use App\Services\DayTimelineService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyRecordController extends Controller
{
    public function recordWakeup(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'wake_up_time' => 'required',
        ]);

        $date = $request->date ?? Carbon::today()->toDateString();
        $record = DailyRecord::firstOrCreate(
            ['user_id' => $request->user()->id, 'record_date' => $date]
        );

        $record->update([
            'wake_up_time' => $request->wake_up_time,
            'wake_up_prompt_dismissed' => true,
        ]);

        $record->calculateSleepDuration();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'record' => $record]);
        }

        return back()->with('success', 'Wake-up time recorded successfully!');
    }

    public function recordSleep(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'sleep_time' => 'required',
            'sleep_quality' => 'nullable|integer|min:1|max:5',
            'day_rating' => 'nullable|integer|min:1|max:5',
            'sleep_notes' => 'nullable|string',
            'day_summary' => 'nullable|string',
        ]);

        $date = $request->date ?? Carbon::today()->toDateString();
        $record = DailyRecord::firstOrCreate(
            ['user_id' => $request->user()->id, 'record_date' => $date]
        );

        $record->update([
            'sleep_time' => $request->sleep_time,
            'sleep_quality' => $request->sleep_quality,
            'day_rating' => $request->day_rating,
            'sleep_notes' => $request->sleep_notes,
            'day_summary' => $request->day_summary,
            'sleep_prompt_dismissed' => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'record' => $record]);
        }

        return back()->with('success', 'Sleep and day rating saved successfully!');
    }

    public function dismissPrompt(Request $request, string $type)
    {
        $date = $request->get('date', Carbon::today()->toDateString());
        $record = DailyRecord::firstOrCreate(
            ['user_id' => $request->user()->id, 'record_date' => $date]
        );

        if ($type === 'wakeup') {
            $record->update(['wake_up_prompt_dismissed' => true]);
        } elseif ($type === 'sleep') {
            $record->update(['sleep_prompt_dismissed' => true]);
        }

        return back()->with('info', 'Prompt dismissed.');
    }

    public function calendar(Request $request, DayTimelineService $timelineService)
    {
        $user = $request->user();
        $year = (int) $request->get('year', Carbon::today()->year);
        $month = (int) $request->get('month', Carbon::today()->month);

        $currentMonth = Carbon::createFromDate($year, $month, 1);
        $daysInMonth = $currentMonth->daysInMonth;
        $startDayOfWeek = $currentMonth->copy()->startOfMonth()->dayOfWeek;

        $monthStart = $currentMonth->copy()->startOfMonth()->toDateString();
        $monthEnd = $currentMonth->copy()->endOfMonth()->toDateString();

        $expensesByDate = Expense::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->selectRaw('date, sum(amount + gst_amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $snacksByDate = FoodEntry::where('user_id', $user->id)
            ->where('is_snack', true)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->selectRaw('date, count(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $tripsByDate = ScooterTrip::where('user_id', $user->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->selectRaw('date, count(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $mistakesByDate = Mistake::where('user_id', $user->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->selectRaw('date, count(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $selectedDate = $request->get('date', Carbon::today()->toDateString());
        $timeline = $timelineService->forDate($user->id, $selectedDate);

        return view('calendar.index', compact(
            'year',
            'month',
            'currentMonth',
            'daysInMonth',
            'startDayOfWeek',
            'expensesByDate',
            'snacksByDate',
            'tripsByDate',
            'mistakesByDate',
            'selectedDate',
            'timeline'
        ));
    }

    public function timeline(Request $request, DayTimelineService $timelineService, ?string $date = null)
    {
        $user = $request->user();
        $date = $date ?? $request->get('date', Carbon::today()->toDateString());
        $timeline = $timelineService->forDate($user->id, $date);

        return view('calendar.timeline', compact('date', 'timeline'));
    }
}

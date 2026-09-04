<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SleepController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();
        $start = $today->copy()->subDays(59);

        $records = DailyRecord::where('user_id', $user->id)
            ->whereBetween('record_date', [$start->toDateString(), $today->toDateString()])
            ->orderByDesc('record_date')
            ->get()
            ->keyBy(fn (DailyRecord $r) => $r->record_date->toDateString());

        $days = [];
        for ($d = $today->copy(); $d->gte($start); $d->subDay()) {
            $key = $d->toDateString();
            $days[] = [
                'date' => $key,
                'label' => $d->format('D, d M Y'),
                'record' => $records->get($key),
            ];
        }

        return view('sleep.index', compact('days'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'wake_up_time' => $request->filled('wake_up_time') ? $request->input('wake_up_time') : null,
            'sleep_time' => $request->filled('sleep_time') ? $request->input('sleep_time') : null,
            'day_rating' => $request->filled('day_rating') ? $request->input('day_rating') : null,
            'sleep_quality' => $request->filled('sleep_quality') ? $request->input('sleep_quality') : null,
        ]);

        $validated = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'wake_up_time' => 'nullable|date_format:H:i',
            'sleep_time' => 'nullable|date_format:H:i',
            'sleep_quality' => 'nullable|integer|min:1|max:5',
            'day_rating' => 'nullable|integer|min:1|max:5',
        ]);

        $record = DailyRecord::firstOrCreate([
            'user_id' => $request->user()->id,
            'record_date' => $validated['date'],
        ]);

        $payload = [];
        if (! empty($validated['wake_up_time'])) {
            $payload['wake_up_time'] = $validated['wake_up_time'];
            $payload['wake_up_prompt_dismissed'] = true;
        }
        if (! empty($validated['sleep_time'])) {
            $payload['sleep_time'] = $validated['sleep_time'];
            $payload['sleep_prompt_dismissed'] = true;
        }
        if (isset($validated['sleep_quality'])) {
            $payload['sleep_quality'] = $validated['sleep_quality'];
        }
        if (isset($validated['day_rating'])) {
            $payload['day_rating'] = $validated['day_rating'];
        }

        if ($payload !== []) {
            $record->update($payload);
            $record->calculateSleepDuration();
        }

        return back()->with('success', 'Sleep / wake saved for '.$validated['date'].'.');
    }
}

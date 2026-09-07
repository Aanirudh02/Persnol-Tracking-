<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\DailyRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Activity::where('user_id', $user->id)->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        $activities = $query->orderByDesc('date')->orderByDesc('start_time')->paginate(15)->withQueryString();
        $categories = ActivityCategory::all();

        return view('activities.index', compact('activities', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'category_id' => 'nullable|exists:activity_categories,id',
            'date' => 'required|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'duration_minutes' => 'nullable|integer',
            'location' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('activities', 'public');
        }

        $dailyRecord = DailyRecord::firstOrCreate([
            'user_id' => $request->user()->id,
            'record_date' => $validated['date'],
        ]);

        // Auto calculate duration if start and end provided
        $duration = $validated['duration_minutes'] ?? null;
        if (! $duration && ! empty($validated['start_time']) && ! empty($validated['end_time'])) {
            $start = Carbon::parse($validated['date'].' '.$validated['start_time']);
            $end = Carbon::parse($validated['date'].' '.$validated['end_time']);
            if ($end->lt($start)) {
                $end->addDay();
            }
            $duration = $start->diffInMinutes($end);
        }

        Activity::create([
            'user_id' => $request->user()->id,
            'daily_record_id' => $dailyRecord->id,
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'duration_minutes' => $duration,
            'location' => $validated['location'] ?? null,
            'description' => $validated['description'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'image_path' => $imagePath,
        ]);

        return redirect()->route('activities.index')->with('success', 'Activity logged successfully!');
    }

    public function destroy(Request $request, Activity $activity)
    {
        if ($activity->user_id !== auth()->id()) {
            abort(403);
        }
        $activity->delete();

        return redirect()->route('activities.index')->with('success', 'Activity removed.');
    }
}

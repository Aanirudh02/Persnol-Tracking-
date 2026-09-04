<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mistake;
use App\Models\MistakeCategory;
use App\Models\DailyRecord;
use App\Services\MistakeAnalyticsService;
use Carbon\Carbon;

class MistakeController extends Controller
{
    public function index(Request $request, MistakeAnalyticsService $analyticsService)
    {
        $user = $request->user();
        $analytics = $analyticsService->getAnalytics($user->id);

        $query = Mistake::where('user_id', $user->id)->with('category');

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $mistakes = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(15)->withQueryString();
        $categories = MistakeCategory::all();

        return view('mistakes.index', compact('mistakes', 'categories', 'analytics'));
    }

    public function create()
    {
        $categories = MistakeCategory::all();
        return view('mistakes.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'category_id' => 'nullable|exists:mistake_categories,id',
            'date' => 'required|date',
            'time' => 'nullable',
            'what_happened' => 'required|string',
            'why_happened' => 'required|string',
            'what_should_have_done' => 'required|string',
            'lesson_learned' => 'required|string',
            'prevention_plan' => 'required|string',
            'severity' => 'required|in:Low,Medium,High,Critical',
            'status' => 'required|in:Open,Working On It,Resolved,Learned',
            'tags' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $dailyRecord = DailyRecord::firstOrCreate([
            'user_id' => $request->user()->id,
            'record_date' => $validated['date'],
        ]);

        $mistake = Mistake::create([
            'user_id' => $request->user()->id,
            'daily_record_id' => $dailyRecord->id,
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'date' => $validated['date'],
            'time' => $validated['time'] ?? Carbon::now()->format('H:i'),
            'what_happened' => $validated['what_happened'],
            'why_happened' => $validated['why_happened'],
            'what_should_have_done' => $validated['what_should_have_done'],
            'lesson_learned' => $validated['lesson_learned'],
            'prevention_plan' => $validated['prevention_plan'],
            'severity' => $validated['severity'],
            'status' => $validated['status'],
            'tags' => $validated['tags'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Check if repeated
        $repeatedCount = $mistake->getRepeatedCount();
        $message = 'Mistake & lesson recorded.';
        if ($repeatedCount > 0) {
            $message .= " ⚠️ Notice: You have {$repeatedCount} other recorded mistake(s) in this category. Focus on your prevention plan!";
        }

        return redirect()->route('mistakes.index')->with('success', $message);
    }

    public function show(Mistake $mistake)
    {
        if ($mistake->user_id !== auth()->id()) abort(403);
        return view('mistakes.show', compact('mistake'));
    }

    public function edit(Mistake $mistake)
    {
        if ($mistake->user_id !== auth()->id()) abort(403);
        $categories = MistakeCategory::all();
        return view('mistakes.edit', compact('mistake', 'categories'));
    }

    public function update(Request $request, Mistake $mistake)
    {
        if ($mistake->user_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'category_id' => 'nullable|exists:mistake_categories,id',
            'date' => 'required|date',
            'time' => 'nullable',
            'what_happened' => 'required|string',
            'why_happened' => 'required|string',
            'what_should_have_done' => 'required|string',
            'lesson_learned' => 'required|string',
            'prevention_plan' => 'required|string',
            'severity' => 'required|in:Low,Medium,High,Critical',
            'status' => 'required|in:Open,Working On It,Resolved,Learned',
            'tags' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $mistake->update($validated);

        return redirect()->route('mistakes.index')->with('success', 'Mistake record updated.');
    }

    public function destroy(Request $request, Mistake $mistake)
    {
        if ($mistake->user_id !== auth()->id()) abort(403);
        $mistake->delete();

        return redirect()->route('mistakes.index')->with('success', 'Mistake record archived.');
    }
}

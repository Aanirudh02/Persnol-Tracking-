<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Mistake;
use App\Models\MistakeCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Lessons registered on their own, optionally linked to the mistake they came from.
 */
class LessonController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Lesson::query()
            ->where('user_id', $user->id)
            ->with(['category', 'mistake']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('q')) {
            $term = (string) $request->string('q');
            $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhere('lesson', 'like', "%{$term}%")->orWhere('tags', 'like', "%{$term}%"));
        }

        $lessons = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(20)->withQueryString();
        $categories = MistakeCategory::query()->orderBy('name')->get();
        $totalLessons = Lesson::query()->where('user_id', $user->id)->count();
        $mistakeCount = Mistake::query()->where('user_id', $user->id)->count();

        return view('lessons.index', compact('lessons', 'categories', 'totalLessons', 'mistakeCount'));
    }

    public function create(Request $request): View
    {
        $lesson = new Lesson([
            'date' => now()->toDateString(),
            'mistake_id' => $request->integer('mistake_id') ?: null,
        ]);

        return view('lessons.create', [
            'lesson' => $lesson,
            'categories' => MistakeCategory::query()->orderBy('name')->get(),
            'mistakes' => $this->mistakeOptions($request->user()->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateLesson($request);

        Lesson::create([...$validated, 'user_id' => $request->user()->id]);

        return redirect()->route('lessons.index')->with('success', 'Lesson recorded.');
    }

    public function edit(Request $request, Lesson $lesson): View
    {
        abort_if($lesson->user_id !== $request->user()->id, 403);

        return view('lessons.edit', [
            'lesson' => $lesson,
            'categories' => MistakeCategory::query()->orderBy('name')->get(),
            'mistakes' => $this->mistakeOptions($request->user()->id),
        ]);
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        abort_if($lesson->user_id !== $request->user()->id, 403);

        $lesson->update($this->validateLesson($request));

        return redirect()->route('lessons.index')->with('success', 'Lesson updated.');
    }

    public function destroy(Request $request, Lesson $lesson): RedirectResponse
    {
        abort_if($lesson->user_id !== $request->user()->id, 403);

        $lesson->delete();

        return redirect()->route('lessons.index')->with('success', 'Lesson removed.');
    }

    /**
     * @return array{title: string, lesson: string, date: string, category_id: int|null, mistake_id: int|null, tags: string|null, notes: string|null}
     */
    private function validateLesson(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'lesson' => ['required', 'string'],
            'date' => ['required', 'date'],
            'category_id' => ['nullable', 'exists:mistake_categories,id'],
            'mistake_id' => ['nullable', Rule::exists('mistakes', 'id')->where('user_id', $request->user()->id)],
            'tags' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        return [
            'title' => $validated['title'],
            'lesson' => $validated['lesson'],
            'date' => $validated['date'],
            'category_id' => $validated['category_id'] ?? null,
            'mistake_id' => $validated['mistake_id'] ?? null,
            'tags' => $validated['tags'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }

    /**
     * @return Collection<int, Mistake>
     */
    private function mistakeOptions(int $userId)
    {
        return Mistake::query()
            ->where('user_id', $userId)
            ->orderByDesc('date')
            ->take(100)
            ->get(['id', 'title', 'date']);
    }
}

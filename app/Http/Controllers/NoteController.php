<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;
use Carbon\Carbon;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Note::where('user_id', $user->id);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('content', 'like', "%{$s}%")
                  ->orWhere('tags', 'like', "%{$s}%");
            });
        }

        $notes = $query->orderByDesc('is_pinned')
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('notes.index', compact('notes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'date' => 'required|date',
            'tags' => 'nullable|string|max:200',
            'is_pinned' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('notes', 'public');
        }

        Note::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'date' => $validated['date'],
            'tags' => $validated['tags'] ?? null,
            'is_pinned' => $request->boolean('is_pinned'),
            'image_path' => $imagePath,
        ]);

        return redirect()->route('notes.index')->with('success', 'Note created successfully!');
    }

    public function togglePin(Request $request, Note $note)
    {
        if ($note->user_id !== auth()->id()) abort(403);
        $note->update(['is_pinned' => !$note->is_pinned]);

        return back()->with('success', $note->is_pinned ? 'Note pinned!' : 'Note unpinned.');
    }

    public function update(Request $request, Note $note)
    {
        if ($note->user_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'date' => 'required|date',
            'tags' => 'nullable|string|max:200',
            'is_pinned' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('notes', 'public');
        }
        $validated['is_pinned'] = $request->boolean('is_pinned');

        $note->update($validated);

        return redirect()->route('notes.index')->with('success', 'Note updated!');
    }

    public function destroy(Request $request, Note $note)
    {
        if ($note->user_id !== auth()->id()) abort(403);
        $note->delete();

        return redirect()->route('notes.index')->with('success', 'Note deleted.');
    }
}

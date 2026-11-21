@csrf
<div>
    <label for="lesson-title" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Title *</label>
    <input id="lesson-title" type="text" name="title" required maxlength="200" value="{{ old('title', $lesson->title) }}" placeholder="e.g. Always check the due date the day before" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white text-sm font-semibold">
    @error('title')<p class="mt-1 text-rose-600">{{ $message }}</p>@enderror
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
        <label for="lesson-date" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Date *</label>
        <input id="lesson-date" type="date" name="date" required value="{{ old('date', $lesson->date?->toDateString() ?? now()->toDateString()) }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
    </div>
    <div>
        <label for="lesson-category" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Category</label>
        <select id="lesson-category" name="category_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
            <option value="">None</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected((string) old('category_id', $lesson->category_id) === (string) $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="lesson-mistake" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Linked mistake (optional)</label>
        <select id="lesson-mistake" name="mistake_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
            <option value="">Standalone lesson</option>
            @foreach($mistakes as $mistake)
                <option value="{{ $mistake->id }}" @selected((string) old('mistake_id', $lesson->mistake_id) === (string) $mistake->id)>{{ $mistake->date?->format('d M') }} · {{ \Illuminate\Support\Str::limit($mistake->title, 40) }}</option>
            @endforeach
        </select>
        @error('mistake_id')<p class="mt-1 text-rose-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label for="lesson-body" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Lesson *</label>
    <textarea id="lesson-body" name="lesson" required rows="5" placeholder="What did you learn? What will you do differently?" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">{{ old('lesson', $lesson->lesson) }}</textarea>
    @error('lesson')<p class="mt-1 text-rose-600">{{ $message }}</p>@enderror
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label for="lesson-tags" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tags</label>
        <input id="lesson-tags" type="text" name="tags" value="{{ old('tags', $lesson->tags) }}" placeholder="money, habits, college" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
    </div>
    <div>
        <label for="lesson-notes" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Notes</label>
        <input id="lesson-notes" type="text" name="notes" value="{{ old('notes', $lesson->notes) }}" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
    </div>
</div>

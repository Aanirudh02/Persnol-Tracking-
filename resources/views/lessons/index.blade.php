<x-app-layout title="Lessons">
    <div class="space-y-6">
        @include('mistakes._tabs')

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <span>Lessons</span>
                    <span class="text-lg">💡</span>
                </h1>
                <p class="text-xs text-slate-500">{{ $totalLessons }} lesson(s) registered on their own, alongside {{ $mistakeCount }} recorded mistake(s).</p>
            </div>
            <a href="{{ route('lessons.create') }}" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-semibold text-xs shadow-md shadow-sky-600/20 self-start transition">
                + Register Lesson
            </a>
        </div>

        <form method="GET" action="{{ route('lessons.index') }}" class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-xs shadow-sm sm:grid-cols-[1fr_220px_auto] dark:border-slate-800 dark:bg-slate-900">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search lessons, tags..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800">
            <select name="category_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800">
                <option value="">All categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 font-bold text-white cursor-pointer">Filter</button>
        </form>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @forelse($lessons as $lesson)
                <article class="flex flex-col gap-3 rounded-3xl border border-sky-100 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ $lesson->title }}</h2>
                            <p class="text-[11px] text-slate-400">
                                {{ $lesson->date->format('d M Y') }}
                                @if($lesson->category) · {{ $lesson->category->name }} @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2 text-xs">
                            <a href="{{ route('lessons.edit', $lesson) }}" class="font-semibold text-indigo-600 hover:underline">Edit</a>
                            <form action="{{ route('lessons.destroy', $lesson) }}" method="POST" onsubmit="return confirm('Remove this lesson?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-semibold text-rose-600 hover:underline cursor-pointer">Delete</button>
                            </form>
                        </div>
                    </div>
                    <p class="whitespace-pre-line rounded-2xl bg-sky-50/70 p-3 text-sm text-sky-950 dark:bg-sky-950/30 dark:text-sky-100">{{ $lesson->lesson }}</p>
                    <x-saved-note :text="$lesson->notes" />
                    @if($lesson->mistake)
                        <a href="{{ route('mistakes.show', $lesson->mistake) }}" class="text-[11px] font-semibold text-rose-600 hover:underline">From mistake: {{ $lesson->mistake->title }}</a>
                    @endif
                    @if($lesson->tags)
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(explode(',', $lesson->tags) as $tag)
                                <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">#{{ trim($tag) }}</span>
                            @endforeach
                        </div>
                    @endif
                </article>
            @empty
                <div class="md:col-span-2 rounded-3xl border border-dashed border-slate-300 p-10 text-center text-xs text-slate-400">
                    No lessons yet. <a href="{{ route('lessons.create') }}" class="font-semibold text-sky-600 hover:underline">Register your first lesson</a>.
                </div>
            @endforelse
        </div>

        <div>{{ $lessons->links() }}</div>
    </div>
</x-app-layout>

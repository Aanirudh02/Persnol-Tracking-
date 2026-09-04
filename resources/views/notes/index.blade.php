<x-app-layout title="Notes">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Personal Notes</h1>
                <p class="text-xs text-slate-500">Capture thoughts, project architectures, personal reflections, and ideas.</p>
            </div>
            <button onclick="window.openQuickAdd(); window.switchQuickTab('note');" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-semibold text-xs shadow-md shadow-sky-600/20 self-start transition active:scale-95">
                + New Note
            </button>
        </div>

        <!-- Search Bar -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-sky-100 dark:border-slate-800 shadow-sm">
            <form action="{{ route('notes.index') }}" method="GET" class="flex gap-2 text-xs">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title, content, or tag..." class="flex-1 px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                <button type="submit" class="px-4 py-2 rounded-xl bg-sky-50 hover:bg-sky-100 text-sky-700 font-semibold border border-sky-200 transition">Search</button>
            </form>
        </div>

        <!-- Notes Masonry Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($notes as $note)
                <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-sky-100 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-3 relative group {{ $note->is_pinned ? 'ring-2 ring-sky-400/50' : '' }}">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-bold text-base text-slate-900 dark:text-white">{{ $note->title }}</h3>
                            <form action="{{ route('notes.pin', $note) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-base cursor-pointer" title="{{ $note->is_pinned ? 'Unpin' : 'Pin' }}">
                                    {{ $note->is_pinned ? '📌' : '📍' }}
                                </button>
                            </form>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-2 whitespace-pre-line leading-relaxed">{{ $note->content }}</p>
                    </div>

                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
                        <span>{{ $note->date->format('d M Y') }}</span>
                        <div class="flex items-center gap-2">
                            @if($note->tags)
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[10px] text-indigo-500 font-medium">#{{ $note->tags }}</span>
                            @endif
                            <form action="{{ route('notes.destroy', $note) }}" method="POST" onsubmit="return confirm('Delete this note?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-500 hover:underline cursor-pointer">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-16 text-slate-400 text-xs">No notes found. Create your first note!</div>
            @endforelse
        </div>

        <div class="pt-2">
            {{ $notes->links() }}
        </div>
    </div>
</x-app-layout>

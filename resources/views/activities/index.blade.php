<x-app-layout title="Activities">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Daily Activities</h1>
                <p class="text-xs text-slate-500">Track gym, study sessions, college lectures, projects, and outings.</p>
            </div>
            <button onclick="window.openQuickAdd(); window.switchQuickTab('activity');" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-semibold text-xs shadow-md shadow-sky-600/20 self-start transition active:scale-95">
                + Log Activity
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse($activities as $act)
                <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-2.5">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">{{ $act->category?->name ?? 'Activity' }}</span>
                            <h3 class="font-bold text-base text-slate-900 dark:text-white mt-0.5">{{ $act->title }}</h3>
                        </div>
                        <span class="text-xs font-mono px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            {{ $act->duration_minutes ? "{$act->duration_minutes}m" : '--' }}
                        </span>
                    </div>

                    <div class="text-xs text-slate-500 space-y-1">
                        <p class="flex items-center gap-1.5">
                            <span>🕒</span>
                            <span>{{ $act->start_time ? \Carbon\Carbon::parse($act->start_time)->format('g:i A') : '' }} @if($act->end_time) - {{ \Carbon\Carbon::parse($act->end_time)->format('g:i A') }} @endif &bull; {{ $act->date->format('d M') }}</span>
                        </p>
                        @if($act->location)
                            <p class="flex items-center gap-1.5"><span>📍</span> <span>{{ $act->location }}</span></p>
                        @endif
                        @if($act->description)
                            <p class="text-slate-700 dark:text-slate-300 pt-1 text-xs">{{ $act->description }}</p>
                        @endif
                    </div>

                    <div class="pt-2 flex justify-end border-t border-slate-100 dark:border-slate-800">
                        <form action="{{ route('activities.destroy', $act) }}" method="POST" onsubmit="return confirm('Remove this activity?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-[11px] text-rose-600 hover:underline">Remove</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-16 text-slate-400 text-xs">No activities logged yet.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>

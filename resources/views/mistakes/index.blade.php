<x-app-layout title="Mistakes & Lessons">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <span>Mistakes & Lessons Learned</span>
                    <span class="text-lg">💡</span>
                </h1>
                <p class="text-xs text-slate-500">Record mistakes, analyze root causes, design prevention plans, and identify repeated patterns.</p>
            </div>
            <a href="{{ route('mistakes.create') }}" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs shadow-md shadow-rose-600/20 self-start transition">
                + Record Mistake & Lesson
            </a>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <span class="text-xs font-semibold text-slate-400">Total Recorded</span>
                <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $analytics['total'] }}</div>
                <span class="text-[10px] text-slate-400">{{ $analytics['this_month'] }} this month</span>
            </div>
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <span class="text-xs font-semibold text-slate-400">Resolved / Learned</span>
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $analytics['resolved'] }}</div>
                <span class="text-[10px] text-emerald-500 font-semibold">{{ $analytics['resolution_rate'] }}% resolution rate</span>
            </div>
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <span class="text-xs font-semibold text-slate-400">Open / Action Needed</span>
                <div class="text-2xl font-bold text-amber-500 mt-1">{{ $analytics['open'] }}</div>
                <span class="text-[10px] text-slate-400">Still working on it</span>
            </div>
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <span class="text-xs font-semibold text-slate-400">Repeated Categories</span>
                <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ count($analytics['repeated_categories']) }}</div>
                <span class="text-[10px] text-slate-400">Needs vigilance</span>
            </div>
        </div>

        <!-- Repeated Pattern Warning Alert if any -->
        @if(count($analytics['repeated_categories']) > 0)
            <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200">
                <p class="font-bold flex items-center gap-1.5 mb-1">
                    <span>⚠️</span> Repeated Mistake Patterns Identified:
                </p>
                <div class="flex gap-2 flex-wrap">
                    @foreach($analytics['repeated_categories'] as $rc)
                        <span class="px-2.5 py-1 rounded-full bg-amber-200/70 dark:bg-amber-900/60 font-medium">
                            {{ $rc->name }}: {{ $rc->count }} incidents
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Mistakes List Cards -->
        <div class="space-y-4">
            @forelse($mistakes as $m)
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $m->severity === 'Critical' ? 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300' : ($m->severity === 'High' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300') }}">
                                {{ $m->severity }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $m->status === 'Resolved' || $m->status === 'Learned' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                                {{ $m->status }}
                            </span>
                            <span class="text-xs font-semibold text-slate-500">{{ $m->category?->name ?? 'General' }}</span>
                        </div>
                        <span class="text-xs font-mono text-slate-400">{{ $m->date->format('d M Y') }}</span>
                    </div>

                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $m->title }}</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="p-3 rounded-2xl bg-rose-50/50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/30">
                            <span class="font-bold text-rose-700 dark:text-rose-300 block mb-0.5">What Happened & Why?</span>
                            <p class="text-slate-600 dark:text-slate-300">{{ $m->what_happened }}</p>
                            @if($m->why_happened)
                                <p class="text-slate-500 mt-1"><span class="font-semibold">Why:</span> {{ $m->why_happened }}</p>
                            @endif
                        </div>

                        <div class="p-3 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30">
                            <span class="font-bold text-emerald-700 dark:text-emerald-300 block mb-0.5">Lesson & Prevention:</span>
                            <p class="text-slate-600 dark:text-slate-300">{{ $m->lesson_learned }}</p>
                            @if($m->prevention_plan)
                                <p class="text-slate-500 mt-1"><span class="font-semibold">Prevention:</span> {{ $m->prevention_plan }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
                        <div class="flex items-center gap-1.5">
                            @if($m->tags)
                                @foreach(explode(',', $m->tags) as $tag)
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-[10px] text-slate-600 dark:text-slate-400">#{{ trim($tag) }}</span>
                                @endforeach
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('mistakes.edit', $m) }}" class="text-indigo-600 hover:underline">Edit</a>
                            <form action="{{ route('mistakes.destroy', $m) }}" method="POST" onsubmit="return confirm('Archive this mistake?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 hover:underline">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-slate-400 text-xs bg-white dark:bg-slate-900 rounded-3xl">
                    No mistakes recorded. Keep up the high discipline!
                </div>
            @endforelse
        </div>

        <div class="pt-2">
            {{ $mistakes->links() }}
        </div>
    </div>
</x-app-layout>

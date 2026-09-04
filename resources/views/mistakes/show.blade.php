<x-app-layout title="{{ $mistake->title }}">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Back Navigation & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <a href="{{ route('mistakes.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Mistakes & Lessons
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ route('mistakes.edit', $mistake) }}" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold shadow-sm transition active:scale-95">
                    Edit Mistake
                </a>
                <form action="{{ route('mistakes.destroy', $mistake) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this record?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-semibold border border-rose-200 transition">
                        Delete
                    </button>
                </form>
            </div>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-3xl border border-sky-100 shadow-sm p-6 sm:p-8 space-y-6">
            <!-- Header Info -->
            <div class="border-b border-slate-100 pb-5">
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    @if($mistake->category)
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200/60">
                            {{ $mistake->category->name }}
                        </span>
                    @endif
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $mistake->severity === 'high' ? 'bg-rose-100 text-rose-700' : ($mistake->severity === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                        Severity: {{ ucfirst($mistake->severity ?? 'Low') }}
                    </span>
                    @if($mistake->financial_loss && $mistake->financial_loss > 0)
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 border border-rose-200">
                            Loss: ₹{{ number_format($mistake->financial_loss, 2) }}
                        </span>
                    @endif
                </div>

                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 leading-snug">
                    {{ $mistake->title }}
                </h1>
                <p class="text-xs text-slate-400 mt-2 flex items-center gap-3">
                    <span>📅 {{ \Carbon\Carbon::parse($mistake->date)->format('F d, Y') }}</span>
                    @if($mistake->time)
                        <span>⏰ {{ \Carbon\Carbon::parse($mistake->time)->format('h:i A') }}</span>
                    @endif
                </p>
            </div>

            <!-- What Happened -->
            <div class="space-y-2">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">What Happened</h2>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 text-sm text-slate-800 leading-relaxed whitespace-pre-line">
                    {{ $mistake->what_happened }}
                </div>
            </div>

            <!-- Root Cause (Why It Happened) -->
            @if($mistake->why_it_happened)
                <div class="space-y-2">
                    <h2 class="text-xs font-bold text-amber-600 uppercase tracking-wider">Root Cause Analysis</h2>
                    <div class="p-4 rounded-2xl bg-amber-50/40 border border-amber-200/60 text-sm text-slate-800 leading-relaxed whitespace-pre-line">
                        {{ $mistake->why_it_happened }}
                    </div>
                </div>
            @endif

            <!-- Lesson Learned -->
            @if($mistake->lesson_learned)
                <div class="space-y-2">
                    <h2 class="text-xs font-bold text-sky-700 uppercase tracking-wider">Key Lesson Learned</h2>
                    <div class="p-4 rounded-2xl bg-sky-50/60 border border-sky-100 text-sm font-medium text-sky-950 leading-relaxed whitespace-pre-line">
                        {{ $mistake->lesson_learned }}
                    </div>
                </div>
            @endif

            <!-- Prevention Strategy -->
            @if($mistake->prevention_strategy)
                <div class="space-y-2">
                    <h2 class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Action & Prevention Plan</h2>
                    <div class="p-4 rounded-2xl bg-emerald-50/50 border border-emerald-100 text-sm text-slate-800 leading-relaxed whitespace-pre-line">
                        {{ $mistake->prevention_strategy }}
                    </div>
                </div>
            @endif

            <!-- Tags -->
            @if($mistake->tags)
                <div class="pt-3 border-t border-slate-100">
                    <span class="text-xs text-slate-400 font-medium mr-2">Tags:</span>
                    <div class="inline-flex flex-wrap gap-1.5 mt-1">
                        @foreach(explode(',', $mistake->tags) as $tag)
                            <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-[11px] font-medium text-slate-600">
                                #{{ trim($tag) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

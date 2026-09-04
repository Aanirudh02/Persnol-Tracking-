<x-app-layout title="Day Timeline - {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}">
    <div class="max-w-4xl mx-auto space-y-6">
        @php
            $parsedDate = \Carbon\Carbon::parse($date);
            $prevDate = $parsedDate->copy()->subDay()->toDateString();
            $nextDate = $parsedDate->copy()->addDay()->toDateString();
            $isToday = $parsedDate->isToday();
        @endphp

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-sky-100 shadow-sm">
            <div>
                <a href="{{ route('calendar', ['date' => $date]) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 transition mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to Month Calendar
                </a>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900">
                        {{ $parsedDate->format('l, F d, Y') }}
                    </h1>
                    @if($isToday)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-700">Today</span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1">Chronological sequence of all events, meals, expenses, and travels.</p>
            </div>

            <!-- Day Navigation -->
            <div class="flex items-center gap-2">
                <a href="{{ route('timeline', ['date' => $prevDate]) }}" class="px-3.5 py-2 rounded-xl bg-sky-50 text-sky-700 font-semibold text-xs hover:bg-sky-100 border border-sky-200/60 transition">
                    &larr; Prev Day
                </a>
                <a href="{{ route('timeline', ['date' => now()->toDateString()]) }}" class="px-3 py-2 rounded-xl bg-white text-slate-600 text-xs font-semibold border border-slate-200 hover:bg-slate-50 transition">
                    Today
                </a>
                <a href="{{ route('timeline', ['date' => $nextDate]) }}" class="px-3.5 py-2 rounded-xl bg-sky-50 text-sky-700 font-semibold text-xs hover:bg-sky-100 border border-sky-200/60 transition">
                    Next Day &rarr;
                </a>
            </div>
        </div>

        <!-- Timeline Stream -->
        <div class="bg-white rounded-3xl border border-sky-100 shadow-sm p-6 sm:p-8">
            <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="text-lg">⏱️</span>
                    <h2 class="font-bold text-sm text-slate-900">Activity Stream</h2>
                </div>
                <span class="text-xs text-slate-400 font-medium">{{ count($timeline) }} item{{ count($timeline) === 1 ? '' : 's' }} recorded</span>
            </div>

            @if(empty($timeline) || count($timeline) === 0)
                <div class="py-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-sky-50 text-sky-500 flex items-center justify-center mx-auto mb-4 text-2xl">
                        📅
                    </div>
                    <h3 class="text-base font-bold text-slate-800">No events logged for this date</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">Use the Quick Record button (+) to log expenses, meals, activities, or trips for this day.</p>
                    <div class="mt-5">
                        <button
                            type="button"
                            onclick="window.openQuickAdd()"
                            class="px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold shadow-sm transition active:scale-95"
                        >
                            + Quick Record Item
                        </button>
                    </div>
                </div>
            @else
                <div class="relative pl-6 sm:pl-8 space-y-6 before:absolute before:left-3 sm:before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-sky-100">
                    @foreach($timeline as $item)
                        <div class="relative flex items-start gap-4 group">
                            <!-- Bullet Icon Node -->
                            <div class="absolute -left-6 sm:-left-8 top-1 w-6 h-6 sm:w-8 sm:h-8 rounded-full bg-white border-2 border-sky-400 text-xs flex items-center justify-center shadow-sm z-10">
                                <span class="text-xs sm:text-sm">{{ $item['icon'] ?? '•' }}</span>
                            </div>

                            <!-- Card Content -->
                            <div class="flex-1 bg-sky-50/40 hover:bg-sky-50/80 border border-sky-100 rounded-2xl p-4 transition shadow-xs">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-1.5">
                                    <span class="font-bold text-sm text-slate-900">{{ $item['title'] }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $item['badge_color'] ?? 'bg-sky-100 text-sky-700' }}">
                                            {{ $item['badge'] ?? 'Event' }}
                                        </span>
                                        <span class="text-xs font-mono font-semibold text-slate-400">{{ $item['time'] }}</span>
                                    </div>
                                </div>
                                @if(!empty($item['desc']))
                                    <p class="text-xs text-slate-600">{{ $item['desc'] }}</p>
                                @endif
                                @if(!empty($item['children']))
                                    <ul class="mt-2 space-y-1 border-t border-sky-100 pt-2">
                                        @foreach($item['children'] as $child)
                                            <li class="text-xs text-slate-600 flex justify-between gap-2">
                                                <span>{{ $child['title'] }}</span>
                                                <span class="font-medium">{{ $child['desc'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

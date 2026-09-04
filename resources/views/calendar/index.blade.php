<x-app-layout title="Calendar & Timeline">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Life Calendar</h1>
                <p class="text-xs text-slate-500">Every day at a glance with indicators for expenses, snacks, trips, and mistakes.</p>
            </div>

            <!-- Month Nav -->
            <div class="flex items-center gap-2">
                @php
                    $prevMonth = $currentMonth->copy()->subMonth();
                    $nextMonth = $currentMonth->copy()->addMonth();
                @endphp
                <a href="{{ route('calendar', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}" class="p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition">
                    &larr; Prev
                </a>
                <span class="px-4 py-1.5 font-bold text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl">
                    {{ $currentMonth->translatedFormat('F Y') }}
                </span>
                <a href="{{ route('calendar', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition">
                    Next &rarr;
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- CALENDAR GRID (8 Cols) -->
            <div class="lg:col-span-8 bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <!-- Day of week headers -->
                <div class="grid grid-cols-7 text-center pb-3 text-xs font-bold text-slate-400 border-b border-slate-100 dark:border-slate-800">
                    <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                </div>

                <!-- Days -->
                <div class="grid grid-cols-7 gap-1 sm:gap-2 pt-3">
                    @for($i = 0; $i < $startDayOfWeek; $i++)
                        <div class="h-20 sm:h-24 p-1 rounded-2xl bg-slate-50/50 dark:bg-slate-900/30 opacity-40"></div>
                    @endfor

                    @for($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $isToday = $dateStr === now()->toDateString();
                            $isSelected = $dateStr === $selectedDate;
                            $exp = $expensesByDate[$dateStr] ?? null;
                            $snk = $snacksByDate[$dateStr] ?? null;
                            $trp = $tripsByDate[$dateStr] ?? null;
                            $mst = $mistakesByDate[$dateStr] ?? null;
                        @endphp
                        <a
                            href="{{ route('calendar', ['year' => $year, 'month' => $month, 'date' => $dateStr]) }}"
                            class="h-20 sm:h-24 p-1.5 rounded-2xl border transition flex flex-col justify-between text-left {{ $isSelected ? 'border-indigo-600 bg-indigo-50/60 dark:bg-indigo-950/40 ring-2 ring-indigo-500/20' : ($isToday ? 'border-amber-400 bg-amber-50/30 dark:bg-amber-950/20' : 'border-slate-100 dark:border-slate-800/80 hover:bg-slate-50 dark:hover:bg-slate-800/50') }}"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold {{ $isToday ? 'w-5 h-5 rounded-full bg-amber-500 text-white flex items-center justify-center text-[10px]' : 'text-slate-700 dark:text-slate-300' }}">
                                    {{ $day }}
                                </span>
                            </div>

                            <!-- Indicator Pills -->
                            <div class="space-y-0.5 text-[9px] font-semibold leading-tight overflow-hidden">
                                @if($exp)
                                    <div class="truncate px-1 rounded bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300">₹{{ number_format($exp, 0) }}</div>
                                @endif
                                @if($snk)
                                    <div class="truncate px-1 rounded bg-orange-100 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300">🍔 {{ $snk }}</div>
                                @endif
                                @if($trp)
                                    <div class="truncate px-1 rounded bg-cyan-100 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300">🛵 {{ $trp }}</div>
                                @endif
                                @if($mst)
                                    <div class="truncate px-1 rounded bg-red-100 dark:bg-red-950/60 text-red-700 dark:text-red-300">⚠️ {{ $mst }}</div>
                                @endif
                            </div>
                        </a>
                    @endfor
                </div>
            </div>

            <!-- SELECTED DAY TIMELINE (4 Cols) -->
            <div class="lg:col-span-4 bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h2 class="font-bold text-sm text-slate-900 dark:text-white">Day Timeline</h2>
                        <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, M j, Y') }}</p>
                    </div>
                    <button onclick="window.openQuickAdd()" class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 text-xs font-semibold">+ Event</button>
                </div>

                @if(count($timeline) === 0)
                    <div class="text-center py-12 text-slate-400 text-xs">
                        <span class="text-2xl block mb-2">📅</span>
                        No entries recorded for this date.
                    </div>
                @else
                    <div class="space-y-3 max-h-[550px] overflow-y-auto pr-1">
                        @foreach($timeline as $item)
                            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/70 border border-slate-100 dark:border-slate-800 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                        <span>{{ $item['icon'] }}</span>
                                        <span>{{ $item['title'] }}</span>
                                    </span>
                                    <span class="text-[10px] font-mono text-slate-400">{{ $item['time'] }}</span>
                                </div>
                                @if(!empty($item['desc']))
                                    <p class="text-xs text-slate-600 dark:text-slate-400">{{ $item['desc'] }}</p>
                                @endif
                                @if(!empty($item['children']))
                                    <ul class="mt-1 space-y-0.5 pl-2 border-l border-slate-200">
                                        @foreach($item['children'] as $child)
                                            <li class="text-[10px] text-slate-500 flex justify-between gap-2">
                                                <span>{{ $child['title'] }}</span>
                                                <span>{{ $child['desc'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <span class="inline-block text-[9px] font-semibold px-2 py-0.5 rounded-full {{ $item['badge_color'] }}">{{ $item['badge'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

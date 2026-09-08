<x-app-layout title="Dashboard">
    <div class="space-y-6">

        <!-- 1. GREETING & DATE HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <span>Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 17 ? 'Afternoon' : 'Evening') }}, {{ auth()->user()->name }}</span>
                    <span class="inline-block animate-bounce">👋</span>
                </h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">
                    {{ now()->translatedFormat('l, F j, Y') }} &bull; <span class="text-sky-600 dark:text-sky-400 font-semibold">Today's Operating Pulse</span>
                </p>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex items-center gap-2 flex-wrap">
                <button onclick="window.openQuickAdd(); window.switchQuickTab('expense');" class="px-3.5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold shadow-sm flex items-center gap-1.5 transition active:scale-95 cursor-pointer">
                    <span>+</span> Expense
                </button>
                <button onclick="window.openQuickAdd(); window.switchQuickTab('food');" class="px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 hover:bg-sky-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-sm transition active:scale-95 cursor-pointer">
                    🍔 Food
                </button>
                <button onclick="window.openQuickAdd(); window.switchQuickTab('scooter');" class="px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 hover:bg-sky-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-sm transition active:scale-95 cursor-pointer">
                    🛵 Trip
                </button>
                <button onclick="window.openQuickAdd(); window.switchQuickTab('mistake');" class="px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-sky-100 dark:border-slate-700 hover:bg-sky-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-sm transition active:scale-95 cursor-pointer">
                    ⚠️ Mistake
                </button>
            </div>
        </div>

        <!-- 2. SMART TIME-BASED PROMPTS BANNER -->
        @if(count($activePrompts) > 0)
            <div class="space-y-3">
                @foreach($activePrompts as $prompt)
                    <div class="relative overflow-hidden p-5 rounded-3xl bg-gradient-to-r from-sky-600 via-blue-600 to-indigo-700 text-white shadow-xl border border-sky-400/30">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-3.5">
                                <span class="text-3xl p-2.5 rounded-2xl bg-white/10 backdrop-blur-md">{{ $prompt['icon'] }}</span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-base text-white">{{ $prompt['title'] }}</h3>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-400 text-slate-950 uppercase tracking-wider">Action Pending</span>
                                    </div>
                                    <p class="text-xs text-indigo-200 mt-1">{{ $prompt['message'] }}</p>
                                </div>
                            </div>

                            <!-- Interactive Prompt Inputs -->
                            @if($prompt['type'] === 'wakeup')
                                <form action="{{ route('daily.wakeup') }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    <input type="time" name="wake_up_time" id="prompt-wake-time" value="{{ $prompt['currentTime'] }}" class="px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-amber-400">
                                    <button type="button" onclick="document.getElementById('prompt-wake-time').value='{{ now()->format('H:i') }}'" class="px-3 py-2 rounded-xl bg-white/15 hover:bg-white/25 text-xs text-white transition">Current Time</button>
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs shadow-md transition">Save</button>
                                    @if(isset($prompt['dismissUrl']))
                                        <a href="{{ $prompt['dismissUrl'] }}" class="p-2 text-white/60 hover:text-white text-xs" title="Dismiss">&times;</a>
                                    @endif
                                </form>
                            @elseif($prompt['type'] === 'sleep')
                                <form action="{{ route('daily.sleep') }}" method="POST" class="flex items-center gap-2 flex-wrap">
                                    @csrf
                                    <input type="time" name="sleep_time" id="prompt-sleep-time" value="{{ $prompt['currentTime'] }}" class="px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-white text-xs font-semibold">
                                    <select name="day_rating" class="px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-white text-xs">
                                        <option value="5" class="text-slate-900">⭐⭐⭐⭐⭐ Excellent</option>
                                        <option value="4" class="text-slate-900">⭐⭐⭐⭐ Good</option>
                                        <option value="3" class="text-slate-900">⭐⭐⭐ Average</option>
                                    </select>
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-purple-400 hover:bg-purple-300 text-slate-950 font-bold text-xs shadow-md transition">Save Sleep</button>
                                    @if(isset($prompt['dismissUrl']))
                                        <a href="{{ $prompt['dismissUrl'] }}" class="p-2 text-white/60 hover:text-white text-xs">&times;</a>
                                    @endif
                                </form>
                            @elseif($prompt['type'] === 'petrol')
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('petrol.index') }}" class="px-4 py-2 rounded-xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 font-bold text-xs shadow-md transition">Record Petrol</a>
                                    <button onclick="this.closest('.relative').remove()" class="px-3 py-2 rounded-xl bg-white/10 text-xs text-white">Remind Later</button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- 2.5 PERIOD APPROACH & WALLET CURRENT BALANCES -->
        <div class="space-y-3.5">
            <!-- Period Toggle Switcher -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-slate-900 p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-2xs">
                <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
                    <a href="{{ route('dashboard', ['date' => $date, 'period' => 'today']) }}" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $period === 'today' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                        📅 Today
                    </a>
                    <a href="{{ route('dashboard', ['date' => $date, 'period' => 'week']) }}" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $period === 'week' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                        📊 This Week
                    </a>
                    <a href="{{ route('dashboard', ['date' => $date, 'period' => 'month']) }}" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $period === 'month' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                        🗓️ This Month
                    </a>
                </div>
                <div class="text-xs font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1.5 px-2">
                    <span>Range:</span>
                    <strong class="text-slate-800 dark:text-slate-200">
                        @if($period === 'today')
                            {{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}
                        @elseif($period === 'week')
                            {{ \Carbon\Carbon::parse($startDate)->format('d M') }} &ndash; {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                        @else
                            {{ \Carbon\Carbon::parse($startDate)->format('F Y') }}
                        @endif
                    </strong>
                </div>
            </div>

            <!-- Current Balance & Mode Breakdown Card -->
            <div class="p-4 sm:p-5 rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-white shadow-md border border-slate-700/60 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-700/60 pb-3">
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Current Balance (All Modes)</div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-white mt-0.5">
                            ₹{{ number_format($currentBalance, 2) }}
                        </div>
                    </div>
                    <a href="{{ route('finance.index') }}" class="self-start sm:self-auto text-xs font-semibold text-sky-400 hover:text-sky-300 flex items-center gap-1 transition">
                        <span>Finance Hub & Wallets</span> &rarr;
                    </a>
                </div>

                @if($wallets->isNotEmpty())
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
                        @foreach($wallets as $wallet)
                            @php
                                $modeIcons = ['Cash' => '💵', 'UPI' => '📱', 'Card' => '💳', 'Bank Transfer' => '🏦', 'Other' => '💰'];
                                $icon = $modeIcons[$wallet->payment_method] ?? '💳';
                            @endphp
                            <div class="p-3 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 transition flex flex-col justify-between">
                                <div class="flex items-center justify-between text-xs text-slate-300 font-semibold mb-1">
                                    <span class="truncate">{{ $wallet->payment_method }}</span>
                                    <span>{{ $icon }}</span>
                                </div>
                                <div class="mt-1">
                                    <div class="text-sm sm:text-base font-bold text-white">
                                        ₹{{ number_format($wallet->current_balance, 2) }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 flex items-center justify-between mt-0.5">
                                        <span>Open: ₹{{ number_format($wallet->opening_balance, 0) }}</span>
                                        @if($wallet->is_enabled)
                                            <span class="text-emerald-400 font-bold" title="Wallet Enabled">●</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- 3. PRIMARY METRIC CARDS -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
            <!-- Wake Up -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-slate-400 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Wake Up</span>
                    <span class="text-lg">☀️</span>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                    {{ $todayRecord->wake_up_time ? \Carbon\Carbon::parse($todayRecord->wake_up_time)->format('g:i A') : '--:--' }}
                </div>
                <span class="text-[11px] text-slate-500 mt-1">
                    {{ $todayRecord->sleep_duration_hours ? "~{$todayRecord->sleep_duration_hours} hrs sleep" : 'Recorded today' }}
                </span>
            </div>

            <!-- Sleep -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-slate-400 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Sleep</span>
                    <span class="text-lg">🌙</span>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                    @if($todayRecord->sleep_time)
                        {{ \Carbon\Carbon::parse($todayRecord->sleep_time)->format('g:i A') }}
                    @elseif($yesterdayRecord && $yesterdayRecord->sleep_time)
                        {{ \Carbon\Carbon::parse($yesterdayRecord->sleep_time)->format('g:i A') }}
                    @else
                        --:--
                    @endif
                </div>
                <span class="text-[11px] text-slate-500 mt-1">
                    {{ $todayRecord->sleep_time ? 'Today sleep' : 'Yesterday sleep' }}
                </span>
            </div>

            <!-- Money Received -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-emerald-500 mb-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Received</span>
                    <span class="text-lg">💵</span>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                    ₹{{ number_format($moneyReceived, 0) }}
                </div>
                <span class="text-[11px] text-slate-500 mt-1">{{ ucfirst($period) }}'s Income</span>
            </div>

            <!-- Expenses -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-rose-500 mb-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Expenses</span>
                    <span class="text-lg">💰</span>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-rose-600 dark:text-rose-400">
                    ₹{{ number_format($expensesTotal, 0) }}
                </div>
                <span class="text-[11px] text-slate-500 mt-1">Spent {{ $period === 'today' ? 'today' : ($period === 'week' ? 'this week' : 'this month') }}</span>
            </div>

            <!-- Snacks -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-amber-500 mb-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Snacks</span>
                    <span class="text-lg">☕</span>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-amber-600 dark:text-amber-400">
                    {{ $snacksCount }}
                </div>
                <span class="text-[11px] text-slate-500 mt-1">{{ ucfirst($period) }} count</span>
            </div>

            <!-- Activities -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-indigo-500 mb-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Activities</span>
                    <span class="text-lg">🎓</span>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                    {{ $activitiesCount }}
                </div>
                <span class="text-[11px] text-slate-500 mt-1">Completed</span>
            </div>

            <!-- Scooter Trips -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-cyan-500 mb-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Scooter Trips</span>
                    <span class="text-lg">🛵</span>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-cyan-600 dark:text-cyan-400">
                    {{ $scooterTripsCount }}
                </div>
                <span class="text-[11px] text-slate-500 mt-1">{{ $scooterDistanceKm }} km traveled</span>
            </div>

            <!-- Petrol -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-teal-500 mb-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Petrol</span>
                    <span class="text-lg">⛽</span>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-teal-600 dark:text-teal-400">
                    ₹{{ number_format($petrolSpent, 0) }}
                </div>
                <span class="text-[11px] text-slate-500 mt-1">Filled {{ $period === 'today' ? 'today' : ($period === 'week' ? 'this week' : 'this month') }}</span>
            </div>

            <!-- Mistakes -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-red-500 mb-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Mistakes</span>
                    <span class="text-lg">⚠️</span>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ $mistakesCount }}
                </div>
                <span class="text-[11px] text-slate-500 mt-1">Recorded {{ $period === 'today' ? 'today' : ($period === 'week' ? 'this week' : 'this month') }}</span>
            </div>

            <!-- Quick Add Tile -->
            <div onclick="window.openQuickAdd()" class="p-4 rounded-3xl bg-sky-50/80 dark:bg-sky-950/40 border border-dashed border-sky-300 dark:border-sky-800/80 hover:bg-sky-100 dark:hover:bg-sky-900/40 transition cursor-pointer flex flex-col items-center justify-center text-center">
                <span class="text-2xl font-bold text-sky-600 dark:text-sky-400">+</span>
                <span class="text-xs font-bold text-sky-700 dark:text-sky-300 mt-1">Quick Add</span>
                <span class="text-[10px] text-slate-500">Record event</span>
            </div>
        </div>

        <!-- 4. TWO-COLUMN: TODAY'S TIMELINE + SPENDING CHART -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- TODAY'S CHRONOLOGICAL TIMELINE (7 cols) -->
            <div class="lg:col-span-7 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-sky-100/90 dark:border-slate-800 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                            <span>{{ $isToday ? "Today's" : \Carbon\Carbon::parse($date)->format('D, d M') }} Life Timeline</span>
                            @if($isToday)<span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>@endif
                        </h2>
                        <p class="text-xs text-slate-500">Pick a day to review events and money</p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('dashboard', ['date' => $prevDate]) }}" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold">&larr;</a>
                        <form method="GET" action="{{ route('dashboard') }}">
                            <input type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}" onchange="this.form.submit()" class="px-2 py-1.5 rounded-lg border border-slate-200 text-xs">
                        </form>
                        <a href="{{ route('dashboard', ['date' => now()->toDateString()]) }}" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold">Today</a>
                        <a href="{{ route('dashboard', ['date' => $nextDate]) }}" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold">&rarr;</a>
                        <a href="{{ route('calendar', ['date' => $date]) }}" class="text-xs font-semibold text-sky-600 hover:underline">Calendar</a>
                    </div>
                </div>

                <div class="mb-5 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-2"><span class="text-slate-400">In</span><div class="font-bold">₹{{ number_format($workings['income'], 0) }}</div></div>
                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-2"><span class="text-slate-400">Expenses</span><div class="font-bold">₹{{ number_format($workings['expenses'], 0) }}</div></div>
                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-2"><span class="text-slate-400">Fuel</span><div class="font-bold">₹{{ number_format($workings['fuel'], 0) }}</div></div>
                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-2"><span class="text-slate-400">Net</span><div class="font-bold">₹{{ number_format($workings['net'], 0) }}</div></div>
                </div>
                <p class="text-[11px] text-slate-400 mb-4">Expense = cash truth (parents only). Snack ₹ on food rows is habit detail; link snacks under an expense to group them.</p>

                @if(count($timeline) === 0)
                    <div class="text-center py-12 text-slate-400">
                        <span class="text-3xl block mb-2">📜</span>
                        <p class="text-xs font-medium">No events logged for this day yet.</p>
                        <button onclick="window.openQuickAdd()" class="mt-3 px-3 py-1.5 rounded-lg bg-sky-50 dark:bg-sky-950 text-sky-600 dark:text-sky-400 text-xs font-semibold">+ Log first event</button>
                    </div>
                @else
                    <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-sky-100 dark:before:bg-slate-800">
                        @foreach($timeline as $item)
                            <div class="relative flex items-start gap-3.5 group">
                                <span class="absolute -left-6 top-0.5 w-4.5 h-4.5 rounded-full bg-white dark:bg-slate-900 border-2 border-sky-500 flex items-center justify-center text-[10px] shadow-sm"></span>

                                <div class="flex-1 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 hover:border-indigo-300 dark:hover:border-indigo-700 transition">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                            <span>{{ $item['icon'] }}</span>
                                            @if(!empty($item['url']))
                                                <a href="{{ $item['url'] }}" class="hover:underline">{{ $item['title'] }}</a>
                                            @else
                                                <span>{{ $item['title'] }}</span>
                                            @endif
                                        </span>
                                        <span class="text-[11px] font-mono font-medium text-slate-400">{{ $item['time'] }}</span>
                                    </div>
                                    @if(!empty($item['desc']))
                                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">{{ $item['desc'] }}</p>
                                    @endif
                                    @if(!empty($item['children']))
                                        <ul class="mt-2 space-y-1 border-t border-slate-100 pt-2">
                                            @foreach($item['children'] as $child)
                                                <li class="text-[11px] text-slate-600 flex justify-between gap-2">
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

            <!-- SPENDING CHART & QUICK ACTIONS (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                <!-- 7-Day Expense Trend Chart -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="font-bold text-sm text-slate-900 dark:text-white">7-Day Spending</h2>
                            <p class="text-xs text-slate-500">Daily expenses breakdown</p>
                        </div>
                        <a href="{{ route('expenses.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Expenses &rarr;</a>
                    </div>
                    <div class="h-52 w-full">
                        <canvas id="weeklyExpenseChart"></canvas>
                    </div>
                </div>

                <!-- Navigation Shortcuts Card -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                    <h2 class="font-bold text-sm text-slate-900 dark:text-white">Quick Access</h2>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <a href="{{ route('friends.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 flex items-center gap-2">
                            <span>👥</span> Friend Debts
                        </a>
                        <a href="{{ route('payments.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 flex items-center gap-2">
                            <span>✅</span> Reconcile
                        </a>
                        <a href="{{ route('petrol.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 flex items-center gap-2">
                            <span>⛽</span> Petrol Log
                        </a>
                        <a href="{{ route('mistakes.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 flex items-center gap-2">
                            <span>⚠️</span> Lessons
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Chart.js Init Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('weeklyExpenseChart');
            if (!ctx) return;

            const labels = @json($last7Days);
            const data = @json($expenseChartData);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Expenses (₹)',
                        data: data,
                        backgroundColor: '#0284c7',
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 } }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (v) => '₹' + v,
                                font: { size: 10 }
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-app-layout>

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
                                        <button type="submit" formaction="{{ $prompt['dismissUrl'] }}" formnovalidate class="p-2 text-white/60 hover:text-white text-xs cursor-pointer" title="Dismiss">&times;</button>
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
                                        <button type="submit" formaction="{{ $prompt['dismissUrl'] }}" formnovalidate class="p-2 text-white/60 hover:text-white text-xs cursor-pointer" title="Dismiss">&times;</button>
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
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 bg-white dark:bg-slate-900 p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-2xs">
                <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl flex-wrap">
                    <a href="{{ route('dashboard', ['date' => $date, 'period' => 'today']) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $period === 'today' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                        📅 Today
                    </a>
                    <a href="{{ route('dashboard', ['date' => $date, 'period' => 'week']) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $period === 'week' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                        📊 This Week
                    </a>
                    <a href="{{ route('dashboard', ['date' => $date, 'period' => 'month', 'month' => now()->format('Y-m')]) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ ($period === 'month' && (!isset($selectedMonth) || $selectedMonth === now()->format('Y-m'))) ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                        🗓️ This Month
                    </a>

                    <!-- Custom Month Selector -->
                    <div class="flex items-center gap-1 pl-1 border-l border-slate-200 dark:border-slate-700">
                        <span class="text-[10px] font-bold text-slate-400 hidden sm:inline">Month:</span>
                        <input type="month" name="month" value="{{ $selectedMonth ?? now()->format('Y-m') }}" onchange="window.location.href='{{ route('dashboard') }}?date={{ $date }}&period=month&month=' + this.value" class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200 shadow-2xs cursor-pointer" title="Pick any custom month">
                    </div>

                    <!-- Custom Range Button -->
                    <button type="button" onclick="document.getElementById('dash-custom-range-form').classList.toggle('hidden')" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $period === 'custom' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-200/50' }}">
                        📅 Custom Range
                    </button>
                </div>

                <div class="text-xs font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1.5 px-2">
                    <span>Range:</span>
                    <strong class="text-slate-800 dark:text-slate-200">
                        @if($period === 'today')
                            {{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}
                        @elseif($period === 'week')
                            {{ \Carbon\Carbon::parse($startDate)->format('d M') }} &ndash; {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                        @elseif($period === 'month')
                            {{ \Carbon\Carbon::parse($startDate)->format('F Y') }}
                        @else
                            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                        @endif
                    </strong>
                </div>
            </div>

            <!-- Custom Range Collapsible Form -->
            <form id="dash-custom-range-form" method="GET" action="{{ route('dashboard') }}" class="{{ $period === 'custom' ? '' : 'hidden' }} p-3.5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-wrap items-center gap-3 text-xs">
                <input type="hidden" name="period" value="custom">
                <input type="hidden" name="date" value="{{ $date }}">
                <div class="flex items-center gap-1.5">
                    <label class="font-bold text-slate-500">From:</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-white">
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="font-bold text-slate-500">To:</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-white">
                </div>
                <button type="submit" class="px-4 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-xs transition">
                    Apply Custom Range
                </button>
            </form>

            <!-- Daily Cash Register: Opening & Closing Balances (Total & Category-wise in same card) -->
            @php
                $regTot = $dailyRegister['total'] ?? ['opening' => 0, 'closing' => 0, 'inflow' => 0, 'outflow' => 0, 'adjustment' => 0, 'net_flow' => 0];
                $regCats = $dailyRegister['categories'] ?? [];
                $dayLabel = \Carbon\Carbon::parse($date)->format('l, d M Y');
                $isTodayDate = ($date === \Carbon\Carbon::today()->toDateString());
            @endphp
            <div class="p-5 sm:p-6 rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-white shadow-xl border border-slate-700/70 space-y-5">
                <!-- Top Row: Date, Title & Quick Links -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-700/60 pb-4">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span class="text-xl">🧾</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-black text-white uppercase tracking-wider">
                                    Daily Cash Register
                                </h3>
                                @if($isTodayDate)
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-500 text-white shadow-xs">
                                        TODAY
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold text-slate-300 bg-white/10">
                                        {{ \Carbon\Carbon::parse($date)->format('d M') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ $dayLabel }} · Day Opening to Closing ledger
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 self-start sm:self-auto">
                        <a href="{{ route('daily-balances.today') }}" class="px-3 py-1.5 rounded-xl bg-emerald-600/30 hover:bg-emerald-600/50 border border-emerald-500/40 text-emerald-300 text-xs font-bold transition flex items-center gap-1">
                            <span>📊</span> Live Flow
                        </a>
                        <a href="{{ route('daily-balances.index', ['date' => $date]) }}" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-sm flex items-center gap-1">
                            <span>Register Table</span> &rarr;
                        </a>
                    </div>
                </div>

                <!-- Combined Total Banner: Opening vs Closing -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-white/5 border border-white/10 p-4 rounded-2xl backdrop-blur-xs">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Total Day Opening</span>
                        <div class="text-xl sm:text-2xl font-black text-white">
                            ₹{{ number_format($regTot['opening'], 2) }}
                        </div>
                        <span class="text-[10px] text-slate-400">Day start count</span>
                    </div>

                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 block mb-0.5">Day Inflow (+)</span>
                        <div class="text-xl sm:text-2xl font-black text-emerald-400">
                            +₹{{ number_format($regTot['inflow'], 2) }}
                        </div>
                        <span class="text-[10px] text-slate-400">Received today</span>
                    </div>

                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-400 block mb-0.5">Day Outflow (-)</span>
                        <div class="text-xl sm:text-2xl font-black text-rose-400">
                            -₹{{ number_format($regTot['outflow'], 2) }}
                        </div>
                        <span class="text-[10px] text-slate-400">Spent today</span>
                    </div>

                    <div class="rounded-xl bg-gradient-to-r from-indigo-600/40 to-sky-600/40 border border-indigo-400/30 p-2.5 sm:-m-1">
                        <div class="flex items-center justify-between mb-0.5">
                            <span class="text-[10px] font-black uppercase tracking-wider text-indigo-200">
                                {{ $isTodayDate ? 'Today Current Closing' : 'End of Day Closing' }}
                            </span>
                            @if($regTot['adjustment'] != 0)
                                <span class="text-[10px] font-bold {{ $regTot['adjustment'] < 0 ? 'text-rose-300' : 'text-amber-300' }}">
                                    Adj: {{ $regTot['adjustment'] > 0 ? '+' : '' }}₹{{ number_format($regTot['adjustment'], 2) }}
                                </span>
                            @endif
                        </div>
                        <div class="text-2xl sm:text-3xl font-black {{ $regTot['closing'] < 0 ? 'text-rose-400' : 'text-white' }}">
                            ₹{{ number_format($regTot['closing'], 2) }}
                        </div>
                        <div class="text-[10px] text-indigo-200 mt-0.5 flex items-center justify-between">
                            <span>Net: {{ $regTot['net_flow'] >= 0 ? '+' : '' }}₹{{ number_format($regTot['net_flow'], 2) }}</span>
                            <span class="font-bold">Total Liquid</span>
                        </div>
                    </div>
                </div>

                <!-- Category-Wise Breakdown in Same Card -->
                <div>
                    <div class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2.5 flex items-center justify-between">
                        <span>Payment Category Balances</span>
                        <span class="text-[10px] font-normal text-slate-400">Opening & Closing per mode</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach($regCats as $catName => $cData)
                            @php
                                $modeIcons = ['Cash' => '💵', 'UPI' => '📱', 'Card' => '💳', 'Bank Transfer' => '🏦', 'Other' => '💰'];
                                $icon = $modeIcons[$catName] ?? '💳';
                            @endphp
                            <div class="p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 transition flex flex-col justify-between space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-white flex items-center gap-1.5">
                                        <span>{{ $icon }}</span>
                                        <span>{{ $catName }}</span>
                                    </span>
                                    @if($cData['adjustment'] != 0)
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md {{ $cData['adjustment'] < 0 ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}" title="{{ $cData['adjustment_note'] ?? 'Adjustment' }}">
                                            {{ $cData['adjustment'] > 0 ? '+' : '' }}₹{{ number_format($cData['adjustment'], 0) }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Opening vs Closing in category -->
                                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-white/10 text-xs">
                                    <div>
                                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Opening</span>
                                        <span class="text-sm font-bold text-slate-200">
                                            ₹{{ number_format($cData['opening'], 2) }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[10px] uppercase font-bold text-indigo-300 block">Closing</span>
                                        <span class="text-base font-black {{ $cData['closing'] < 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                                            ₹{{ number_format($cData['closing'], 2) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Inflow / Outflow micro stats -->
                                <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1 border-t border-white/5">
                                    <span class="text-emerald-400 font-semibold">+₹{{ number_format($cData['inflow'], 0) }} in</span>
                                    <span class="text-rose-400 font-semibold">-₹{{ number_format($cData['outflow'], 0) }} out</span>
                                    <span class="{{ $cData['net_flow'] >= 0 ? 'text-emerald-300' : 'text-rose-300' }} font-bold">
                                        {{ $cData['net_flow'] >= 0 ? '+' : '' }}₹{{ number_format($cData['net_flow'], 0) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- 2.7 WEEKLY & MONTHLY FINANCIAL OVERVIEW -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            <!-- Weekly Financial Overview -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-sky-50 dark:bg-sky-950/60 border border-sky-200/60 dark:border-sky-800 flex items-center justify-center text-lg shrink-0">
                        📊
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5 flex-wrap">
                            <span>Weekly Total</span>
                            <span class="text-[10px] font-normal text-slate-400">({{ \Carbon\Carbon::parse($startOfWeek)->format('d M') }} – {{ \Carbon\Carbon::parse($endOfWeek)->format('d M') }})</span>
                        </div>
                        <div class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                            <span>Net:</span>
                            <strong class="{{ ($weeklyIncomeTotal - $weeklyExpensesTotal) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                ₹{{ number_format($weeklyIncomeTotal - $weeklyExpensesTotal, 2) }}
                            </strong>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4 text-xs w-full sm:w-auto justify-between sm:justify-end pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="text-[10px] uppercase font-semibold text-slate-400 block">Expenses</span>
                        <span class="text-sm font-bold text-rose-600 dark:text-rose-400">₹{{ number_format($weeklyExpensesTotal, 2) }}</span>
                    </div>
                    <div class="w-px h-7 bg-slate-200 dark:bg-slate-800"></div>
                    <div>
                        <span class="text-[10px] uppercase font-semibold text-slate-400 block">Income</span>
                        <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">₹{{ number_format($weeklyIncomeTotal, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Monthly Financial Overview -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between gap-3">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200/60 dark:border-indigo-800 flex items-center justify-center text-lg shrink-0">
                            🗓️
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5 flex-wrap">
                                <span>Monthly Total</span>
                                <span class="text-[10px] font-normal text-slate-400">({{ \Carbon\Carbon::parse($startOfMonth)->format('F Y') }})</span>
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                                <span>Net:</span>
                                <strong class="{{ ($monthlyIncomeTotal - $monthlyExpensesTotal) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    ₹{{ number_format($monthlyIncomeTotal - $monthlyExpensesTotal, 2) }}
                                </strong>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-xs w-full sm:w-auto justify-between sm:justify-end pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                        <div>
                            <span class="text-[10px] uppercase font-semibold text-slate-400 block">Total Spend</span>
                            <span class="text-sm font-bold text-rose-600 dark:text-rose-400">₹{{ number_format($monthlyExpensesTotal, 2) }}</span>
                        </div>
                        <div class="w-px h-7 bg-slate-200 dark:bg-slate-800"></div>
                        <div>
                            <span class="text-[10px] uppercase font-semibold text-slate-400 block">Income</span>
                            <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">₹{{ number_format($monthlyIncomeTotal, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Monthly Spend Clarification & Breakdown Controls -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-[11px]">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-medium">Base: ₹{{ number_format($monthlyBaseExpenses, 2) }}</span>
                        @if($monthlyVoluntaryExpenses > 0)
                            <span class="px-2 py-0.5 rounded-md bg-pink-50 dark:bg-pink-950/50 text-pink-700 dark:text-pink-300 font-semibold" title="Discretionary / Voluntary">Voluntary: +₹{{ number_format($monthlyVoluntaryExpenses, 2) }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="document.getElementById('monthly-breakdown-modal').classList.toggle('hidden')" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 cursor-pointer">
                            <span>📊</span> Category & Payment Breakdown
                        </button>
                        <a href="{{ route('expenses.index', ['period' => 'month']) }}" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-semibold">
                            Open in Expenses &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2.8 SAVINGS, INCOME TALLY SURPLUS & DAILY CASH REGISTER -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            <!-- Savings Card -->
            <div class="p-4 rounded-3xl bg-gradient-to-br from-emerald-50 via-teal-50/40 to-white dark:from-emerald-950/40 dark:via-slate-900 dark:to-slate-900 border border-emerald-200/80 dark:border-emerald-800/60 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-950 dark:text-emerald-300 uppercase tracking-wider flex items-center gap-1.5">
                            <span>🏦</span> Total Savings Fund
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-200/80 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200">
                            Available
                        </span>
                    </div>
                    <div class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-2">
                        ₹{{ number_format($totalSavingsAvailable, 2) }}
                    </div>
                    <p class="text-[11px] text-emerald-800/80 dark:text-emerald-400/80 mt-0.5">
                        Net reserves allocated across all savings goals
                    </p>
                </div>
                <div class="pt-3 mt-3 border-t border-emerald-100 dark:border-emerald-900/50">
                    <a href="{{ route('savings.index') }}" class="text-xs font-bold text-emerald-700 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-200 flex items-center justify-between">
                        <span>Manage Savings & Funds</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Income Surplus Card -->
            <div class="p-4 rounded-3xl bg-gradient-to-br from-indigo-50 via-sky-50/40 to-white dark:from-indigo-950/40 dark:via-slate-900 dark:to-slate-900 border border-indigo-200/80 dark:border-indigo-800/60 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-indigo-950 dark:text-indigo-300 uppercase tracking-wider flex items-center gap-1.5">
                            <span>📈</span> Income Surplus
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-200/80 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                            Untallied
                        </span>
                    </div>
                    <div class="text-2xl font-black text-indigo-700 dark:text-indigo-400 mt-2">
                        ₹{{ number_format($totalIncomeSurplus, 2) }}
                    </div>
                    <p class="text-[11px] text-indigo-800/80 dark:text-indigo-400/80 mt-0.5">
                        Income remaining after all expense tallies
                    </p>
                </div>
                <div class="pt-3 mt-3 border-t border-indigo-100 dark:border-indigo-900/50">
                    <a href="{{ route('income.index') }}" class="text-xs font-bold text-indigo-700 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200 flex items-center justify-between">
                        <span>Income & Expense Tally</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Daily Cash Register Card -->
            <div class="p-4 rounded-3xl bg-gradient-to-br from-amber-50 via-orange-50/40 to-white dark:from-amber-950/40 dark:via-slate-900 dark:to-slate-900 border border-amber-200/80 dark:border-amber-800/60 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-950 dark:text-amber-300 uppercase tracking-wider flex items-center gap-1.5">
                            <span>🧾</span> Today's Closing Cash
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-200/80 dark:bg-amber-900 text-amber-800 dark:text-amber-200">
                            Register
                        </span>
                    </div>
                    <div class="text-2xl font-black text-amber-700 dark:text-amber-400 mt-2">
                        ₹{{ number_format($todayDailyBalance?->closing_balance ?? $currentBalance, 2) }}
                    </div>
                    <p class="text-[11px] text-amber-800/80 dark:text-amber-400/80 mt-0.5">
                        Rolls forward automatically into tomorrow's opening
                    </p>
                </div>
                <div class="pt-3 mt-3 border-t border-amber-100 dark:border-amber-900/50">
                    <a href="{{ route('daily-balances.index') }}" class="text-xs font-bold text-amber-700 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-200 flex items-center justify-between">
                        <span>Daily Balance & Roll-Forward</span>
                        <span>&rarr;</span>
                    </a>
                </div>
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
                <div>
                    <div class="flex items-center justify-between text-emerald-500 mb-2">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Received</span>
                        <span class="text-lg">💵</span>
                    </div>
                    <div class="text-xl sm:text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        ₹{{ number_format($moneyReceived, 0) }}
                    </div>
                    <span class="text-[11px] text-slate-500 mt-1 block">{{ ucfirst($period) }}'s Income</span>
                </div>
                <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800/80 text-[10px] space-y-0.5 text-slate-500 dark:text-slate-400">
                    <div class="flex items-center justify-between">
                        <span>Wk:</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">₹{{ number_format($weeklyIncomeTotal, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Mo:</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">₹{{ number_format($monthlyIncomeTotal, 0) }}</span>
                    </div>
                </div>
            </div>

            <!-- Expenses -->
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between text-rose-500 mb-2">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Expenses</span>
                        <span class="text-lg">💰</span>
                    </div>
                    <div class="text-xl sm:text-2xl font-bold text-rose-600 dark:text-rose-400">
                        ₹{{ number_format($expensesTotal, 0) }}
                    </div>
                    <span class="text-[11px] text-slate-500 mt-1 block">Spent {{ $period === 'today' ? 'today' : ($period === 'week' ? 'this week' : 'this month') }}</span>
                </div>
                <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800/80 text-[10px] space-y-0.5 text-slate-500 dark:text-slate-400">
                    <div class="flex items-center justify-between">
                        <span>Wk:</span>
                        <span class="font-bold text-rose-600 dark:text-rose-400">₹{{ number_format($weeklyExpensesTotal, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Mo:</span>
                        <span class="font-bold text-rose-600 dark:text-rose-400">₹{{ number_format($monthlyExpensesTotal, 0) }}</span>
                    </div>
                </div>
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
                <!-- Dynamic Spending Trend Chart -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                        <div>
                            <h2 class="font-bold text-sm text-slate-900 dark:text-white" id="chart-title">7-Day Spending</h2>
                            <p class="text-xs text-slate-500" id="chart-sub">Daily expenses breakdown · click a bar for categories</p>
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl text-[11px] font-bold">
                                <button type="button" onclick="switchChartRange('7d', this)" class="chart-range-btn px-2.5 py-1 rounded-lg bg-white dark:bg-slate-900 shadow-xs text-indigo-600 dark:text-sky-400">7D</button>
                                <button type="button" onclick="switchChartRange('week', this)" class="chart-range-btn px-2.5 py-1 rounded-lg text-slate-600 dark:text-slate-400 hover:text-slate-900">Week</button>
                                <button type="button" onclick="switchChartRange('month', this)" class="chart-range-btn px-2.5 py-1 rounded-lg text-slate-600 dark:text-slate-400 hover:text-slate-900">Month</button>
                                <button type="button" onclick="switchChartRange('year', this)" class="chart-range-btn px-2.5 py-1 rounded-lg text-slate-600 dark:text-slate-400 hover:text-slate-900">Year</button>
                            </div>

                            <!-- Custom Month Selector for Chart -->
                            <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl text-[11px]">
                                <input type="month" id="chart-month-input" value="{{ $selectedMonth ?? now()->format('Y-m') }}" onchange="applyCustomMonthChart(this.value)" class="px-2 py-0.5 rounded-lg border-0 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200 cursor-pointer shadow-2xs" title="Select custom month for chart">
                            </div>

                            <!-- Custom Date Range Button -->
                            <button type="button" onclick="document.getElementById('chart-custom-range-box').classList.toggle('hidden')" class="chart-range-btn px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-[11px] font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs flex items-center gap-1 cursor-pointer">
                                <span>📅</span> Range
                            </button>
                        </div>
                    </div>

                    <!-- Custom Date Range Filter for Chart -->
                    <div id="chart-custom-range-box" class="hidden mb-4 p-3 bg-slate-50 dark:bg-slate-800/80 rounded-2xl border border-slate-200 dark:border-slate-700 flex flex-wrap items-center gap-2 text-xs">
                        <div class="flex items-center gap-1">
                            <label class="font-bold text-slate-500">From:</label>
                            <input type="date" id="chart-range-start" value="{{ now()->subDays(14)->toDateString() }}" class="px-2.5 py-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-xs font-bold text-slate-800 dark:text-white">
                        </div>
                        <div class="flex items-center gap-1">
                            <label class="font-bold text-slate-500">To:</label>
                            <input type="date" id="chart-range-end" value="{{ now()->toDateString() }}" class="px-2.5 py-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-xs font-bold text-slate-800 dark:text-white">
                        </div>
                        <button type="button" onclick="applyCustomRangeChart()" class="px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-xs transition cursor-pointer">
                            Apply
                        </button>
                    </div>
                    <div class="h-52 w-full">
                        <canvas id="weeklyExpenseChart" class="cursor-pointer"></canvas>
                    </div>
                </div>

                <!-- Savings & Income Tally Snapshot Card -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="p-4 rounded-3xl bg-emerald-50/70 border border-emerald-100 shadow-sm space-y-1">
                        <div class="flex items-center justify-between text-emerald-800">
                            <span class="text-[11px] font-bold uppercase tracking-wider">Net Savings Pool</span>
                            <span class="text-base">💰</span>
                        </div>
                        <div class="text-xl font-black text-emerald-900">₹{{ number_format($totalSavingsAvailable, 2) }}</div>
                        <a href="{{ route('savings.index') }}" class="text-[11px] font-bold text-emerald-700 hover:underline block pt-1">Manage Savings &rarr;</a>
                    </div>
                    <div class="p-4 rounded-3xl bg-indigo-50/70 border border-indigo-100 shadow-sm space-y-1">
                        <div class="flex items-center justify-between text-indigo-800">
                            <span class="text-[11px] font-bold uppercase tracking-wider">Income Tallying</span>
                            <span class="text-base">🧾</span>
                        </div>
                        <div class="text-xl font-black text-indigo-900">₹{{ number_format($totalIncomeTallied, 2) }}</div>
                        <span class="text-[11px] text-slate-500 block">₹{{ number_format($totalIncomeSurplus, 2) }} untallied surplus</span>
                        <a href="{{ route('income.index') }}" class="text-[11px] font-bold text-indigo-700 hover:underline block">View Tallies &rarr;</a>
                    </div>
                </div>

                <!-- Navigation Shortcuts Card -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                    <h2 class="font-bold text-sm text-slate-900 dark:text-white">Quick Access</h2>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <a href="{{ route('daily-balances.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 flex items-center gap-2">
                            <span>📅</span> Cash Register
                        </a>
                        <a href="{{ route('savings.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 flex items-center gap-2">
                            <span>💰</span> Savings
                        </a>
                        <a href="{{ route('petrol.statement') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 flex items-center gap-2">
                            <span>⛽</span> Petrol Statement
                        </a>
                        <a href="{{ route('friends.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 flex items-center gap-2">
                            <span>👥</span> Friend Debts
                        </a>
                        <a href="{{ route('payments.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 flex items-center gap-2">
                            <span>✅</span> Reconcile
                        </a>
                        <a href="{{ route('petrol.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 flex items-center gap-2">
                            <span>⛽</span> Petrol Log
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Chart.js Init & Dynamic Switcher Script -->
    <script>
        let expenseChartInstance = null;
        let expenseChartRanges = [];

        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('weeklyExpenseChart');
            if (!ctx) return;

            const labels = @json($last7Days);
            const data = @json($expenseChartData);
            expenseChartRanges = @json($expenseChartRanges);

            expenseChartInstance = new Chart(ctx, {
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
                        legend: { display: false },
                        tooltip: { callbacks: { footer: () => 'Click to see categories' } }
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
                    },
                    onClick: (evt, elements) => {
                        if (!elements.length) return;
                        const bucket = expenseChartRanges[elements[0].index];
                        if (bucket) openChartBreakdown(bucket.start, bucket.end);
                    }
                }
            });
        });

        let chartBreakdownItems = [];

        async function openChartBreakdown(start, end) {
            const modal = document.getElementById('chart-breakdown-modal');
            const categoriesEl = document.getElementById('chart-breakdown-categories');
            document.getElementById('chart-breakdown-title').textContent = 'Loading…';
            document.getElementById('chart-breakdown-total').textContent = '';
            categoriesEl.innerHTML = '<div class="py-6 text-center text-xs text-slate-400">Loading categories…</div>';
            document.getElementById('chart-breakdown-items').innerHTML = '';
            modal.classList.remove('hidden');

            try {
                const res = await fetch(`{{ route('dashboard.chart-breakdown') }}?start=${start}&end=${end}`, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Request failed');
                const data = await res.json();
                chartBreakdownItems = data.expenses || [];

                document.getElementById('chart-breakdown-title').textContent = data.title;
                document.getElementById('chart-breakdown-total').textContent = '₹' + Number(data.total).toLocaleString('en-IN', { minimumFractionDigits: 2 });

                if (!data.categories.length) {
                    categoriesEl.innerHTML = '<div class="py-6 text-center text-xs text-slate-400">No expenses recorded for this period.</div>';
                    return;
                }

                categoriesEl.innerHTML = data.categories.map((cat, i) => `
                    <button type="button" data-category-index="${i}" class="chart-breakdown-cat w-full p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 hover:border-indigo-300 flex items-center justify-between text-xs cursor-pointer transition">
                        <span class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color:${escapeChartHtml(cat.color)}"></span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200 truncate">${escapeChartHtml(cat.name)}</span>
                            <span class="text-[10px] text-slate-400">(${cat.count}x)</span>
                        </span>
                        <span class="text-right shrink-0">
                            <span class="font-bold text-slate-900 dark:text-white">₹${Number(cat.total).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                            <span class="block text-[10px] text-slate-400">${cat.percentage}%</span>
                        </span>
                    </button>
                `).join('');

                categoriesEl.querySelectorAll('.chart-breakdown-cat').forEach(btn => {
                    btn.addEventListener('click', () => {
                        categoriesEl.querySelectorAll('.chart-breakdown-cat').forEach(b => b.classList.remove('ring-2', 'ring-indigo-500'));
                        btn.classList.add('ring-2', 'ring-indigo-500');
                        renderChartBreakdownItems(data.categories[btn.dataset.categoryIndex].name);
                    });
                });
                renderChartBreakdownItems(null);
            } catch (e) {
                categoriesEl.innerHTML = '<div class="py-6 text-center text-xs text-rose-500">Unable to load the breakdown. Please try again.</div>';
            }
        }

        function renderChartBreakdownItems(categoryName) {
            const items = categoryName ? chartBreakdownItems.filter(item => item.category === categoryName) : chartBreakdownItems;
            document.getElementById('chart-breakdown-items-title').textContent = categoryName ? `${categoryName} items` : 'All items';
            document.getElementById('chart-breakdown-items').innerHTML = items.map(item => `
                <a href="${item.url}" class="py-2 flex items-center justify-between gap-3 text-xs hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-lg px-2">
                    <span class="min-w-0">
                        <span class="block font-semibold text-slate-800 dark:text-slate-200 truncate">${escapeChartHtml(item.description)}</span>
                        <span class="block text-[10px] text-slate-400">${escapeChartHtml(item.category)} · ${escapeChartHtml(item.date)}</span>
                    </span>
                    <span class="font-bold text-slate-900 dark:text-white shrink-0">₹${Number(item.amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                </a>
            `).join('') || '<div class="py-4 text-center text-xs text-slate-400">No items.</div>';
        }

        function closeChartBreakdown() {
            document.getElementById('chart-breakdown-modal').classList.add('hidden');
        }

        function escapeChartHtml(value) {
            return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function switchChartRange(range, btn, queryParams = '') {
            document.querySelectorAll('.chart-range-btn').forEach(b => {
                b.className = 'chart-range-btn px-2.5 py-1 rounded-lg text-slate-600 dark:text-slate-400 hover:text-slate-900';
            });
            if (btn) {
                btn.className = 'chart-range-btn px-2.5 py-1 rounded-lg bg-white dark:bg-slate-900 shadow-xs text-indigo-600 dark:text-sky-400 font-bold';
            }

            let url = `/dashboard/chart-data?range=${range}` + (queryParams ? `&${queryParams}` : '');

            fetch(url)
                .then(r => r.json())
                .then(res => {
                    if (!expenseChartInstance) return;
                    expenseChartInstance.data.labels = res.labels;
                    expenseChartInstance.data.datasets[0].data = res.data;
                    expenseChartRanges = res.ranges || [];
                    expenseChartInstance.update();

                    document.getElementById('chart-title').textContent = res.title || 'Spending Trend';
                    document.getElementById('chart-sub').textContent = res.sub || `Total: ₹${Number(res.total).toFixed(2)}`;
                });
        }

        function applyCustomMonthChart(monthVal) {
            if (!monthVal) return;
            switchChartRange('month', null, `month=${monthVal}`);
        }

        function applyCustomRangeChart() {
            const start = document.getElementById('chart-range-start').value;
            const end = document.getElementById('chart-range-end').value;
            if (!start || !end) return;
            switchChartRange('custom', null, `start_date=${start}&end_date=${end}`);
        }
    </script>

    <!-- Chart Bar Category Breakdown Modal -->
    <div id="chart-breakdown-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" onclick="if(event.target === this) closeChartBreakdown()">
        <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                        <span>📊</span> <span id="chart-breakdown-title">Spending</span>
                    </h3>
                    <p class="text-xs text-slate-500">Spend by category · <span id="chart-breakdown-total" class="font-bold text-rose-600 dark:text-rose-400"></span></p>
                </div>
                <button type="button" onclick="closeChartBreakdown()" class="text-slate-400 hover:text-slate-600 text-2xl font-bold cursor-pointer">&times;</button>
            </div>
            <div id="chart-breakdown-categories" class="space-y-1.5"></div>
            <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                <h4 id="chart-breakdown-items-title" class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">All items</h4>
                <div id="chart-breakdown-items" class="divide-y divide-slate-100 dark:divide-slate-800 max-h-60 overflow-y-auto"></div>
            </div>
        </div>
    </div>

    <!-- Monthly Breakdown Modal -->
    <div id="monthly-breakdown-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                        <span>🗓️</span> Monthly Expense Breakdown
                    </h3>
                    <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($startOfMonth)->format('F Y') }} spending details</p>
                </div>
                <button type="button" onclick="document.getElementById('monthly-breakdown-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <!-- Total Card -->
            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Monthly Spend</span>
                    <div class="text-xl font-black text-rose-600 dark:text-rose-400">₹{{ number_format($monthlyExpensesTotal, 2) }}</div>
                </div>
                <div class="text-right text-xs space-y-0.5">
                    <div class="text-slate-600 dark:text-slate-300 font-medium">Base Needs: <strong>₹{{ number_format($monthlyBaseExpenses, 2) }}</strong></div>
                    @if($monthlyVoluntaryExpenses > 0)
                        <div class="text-pink-600 dark:text-pink-400 font-semibold">Voluntary / Leisure: <strong>₹{{ number_format($monthlyVoluntaryExpenses, 2) }}</strong></div>
                    @endif
                </div>
            </div>

            <!-- Category Breakdown -->
            <div class="space-y-2">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                    <span>📊</span> Spend by Category
                </h4>
                <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                    @forelse($monthlyCategoryBreakdown as $cat)
                        <div class="p-2.5 rounded-xl bg-slate-50/80 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $cat['color'] }}"></span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $cat['name'] }}</span>
                                <span class="text-[10px] text-slate-400">({{ $cat['count'] }}x)</span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-slate-900 dark:text-white">₹{{ number_format($cat['total'], 2) }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $cat['percentage'] }}%</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-xs text-slate-400">No category records found for this month.</div>
                    @endforelse
                </div>
            </div>

            <!-- Payment Type Breakdown -->
            <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                    <span>💳</span> Spend by Payment Method
                </h4>
                <div class="grid grid-cols-2 gap-2">
                    @forelse($monthlyPaymentBreakdown as $pay)
                        <div class="p-2.5 rounded-xl bg-slate-50/80 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-xs">
                            <div class="flex items-center justify-between text-slate-500 mb-1">
                                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $pay['method'] }}</span>
                                <span class="text-[10px]">{{ $pay['count'] }}x</span>
                            </div>
                            <div class="font-bold text-slate-900 dark:text-white text-sm">₹{{ number_format($pay['total'], 2) }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">{{ $pay['percentage'] }}% of month</div>
                        </div>
                    @empty
                        <div class="col-span-2 text-center py-3 text-xs text-slate-400">No payment records found.</div>
                    @endforelse
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <a href="{{ route('expenses.index', ['period' => 'month']) }}" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition">
                    View in Expenses Table &rarr;
                </a>
                <button type="button" onclick="document.getElementById('monthly-breakdown-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-xs hover:bg-slate-200 transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
</x-app-layout>

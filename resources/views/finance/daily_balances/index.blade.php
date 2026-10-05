<x-app-layout title="Daily Cash Register">
    <div class="space-y-6">

        <!-- 1. HEADER & DATE NAVIGATION -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Daily Cash Register</span>
                        <span class="text-xl">🧾</span>
                    </h1>
                    @if($selectedDayData && $selectedDayData['is_today'])
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500 text-white shadow-xs">
                            Today Active
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Multi-category liquid cashflow tracking. Opening balances roll forward into the next day alone.
                </p>
            </div>

            <!-- Action & Navigation Controls -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Date Picker / Switcher -->
                <div class="flex items-center gap-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-1 rounded-2xl shadow-xs">
                    <a href="{{ route('daily-balances.index', ['date' => $prevDate]) }}" class="px-2.5 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300 transition" title="Previous Day">
                        &larr;
                    </a>
                    <form method="GET" action="{{ route('daily-balances.index') }}" class="inline">
                        <input type="date" name="date" value="{{ $selectedDateStr }}" onchange="this.form.submit()" class="px-2 py-1 rounded-xl text-xs font-bold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-800/80 border-0 focus:ring-1 focus:ring-sky-500">
                    </form>
                    <a href="{{ route('daily-balances.index', ['date' => $todayStr]) }}" class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition {{ $selectedDateStr === $todayStr ? 'bg-sky-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        Today
                    </a>
                    <a href="{{ route('daily-balances.index', ['date' => $nextDate]) }}" class="px-2.5 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300 transition" title="Next Day">
                        &rarr;
                    </a>
                </div>

                <!-- Manage Categories Button -->
                <button type="button" onclick="openCategoriesModal()" class="px-3.5 py-2 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold shadow-xs transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                    <span>⚙️</span>
                    <span>Payment Categories ({{ count($activeCategories) }})</span>
                </button>

                <!-- Adjust Balances Button -->
                <button type="button" onclick="openAdjustmentModal()" class="px-4 py-2 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-md shadow-indigo-600/20 transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                    <span>✍️</span>
                    <span>Set / Adjust Balances</span>
                </button>
            </div>
        </div>

        <!-- 2. TOTAL COMBINED DAILY REGISTER CARD -->
        @if($selectedDayData)
            @php
                $tot = $selectedDayData['total'];
                $isPositive = $tot['closing'] >= 0;
            @endphp
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 shadow-xl border border-slate-700/60">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg bg-white/10 text-white text-xs font-bold tracking-wide uppercase">
                                Combined Register Total
                            </span>
                            <span class="text-xs text-indigo-300 font-semibold">
                                {{ $selectedDate->format('l, F j, Y') }}
                            </span>
                        </div>
                        <div class="flex items-baseline gap-3">
                            <div class="text-3xl sm:text-4xl font-black text-white">
                                ₹{{ number_format($tot['closing'], 2) }}
                            </div>
                            <span class="text-xs text-indigo-200 uppercase font-bold tracking-wider">
                                {{ $selectedDayData['is_today'] ? "Today's Current Closing" : 'Closing Balance' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-300">
                            Combined cashflow sum across all {{ count($activeCategories) }} active payment categories ({{ implode(', ', $activeCategories) }}).
                        </p>
                    </div>

                    <!-- Flow Calculation Equation -->
                    <div class="flex items-center gap-2 sm:gap-3 flex-wrap bg-white/5 border border-white/10 p-3.5 rounded-2xl">
                        <!-- Opening -->
                        <div class="text-center px-2">
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Opening</span>
                            <span class="text-sm sm:text-base font-bold text-white">₹{{ number_format($tot['opening'], 2) }}</span>
                        </div>

                        <span class="text-slate-400 font-bold text-sm">+</span>

                        <!-- Inflow -->
                        <div class="text-center px-2">
                            <span class="text-[10px] font-bold uppercase text-emerald-400 block">Inflow (+)</span>
                            <span class="text-sm sm:text-base font-bold text-emerald-400">₹{{ number_format($tot['inflow'], 2) }}</span>
                        </div>

                        <span class="text-slate-400 font-bold text-sm">-</span>

                        <!-- Outflow -->
                        <div class="text-center px-2">
                            <span class="text-[10px] font-bold uppercase text-rose-400 block">Outflow (-)</span>
                            <span class="text-sm sm:text-base font-bold text-rose-400">₹{{ number_format($tot['outflow'], 2) }}</span>
                        </div>

                        @if($tot['adjustment'] != 0)
                            <span class="text-slate-400 font-bold text-sm">±</span>
                            <!-- Adjustment -->
                            <div class="text-center px-2">
                                <span class="text-[10px] font-bold uppercase text-amber-400 block">Adjustment</span>
                                <span class="text-sm sm:text-base font-bold text-amber-400">{{ ($tot['adjustment'] > 0 ? '+' : '').'₹'.number_format($tot['adjustment'], 2) }}</span>
                            </div>
                        @endif

                        <span class="text-slate-400 font-bold text-sm">=</span>

                        <!-- Closing -->
                        <div class="text-center px-2 bg-white/10 py-1 rounded-xl">
                            <span class="text-[10px] font-bold uppercase text-indigo-200 block">Closing</span>
                            <span class="text-sm sm:text-base font-black text-white">₹{{ number_format($tot['closing'], 2) }}</span>
                        </div>
                    </div>
                </div>

                @if($selectedDayData['notes'])
                    <div class="mt-4 pt-3 border-t border-white/10 flex items-center gap-2 text-xs text-indigo-200">
                        <span class="font-bold text-white">Day Note:</span>
                        <span>{{ $selectedDayData['notes'] }}</span>
                    </div>
                @endif
            </div>
        @endif

        <!-- 3. PER-CATEGORY BREAKDOWN CARDS (Cash, UPI, Card, etc.) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ min(count($activeCategories), 4) }} gap-4">
            @foreach($activeCategories as $catName)
                @php
                    $cat = $selectedDayData['categories'][$catName] ?? [
                        'opening' => 0.0,
                        'inflow' => 0.0,
                        'outflow' => 0.0,
                        'adjustment' => 0.0,
                        'closing' => 0.0,
                        'is_opening_manual' => false,
                        'is_closing_manual' => false,
                    ];

                    $isCash = strtolower($catName) === 'cash';
                    $isUpi = strtolower($catName) === 'upi';
                    $isCard = strtolower($catName) === 'card';

                    $cardBg = $isCash
                        ? 'from-emerald-50 via-teal-50/40 to-white dark:from-emerald-950/40 dark:via-slate-900 dark:to-slate-900 border-emerald-200/80 dark:border-emerald-800/60'
                        : ($isUpi
                            ? 'from-sky-50 via-indigo-50/40 to-white dark:from-sky-950/40 dark:via-slate-900 dark:to-slate-900 border-sky-200/80 dark:border-sky-800/60'
                            : 'from-purple-50 via-slate-50 to-white dark:from-purple-950/40 dark:via-slate-900 dark:to-slate-900 border-purple-200/80 dark:border-purple-800/60');

                    $icon = $isCash ? '💵' : ($isUpi ? '📱' : ($isCard ? '💳' : '🏦'));
                    $accentColor = $isCash ? 'text-emerald-700 dark:text-emerald-400' : ($isUpi ? 'text-sky-700 dark:text-sky-400' : 'text-purple-700 dark:text-purple-400');
                @endphp

                <div class="p-5 rounded-3xl bg-gradient-to-br {{ $cardBg }} border shadow-sm flex flex-col justify-between space-y-4">
                    <!-- Card Top Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">{{ $icon }}</span>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $catName }} Register</h3>
                                <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">Liquid Payment Category</span>
                            </div>
                        </div>
                        @if($cat['is_closing_manual'])
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200">
                                Adjusted
                            </span>
                        @endif
                    </div>

                    <!-- Closing Amount Highlight -->
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Closing / Current Balance</span>
                        <div class="text-2xl sm:text-3xl font-black {{ $accentColor }} mt-0.5">
                            ₹{{ number_format($cat['closing'], 2) }}
                        </div>
                    </div>

                    <!-- Micro Flow Math -->
                    <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-200/60 dark:border-slate-800/60 text-xs">
                        <div>
                            <span class="text-[10px] font-semibold text-slate-400 block uppercase">Opening</span>
                            <span class="font-bold text-slate-700 dark:text-slate-200">₹{{ number_format($cat['opening'], 2) }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-semibold text-emerald-600 block uppercase">In (+)</span>
                            <span class="font-bold text-emerald-600">₹{{ number_format($cat['inflow'], 2) }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-semibold text-rose-600 block uppercase">Out (-)</span>
                            <span class="font-bold text-rose-600">₹{{ number_format($cat['outflow'], 2) }}</span>
                        </div>
                    </div>

                    @if($cat['adjustment'] != 0)
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px]">
                            <span class="text-slate-400">Manual Adjustment:</span>
                            <span class="font-bold text-amber-600">{{ ($cat['adjustment'] > 0 ? '+' : '').'₹'.number_format($cat['adjustment'], 2) }}</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- 4. TOMORROW'S ROLLOVER PREVIEW (ONLY SHOWN FOR NEXT DAY ALONE, NOT FULL MONTH) -->
        @if($tomorrowPreview && $selectedDayData && $selectedDayData['is_today'])
            <div class="p-4 rounded-3xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/60 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-100 dark:bg-amber-900/60 flex items-center justify-center text-lg shrink-0">
                        🌅
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-amber-900 dark:text-amber-200">Tomorrow's Projected Opening ({{ $tomorrowPreview['date']->format('l, d M') }})</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-amber-200/80 dark:bg-amber-900 text-amber-900 dark:text-amber-200">Next Day Alone</span>
                        </div>
                        <p class="text-[11px] text-amber-800/80 dark:text-amber-300/80 mt-0.5">
                            Today's current closing rolls automatically into tomorrow's starting balance at midnight.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4 text-xs font-bold text-slate-800 dark:text-slate-200 flex-wrap">
                    @foreach($activeCategories as $catName)
                        <div class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-amber-200/60 dark:border-slate-800">
                            <span class="text-[10px] uppercase text-slate-400 font-semibold block">{{ $catName }} Opening</span>
                            <span class="text-sm font-black text-amber-900 dark:text-amber-300">₹{{ number_format($tomorrowPreview['categories'][$catName] ?? 0, 2) }}</span>
                        </div>
                    @endforeach

                    <div class="px-3 py-1.5 rounded-xl bg-amber-500 text-white shadow-xs">
                        <span class="text-[10px] uppercase text-amber-100 font-semibold block">Total Opening</span>
                        <span class="text-sm font-black">₹{{ number_format($tomorrowPreview['total'], 2) }}</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- 5. DETAILED TRANSACTIONS ON SELECTED DATE -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Inflows on Selected Day -->
            <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                        <span>💵</span> Money Received ({{ $selectedDayIncomes->count() }})
                    </h3>
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                        +₹{{ number_format($selectedDayIncomes->sum('amount'), 2) }}
                    </span>
                </div>

                @if($selectedDayIncomes->isEmpty())
                    <div class="py-8 text-center text-xs text-slate-400">
                        No incomes recorded on {{ $selectedDate->format('d M Y') }}.
                    </div>
                @else
                    <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                        @foreach($selectedDayIncomes as $inc)
                            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $inc->source ?? 'Income' }}</div>
                                    <div class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold">{{ $inc->payment_method ?: 'Cash' }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $inc->category->name ?? 'General' }}</span>
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">+₹{{ number_format($inc->amount, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Outflows on Selected Day -->
            <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                        <span>💰</span> Expenses Spent ({{ $selectedDayExpenses->count() }})
                    </h3>
                    <span class="text-xs font-bold text-rose-600 dark:text-rose-400">
                        -₹{{ number_format($selectedDayExpenses->sum(fn ($e) => $e->amount + $e->gst_amount), 2) }}
                    </span>
                </div>

                @if($selectedDayExpenses->isEmpty())
                    <div class="py-8 text-center text-xs text-slate-400">
                        No expenses recorded on {{ $selectedDate->format('d M Y') }}.
                    </div>
                @else
                    <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                        @foreach($selectedDayExpenses as $exp)
                            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $exp->description ?: 'Expense' }}</div>
                                    <div class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold">{{ $exp->payment_method ?: 'Cash' }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $exp->category->name ?? 'General' }}</span>
                                        @if($exp->time)
                                            <span>&bull;</span>
                                            <span>{{ \Carbon\Carbon::parse($exp->time)->format('g:i A') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-rose-600 dark:text-rose-400">-₹{{ number_format($exp->amount + $exp->gst_amount, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- 6. HISTORICAL LOG (PAST DAYS UP TO TODAY - NO FUTURE MONTH PROJECTIONS) -->
        <div class="rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden space-y-0">
            <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                        <span>🗓️</span> Historical Cash Register ({{ Carbon\Carbon::parse($month.'-01')->format('F Y') }})
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Historical records up to today. Future dates are hidden until each day arrives.
                    </p>
                </div>
                <form method="GET" action="{{ route('daily-balances.index') }}" class="flex items-center gap-2">
                    <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-200 shadow-xs">
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Opening</th>
                            <th class="px-4 py-3 text-emerald-700 dark:text-emerald-400">Inflow (+)</th>
                            <th class="px-4 py-3 text-rose-700 dark:text-rose-400">Outflow (-)</th>
                            @foreach($activeCategories as $cat)
                                <th class="px-4 py-3">{{ $cat }} Flow</th>
                            @endforeach
                            <th class="px-4 py-3 text-amber-700 dark:text-amber-400">Adjustment</th>
                            <th class="px-4 py-3 font-black text-slate-900 dark:text-white">Closing / Current</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-semibold">
                        @forelse($days as $d)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition {{ $d['is_selected'] ? 'bg-sky-50/60 dark:bg-sky-950/30' : ($d['is_today'] ? 'bg-indigo-50/40 dark:bg-indigo-950/30 font-bold' : '') }}">
                                <!-- Date -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('daily-balances.index', ['date' => $d['date_str']]) }}" class="hover:underline font-bold text-slate-900 dark:text-white">
                                            {{ $d['date']->format('d M (D)') }}
                                        </a>
                                        @if($d['is_today'])
                                            <span class="rounded bg-indigo-600 px-1.5 py-0.5 text-[9px] font-black text-white">TODAY</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Total Opening -->
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                    ₹{{ number_format($d['total']['opening'], 2) }}
                                </td>

                                <!-- Total Inflow -->
                                <td class="px-4 py-3 text-emerald-600 dark:text-emerald-400">
                                    {{ $d['total']['inflow'] > 0 ? '+₹'.number_format($d['total']['inflow'], 2) : '—' }}
                                </td>

                                <!-- Total Outflow -->
                                <td class="px-4 py-3 text-rose-600 dark:text-rose-400">
                                    {{ $d['total']['outflow'] > 0 ? '-₹'.number_format($d['total']['outflow'], 2) : '—' }}
                                </td>

                                <!-- Category Columns -->
                                @foreach($activeCategories as $cat)
                                    @php
                                        $catData = $d['categories'][$cat] ?? ['closing' => 0.0, 'opening' => 0.0];
                                    @endphp
                                    <td class="px-4 py-3 text-[11px] text-slate-500 dark:text-slate-400">
                                        <div class="flex items-center gap-1">
                                            <span class="font-bold text-slate-700 dark:text-slate-200">₹{{ number_format($catData['closing'], 2) }}</span>
                                        </div>
                                    </td>
                                @endforeach

                                <!-- Adjustment -->
                                <td class="px-4 py-3 text-amber-600 dark:text-amber-400">
                                    {{ $d['total']['adjustment'] != 0 ? ($d['total']['adjustment'] > 0 ? '+' : '').'₹'.number_format($d['total']['adjustment'], 2) : '—' }}
                                </td>

                                <!-- Closing -->
                                <td class="px-4 py-3 font-black {{ $d['total']['closing'] < 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white' }}">
                                    ₹{{ number_format($d['total']['closing'], 2) }}
                                </td>

                                <!-- Action -->
                                <td class="px-4 py-3 text-right">
                                    <button type="button" onclick="openAdjustmentModalWithData('{{ $d['date_str'] }}', {{ json_encode($d['categories']) }}, '{{ addslashes($d['notes'] ?? '') }}')" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1 text-[11px] font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-2xs">
                                        Adjust
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 6 + count($activeCategories) }}" class="px-4 py-8 text-center text-xs text-slate-400">
                                    No records available for this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL 1: SET / ADJUST DAILY BALANCES -->
    <div id="adjustment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="w-full max-w-lg rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>✍️</span> Set / Adjust Daily Cash Register
                    </h3>
                    <p class="text-xs text-slate-500" id="adj-modal-date-label">Date: {{ $selectedDateStr }}</p>
                </div>
                <button type="button" onclick="closeAdjustmentModal()" class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 text-lg font-bold">&times;</button>
            </div>

            <form action="{{ route('daily-balances.update') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="record_date" id="adj_modal_date" value="{{ $selectedDateStr }}">

                <!-- Category Breakdown Form Fields -->
                <div class="space-y-3">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                        Category Breakdown Adjustments
                    </p>

                    @foreach($activeCategories as $cat)
                        @php
                            $catInit = $selectedDayData['categories'][$cat] ?? ['opening' => 0, 'adjustment' => 0, 'closing' => 0];
                        @endphp
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <span>{{ strtolower($cat) === 'cash' ? '💵' : (strtolower($cat) === 'upi' ? '📱' : '💳') }}</span>
                                    <span>{{ $cat }}</span>
                                </span>
                                <span class="text-[10px] text-slate-400">Opening, manual change or closing count</span>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Opening (₹)</label>
                                    <input type="number" step="0.01" name="categories[{{ $cat }}][opening]" id="modal_{{ $cat }}_opening" value="{{ $catInit['opening'] }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2.5 py-1.5 text-xs font-bold text-slate-800 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-amber-600 mb-1">Adjustment (₹)</label>
                                    <input type="number" step="0.01" name="categories[{{ $cat }}][adjustment]" id="modal_{{ $cat }}_adjustment" value="{{ $catInit['adjustment'] }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2.5 py-1.5 text-xs font-bold text-amber-600">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-indigo-600 dark:text-indigo-400 mb-1">Closing (₹)</label>
                                    <input type="number" step="0.01" name="categories[{{ $cat }}][closing]" id="modal_{{ $cat }}_closing" value="{{ $catInit['closing'] }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2.5 py-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Notes -->
                <div>
                    <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Day Notes / Tally Remarks</label>
                    <input type="text" name="notes" id="adj_modal_notes" value="{{ $selectedDayData['notes'] ?? '' }}" placeholder="Reason for adjustments, wallet reconcile notes..." class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeAdjustmentModal()" class="rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 font-bold text-white shadow-md hover:bg-indigo-700">Save Balances</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: MANAGE ACTIVE PAYMENT CATEGORIES -->
    <div id="categories-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>⚙️</span> Tracked Payment Categories
                    </h3>
                    <p class="text-xs text-slate-500">Choose which payment categories are tracked in your Daily Cash Register.</p>
                </div>
                <button type="button" onclick="closeCategoriesModal()" class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 text-lg font-bold">&times;</button>
            </div>

            <form action="{{ route('daily-balances.categories') }}" method="POST" class="space-y-4 text-xs">
                @csrf

                <div class="space-y-2">
                    @foreach($allPaymentMethods as $method)
                        @php
                            $methodName = $method['name'];
                            $isChecked = in_array($methodName, $activeCategories, true);
                            $icon = strtolower($methodName) === 'cash' ? '💵' : (strtolower($methodName) === 'upi' ? '📱' : (strtolower($methodName) === 'card' ? '💳' : '🏦'));
                        @endphp
                        <label class="p-3 rounded-2xl border border-slate-200 dark:border-slate-700 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer transition">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">{{ $icon }}</span>
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-white block">{{ $methodName }}</span>
                                    <span class="text-[10px] text-slate-400">{{ in_array($methodName, ['Cash', 'UPI']) ? 'Primary Daily Liquid Mode' : 'Additional Wallet Mode' }}</span>
                                </div>
                            </div>
                            <input type="checkbox" name="categories[]" value="{{ $methodName }}" {{ $isChecked ? 'checked' : '' }} class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500 border-slate-300">
                        </label>
                    @endforeach
                </div>

                <p class="text-[11px] text-slate-400">
                    💡 You can enable Card or other modes at any time. Unchecked modes remain in Finance Hub but are excluded from the daily cash register equation.
                </p>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeCategoriesModal()" class="rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="rounded-xl bg-sky-600 px-5 py-2 font-bold text-white shadow-md hover:bg-sky-500">Save Categories</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT FOR MODALS -->
    <script>
        function openAdjustmentModal() {
            document.getElementById('adjustment-modal').classList.remove('hidden');
            document.getElementById('adjustment-modal').classList.add('flex');
        }

        function openAdjustmentModalWithData(dateStr, categories, notes) {
            document.getElementById('adj_modal_date').value = dateStr;
            document.getElementById('adj-modal-date-label').textContent = 'Date: ' + dateStr;
            document.getElementById('adj_modal_notes').value = notes || '';

            if (categories) {
                for (const [catName, catData] of Object.entries(categories)) {
                    const openElem = document.getElementById(`modal_${catName}_opening`);
                    const adjElem = document.getElementById(`modal_${catName}_adjustment`);
                    const closeElem = document.getElementById(`modal_${catName}_closing`);
                    if (openElem) openElem.value = catData.opening;
                    if (adjElem) adjElem.value = catData.adjustment;
                    if (closeElem) closeElem.value = catData.closing;
                }
            }

            openAdjustmentModal();
        }

        function closeAdjustmentModal() {
            document.getElementById('adjustment-modal').classList.add('hidden');
            document.getElementById('adjustment-modal').classList.remove('flex');
        }

        function openCategoriesModal() {
            document.getElementById('categories-modal').classList.remove('hidden');
            document.getElementById('categories-modal').classList.add('flex');
        }

        function closeCategoriesModal() {
            document.getElementById('categories-modal').classList.add('hidden');
            document.getElementById('categories-modal').classList.remove('flex');
        }
    </script>
</x-app-layout>

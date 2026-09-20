<x-app-layout title="Normal Expenses & Petrol Breakdown">
    <div class="space-y-6">
        <!-- Page Header & Navigation -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                    <a href="{{ route('all-expenses.index') }}" class="hover:underline text-indigo-600 dark:text-indigo-400 font-semibold">All Expenses</a>
                    <span>/</span>
                    <a href="{{ route('expenses.index') }}" class="hover:underline text-slate-600 dark:text-slate-400">Normal Expenses</a>
                    <span>/</span>
                    <span class="text-slate-900 dark:text-white font-bold">Breakdown & Petrol Hub</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Normal Expenses & Petrol Breakdown</h1>
                <p class="text-xs text-slate-500">Comprehensive month-wise overview, category & payment splits, and associated vs unassociated petrol tracking.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('expenses.index') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold shadow-xs transition">
                    &larr; Expenses Table
                </a>
                <a href="{{ route('petrol.index') }}" class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-500 text-white text-xs font-semibold shadow-md shadow-teal-600/20 transition">
                    ⛽ Petrol Tracker
                </a>
                <a href="{{ route('expenses.create') }}" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold shadow-md shadow-sky-600/20 transition">
                    + Add Expense
                </a>
            </div>
        </div>

        <!-- Filter Presets & Date Range Picker -->
        <x-date-range-picker :period="$period" :fromDate="$fromDate" :toDate="$toDate" />

        <!-- 4 Metric KPI Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Total Normal Spend -->
            <div class="rounded-3xl border border-indigo-100 dark:border-indigo-950/40 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Normal Spend</span>
                    <span class="text-lg">💰</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">₹{{ number_format($totalNormalSpend, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 text-[11px] text-slate-500">
                    Your personal out-of-pocket
                </div>
            </div>

            <!-- 2. Base Needs Spend -->
            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Base Essential Spend</span>
                    <span class="text-lg">🛡️</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">₹{{ number_format($totalBaseSpend, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 text-[11px] text-slate-500">
                    Non-voluntary routine expenses
                </div>
            </div>

            <!-- 3. Voluntary Spend -->
            <div class="rounded-3xl border border-pink-100 dark:border-pink-950/40 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Voluntary Spending</span>
                    <span class="text-lg">✨</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-pink-600 dark:text-pink-400">₹{{ number_format($totalVoluntarySpend, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 text-[11px] text-slate-500">
                    Discretionary / extra purchases
                </div>
            </div>

            <!-- 4. Petrol Cost -->
            <div class="rounded-3xl border border-teal-100 dark:border-teal-950/40 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Petrol Logged</span>
                    <span class="text-lg">⛽</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-teal-600 dark:text-teal-400">₹{{ number_format($totalPetrolCost, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 text-[11px] text-slate-500 flex justify-between">
                    <span>{{ $allFuelEntries->count() }} fuel fills</span>
                    <span>{{ $totalPetrolLitres }} Litres</span>
                </div>
            </div>
        </div>

        <!-- SECTION 1: PETROL EXPENSES & NORMAL ASSOCIATION HUB -->
        <div class="rounded-3xl border border-teal-200 dark:border-teal-900/60 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>🛵</span> Petrol & Normal Expenses Association Hub
                    </h2>
                    <p class="text-xs text-slate-500">Verify fuel logs that have been recorded into normal expenses versus unlogged fills.</p>
                </div>
                <!-- Inline Quick Scope Info -->
                <div class="flex items-center gap-2 text-xs">
                    <span class="px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300">
                        Period: <span class="capitalize text-teal-600 dark:text-teal-400">{{ $period === 'all' ? 'All Time' : $period }}</span>
                    </span>
                    <a href="{{ route('petrol.index') }}" class="px-3 py-1 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800 font-bold hover:bg-teal-100 transition">
                        Manage in Petrol Tracker &rarr;
                    </a>
                </div>
            </div>

            <!-- Associated vs Unassociated Summary Counters -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Associated with Normal Expenses Card -->
                <div class="p-4 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                            <span>✅</span> Associated with Normal Expenses
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200">
                            {{ $associatedFuel->count() }} Fills
                        </span>
                    </div>
                    <div class="text-2xl font-black text-emerald-700 dark:text-emerald-300">
                        ₹{{ number_format($associatedTotal, 2) }}
                    </div>
                    <p class="text-[11px] text-emerald-600 dark:text-emerald-400">
                        These fuel fills have matching expense records and are counted in your Normal Expenses.
                    </p>

                    <!-- Associated Items Preview List -->
                    <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1 text-xs">
                        @forelse($associatedFuel as $af)
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-emerald-100 dark:border-emerald-900/40 flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $af->date->format('d M Y') }} &middot; {{ $af->petrol_station ?: ($af->vehicle?->name ?? 'Vehicle') }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $af->litres }} L @ ₹{{ $af->price_per_litre }}/L</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-emerald-700 dark:text-emerald-400">₹{{ number_format($af->amount, 2) }}</div>
                                    @if($af->expense && $af->expense->is_archived)
                                        <a href="{{ route('expenses.show', $af->expense_id) }}" class="text-[10px] text-purple-600 dark:text-purple-400 font-bold hover:underline">📦 Archived Expense</a>
                                    @else
                                        <a href="{{ route('expenses.show', $af->expense_id) }}" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold hover:underline">View Expense &rarr;</a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-4 text-center text-slate-400 text-xs">No associated petrol records in this period.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Unassociated Petrol Card -->
                <div class="p-4 rounded-2xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                            <span>⚪</span> Unassociated (Not in Expenses)
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200">
                            {{ $unassociatedFuel->count() }} Fills
                        </span>
                    </div>
                    <div class="text-2xl font-black text-amber-700 dark:text-amber-300">
                        ₹{{ number_format($unassociatedTotal, 2) }}
                    </div>
                    <p class="text-[11px] text-amber-600 dark:text-amber-400">
                        These fuel fills exist in your Petrol log but have NOT been added as a Normal Expense yet.
                    </p>

                    <!-- Unassociated Items List with 1-Click Action -->
                    <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1 text-xs">
                        @forelse($unassociatedFuel as $uf)
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-amber-100 dark:border-amber-900/40 flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $uf->date->format('d M Y') }} &middot; {{ $uf->petrol_station ?: ($uf->vehicle?->name ?? 'Vehicle') }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $uf->litres }} L @ ₹{{ $uf->price_per_litre }}/L</div>
                                </div>
                                <div class="text-right flex items-center gap-2">
                                    <div class="font-bold text-amber-700 dark:text-amber-400">₹{{ number_format($uf->amount, 2) }}</div>
                                    <form action="{{ route('petrol.link-expense', $uf) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold text-[10px] shadow-xs cursor-pointer transition" title="Log this petrol fill as normal expense">
                                            + Log Expense
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="py-4 text-center text-slate-400 text-xs">All petrol records are fully associated!</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: NORMAL EXPENSES MONTH-WISE BREAKDOWN -->
        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>🗓️</span> Month-Wise Normal Expense History
                    </h2>
                    <p class="text-xs text-slate-500">Monthly breakdown of your personal spending, category totals, and payment modes.</p>
                </div>
                <div class="text-xs text-slate-400">
                    {{ $monthlyGroups->count() }} Month(s) Recorded
                </div>
            </div>

            <!-- Month Cards Accordion -->
            <div class="space-y-4">
                @forelse($monthlyGroups as $monthName => $mData)
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-850 p-4 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>📅</span> {{ $mData['month'] }}
                                </h3>
                                <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                                    <span>{{ $mData['count'] }} transactions</span>
                                    <span>&middot;</span>
                                    <span>Base Needs: <strong>₹{{ number_format($mData['base'], 2) }}</strong></span>
                                    @if($mData['voluntary'] > 0)
                                        <span>&middot;</span>
                                        <span class="text-pink-600 font-semibold">Voluntary: ₹{{ number_format($mData['voluntary'], 2) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Month Spend</span>
                                    <span class="text-xl font-black text-rose-600 dark:text-rose-400">₹{{ number_format($mData['total'], 2) }}</span>
                                </div>
                                <a href="{{ route('expenses.index', ['period' => 'month', 'from_date' => \Carbon\Carbon::parse($mData['month'])->startOfMonth()->toDateString(), 'to_date' => \Carbon\Carbon::parse($mData['month'])->endOfMonth()->toDateString()]) }}" class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-100 transition">
                                    View Month Table &rarr;
                                </a>
                            </div>
                        </div>

                        <!-- Month Category & Payment Mini-Breakdowns -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2 border-t border-slate-200/60 dark:border-slate-800">
                            <!-- Categories in this month -->
                            <div class="space-y-1.5">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Categories</span>
                                <div class="space-y-1">
                                    @foreach($mData['categories'] as $mCat)
                                        <div class="flex items-center justify-between text-xs p-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $mCat['color'] }}"></span>
                                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $mCat['name'] }}</span>
                                                <span class="text-[10px] text-slate-400">({{ $mCat['count'] }}x)</span>
                                            </div>
                                            <div class="font-bold text-slate-900 dark:text-white">
                                                ₹{{ number_format($mCat['total'], 2) }}
                                                <span class="text-[10px] font-normal text-slate-400">({{ $mCat['percentage'] }}%)</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Payments in this month -->
                            <div class="space-y-1.5">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Payment Modes</span>
                                <div class="space-y-1">
                                    @foreach($mData['payments'] as $mPay)
                                        <div class="flex items-center justify-between text-xs p-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $mPay['method'] }} ({{ $mPay['count'] }}x)</span>
                                            <span class="font-bold text-slate-900 dark:text-white">₹{{ number_format($mPay['total'], 2) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400 text-xs">No normal expenses found for this time period.</div>
                @endforelse
            </div>
        </div>

        <!-- SECTION 3: OVERALL CATEGORY & PAYMENT DISTRIBUTION -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Overall Category Breakdown -->
            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-4">
                <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📊</span> Overall Category Distribution
                </h3>
                <div class="space-y-2">
                    @forelse($overallCategoryBreakdown as $cat)
                        <div class="p-3 rounded-2xl bg-slate-50/80 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-full" style="background-color: {{ $cat['color'] }}"></span>
                                <div>
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $cat['name'] }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ $cat['count'] }} transaction(s)</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-black text-slate-900 dark:text-white text-sm">₹{{ number_format($cat['total'], 2) }}</span>
                                <span class="text-[10px] text-slate-400 block font-semibold">{{ $cat['percentage'] }}% of total</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs">No category data.</div>
                    @endforelse
                </div>
            </div>

            <!-- Overall Payment Method Breakdown -->
            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-4">
                <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                    <span>💳</span> Overall Payment Method Distribution
                </h3>
                <div class="space-y-2">
                    @forelse($overallPaymentBreakdown as $pay)
                        <div class="p-3 rounded-2xl bg-slate-50/80 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $pay['method'] }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $pay['count'] }} payment line(s)</span>
                            </div>
                            <div class="text-right">
                                <span class="font-black text-slate-900 dark:text-white text-sm">₹{{ number_format($pay['total'], 2) }}</span>
                                <span class="text-[10px] text-slate-400 block font-semibold">{{ $pay['percentage'] }}% of total</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs">No payment data.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

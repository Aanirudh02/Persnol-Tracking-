<x-app-layout title="Today's Cash & UPI Register Flow">
    <div class="mx-auto max-w-7xl space-y-6">
        <!-- Top Navigation & Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('daily-balances.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 transition">
                    <span>&larr;</span> Back to Full Register Calendar
                </a>
                <div class="mt-2 flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Today's Live Flow Breakdown
                    </h1>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-black text-emerald-600 ring-1 ring-emerald-500/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live Today
                    </span>
                </div>
                <p class="text-xs text-slate-500">
                    Real-time liquid tracking for {{ $today->format('l, d F Y') }}. Opening balance, incoming, and outgoing flow separated by payment category.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('daily-balances.index') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                    📅 Calendar View
                </a>
                <a href="{{ route('income.create') }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs font-bold text-white shadow-sm transition">
                    + Inflow (Income)
                </a>
                <a href="{{ route('expenses.create') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white shadow-sm transition">
                    + Outflow (Expense)
                </a>
            </div>
        </div>

        <!-- Section 1: Hero Combined Register Card -->
        <div class="rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 p-6 text-white shadow-xl">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-lg bg-slate-800/80 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-300">
                        <span>Combined Register Total</span> · <span>{{ $today->format('l, F j, Y') }}</span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-3">
                        <div class="text-4xl font-black tracking-tight text-white sm:text-5xl">
                            ₹{{ number_format($totals['closing'], 2) }}
                        </div>
                        <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Today's Current Closing</span>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">
                        Combined cashflow sum across all active liquid categories ({{ implode(', ', $activeCategories) }}).
                    </p>
                </div>

                <!-- Combined Formula Pill -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-4 backdrop-blur-xs">
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Opening</div>
                            <div class="text-sm font-bold text-slate-200">₹{{ number_format($totals['opening'], 2) }}</div>
                        </div>
                        <div class="border-l border-slate-800 pl-4">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-400">Inflow (+)</div>
                            <div class="text-sm font-bold text-emerald-400">+₹{{ number_format($totals['inflow'], 2) }}</div>
                        </div>
                        <div class="border-l border-slate-800 pl-4">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-rose-400">Outflow (-)</div>
                            <div class="text-sm font-bold text-rose-400">-₹{{ number_format($totals['outflow'], 2) }}</div>
                        </div>
                        <div class="border-l border-slate-800 pl-4">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-sky-400">Closing (=)</div>
                            <div class="text-sm font-black text-sky-300">₹{{ number_format($totals['closing'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Individual Category Breakdown Cards (UPI, Cash, etc.) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ count($categoriesBreakdown) > 2 ? '3' : '2' }} gap-5">
            @foreach($categoriesBreakdown as $catName => $data)
                @php
                    $isUPI = strtolower($catName) === 'upi';
                    $isCash = strtolower($catName) === 'cash';
                    $cardBorder = $isUPI ? 'border-sky-200/80 dark:border-sky-900/50' : ($isCash ? 'border-emerald-200/80 dark:border-emerald-900/50' : 'border-slate-200 dark:border-slate-800');
                    $badgeBg = $isUPI ? 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300' : ($isCash ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300');
                    $heroColor = $isUPI ? 'text-sky-700 dark:text-sky-300' : ($isCash ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-900 dark:text-white');
                    $icon = $isUPI ? '📱' : ($isCash ? '💵' : '💳');
                @endphp
                <div class="rounded-3xl border {{ $cardBorder }} bg-white dark:bg-slate-900 p-5 shadow-sm transition hover:shadow-md space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xl">{{ $icon }}</span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $catName }} Register</h3>
                                <p class="text-[11px] text-slate-500">Liquid Payment Category</p>
                            </div>
                        </div>
                        <span class="rounded-xl px-2.5 py-1 text-[11px] font-bold {{ $badgeBg }}">
                            {{ count($data['incomes']) + count($data['expenses']) }} transactions
                        </span>
                    </div>

                    <!-- Current Balance -->
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Closing / Current Balance</div>
                        <div class="text-3xl font-black {{ $heroColor }} mt-0.5">
                            ₹{{ number_format($data['closing'], 2) }}
                        </div>
                    </div>

                    <!-- Flow Equation Row -->
                    <div class="grid grid-cols-4 gap-2 rounded-2xl bg-slate-50 dark:bg-slate-800/60 p-3 text-center">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Opening</div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-0.5">₹{{ number_format($data['opening'], 2) }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">In (+)</div>
                            <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">+₹{{ number_format($data['inflow'], 2) }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Out (-)</div>
                            <div class="text-xs font-bold text-rose-600 dark:text-rose-400 mt-0.5">-₹{{ number_format($data['outflow'], 2) }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Net Flow</div>
                            <div class="text-xs font-black {{ $data['net_flow'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} mt-0.5">
                                {{ $data['net_flow'] >= 0 ? '+' : '' }}₹{{ number_format($data['net_flow'], 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Section 3: Today's Detailed Transactions (UPI vs Cash) -->
        <div class="rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4">
                <span>🧾</span> Today's Category-Specific Activity Feed
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- UPI Feed -->
                <div class="rounded-2xl border border-sky-100 dark:border-sky-950/60 bg-sky-50/20 dark:bg-sky-950/20 p-4 space-y-3">
                    <div class="flex items-center justify-between border-b border-sky-100 dark:border-sky-900/40 pb-2">
                        <div class="flex items-center gap-2">
                            <span>📱</span>
                            <span class="font-bold text-sm text-slate-900 dark:text-white">UPI Activity</span>
                        </div>
                        <span class="text-xs text-slate-500">
                            +₹{{ number_format($categoriesBreakdown['UPI']['inflow'] ?? 0, 2) }} / -₹{{ number_format($categoriesBreakdown['UPI']['outflow'] ?? 0, 2) }}
                        </span>
                    </div>

                    @php
                        $upiIncomes = $categoriesBreakdown['UPI']['incomes'] ?? collect();
                        $upiExpenses = $categoriesBreakdown['UPI']['expenses'] ?? collect();
                    @endphp

                    @if($upiIncomes->isEmpty() && $upiExpenses->isEmpty())
                        <p class="py-6 text-center text-xs text-slate-400">No UPI transactions recorded today.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($upiIncomes as $inc)
                                <div class="flex items-center justify-between rounded-xl bg-white dark:bg-slate-900 p-3 text-xs border border-emerald-100 dark:border-emerald-950/50 shadow-2xs">
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $inc->source ?: 'Income' }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $inc->category?->name ?? 'General' }} · {{ $inc->time ? substr($inc->time, 0, 5) : 'Today' }}</div>
                                    </div>
                                    <div class="font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                                        +₹{{ number_format($inc->amount, 2) }}
                                    </div>
                                </div>
                            @endforeach

                            @foreach($upiExpenses as $exp)
                                <div class="flex items-center justify-between rounded-xl bg-white dark:bg-slate-900 p-3 text-xs border border-rose-100 dark:border-rose-950/50 shadow-2xs">
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $exp->description }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $exp->category?->name ?? 'Expense' }} · {{ $exp->time ? substr($exp->time, 0, 5) : 'Today' }}</div>
                                    </div>
                                    <div class="font-bold text-rose-600 dark:text-rose-400 text-sm">
                                        -₹{{ number_format((float)$exp->amount + (float)($exp->gst_amount ?? 0), 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Cash Feed -->
                <div class="rounded-2xl border border-emerald-100 dark:border-emerald-950/60 bg-emerald-50/20 dark:bg-emerald-950/20 p-4 space-y-3">
                    <div class="flex items-center justify-between border-b border-emerald-100 dark:border-emerald-900/40 pb-2">
                        <div class="flex items-center gap-2">
                            <span>💵</span>
                            <span class="font-bold text-sm text-slate-900 dark:text-white">Cash Activity</span>
                        </div>
                        <span class="text-xs text-slate-500">
                            +₹{{ number_format($categoriesBreakdown['Cash']['inflow'] ?? 0, 2) }} / -₹{{ number_format($categoriesBreakdown['Cash']['outflow'] ?? 0, 2) }}
                        </span>
                    </div>

                    @php
                        $cashIncomes = $categoriesBreakdown['Cash']['incomes'] ?? collect();
                        $cashExpenses = $categoriesBreakdown['Cash']['expenses'] ?? collect();
                    @endphp

                    @if($cashIncomes->isEmpty() && $cashExpenses->isEmpty())
                        <p class="py-6 text-center text-xs text-slate-400">No Cash transactions recorded today.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($cashIncomes as $inc)
                                <div class="flex items-center justify-between rounded-xl bg-white dark:bg-slate-900 p-3 text-xs border border-emerald-100 dark:border-emerald-950/50 shadow-2xs">
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $inc->source ?: 'Income' }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $inc->category?->name ?? 'General' }} · {{ $inc->time ? substr($inc->time, 0, 5) : 'Today' }}</div>
                                    </div>
                                    <div class="font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                                        +₹{{ number_format($inc->amount, 2) }}
                                    </div>
                                </div>
                            @endforeach

                            @foreach($cashExpenses as $exp)
                                <div class="flex items-center justify-between rounded-xl bg-white dark:bg-slate-900 p-3 text-xs border border-rose-100 dark:border-rose-950/50 shadow-2xs">
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $exp->description }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $exp->category?->name ?? 'Expense' }} · {{ $exp->time ? substr($exp->time, 0, 5) : 'Today' }}</div>
                                    </div>
                                    <div class="font-bold text-rose-600 dark:text-rose-400 text-sm">
                                        -₹{{ number_format((float)$exp->amount + (float)($exp->gst_amount ?? 0), 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout title="Finance Hub">
    <div class="space-y-6">
        <!-- Header & Nav Tabs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Finance Management</h1>
                <p class="text-xs text-slate-500">Track personal expenses, income, payment reconciliations, and friend settlements.</p>
            </div>

            <!-- Finance Sub-Nav Buttons -->
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('expenses.index') }}" class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold hover:bg-slate-50 transition">Expenses</a>
                <a href="{{ route('income.index') }}" class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold hover:bg-slate-50 transition">Income</a>
                <a href="{{ route('payments.index') }}" class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold hover:bg-slate-50 transition">Payments</a>
                <a href="{{ route('friends.index') }}" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-sm font-semibold hover:bg-slate-50 transition">Friends & Splits</a>
                <a href="{{ route('credits.index', ['type' => 'credit']) }}" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-sm font-semibold hover:bg-slate-50 transition">Credits</a>
                <a href="{{ route('credits.index', ['type' => 'debt']) }}" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-sm font-semibold hover:bg-slate-50 transition">Debts</a>
                <a href="{{ route('reports.index') }}" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-sm font-semibold hover:bg-slate-50 transition">Reports</a>
            </div>
        </div>

        @if($wallets->count())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($wallets as $wallet)
                    <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-sm">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $wallet->payment_method }} balance</span>
                        <div class="text-2xl font-bold {{ $wallet->current_balance >= 0 ? 'text-slate-900' : 'text-rose-600' }} mt-2">
                            ₹{{ number_format($wallet->current_balance, 2) }}
                        </div>
                        <span class="text-xs text-slate-500">Opening ₹{{ number_format($wallet->opening_balance, 2) }}</span>
                    </div>
                @endforeach
                <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-sm">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Open credits (I owe)</span>
                    <div class="text-2xl font-bold text-rose-600 mt-2">₹{{ number_format($openCredits, 2) }}</div>
                </div>
                <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-sm">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Open debts (owe me)</span>
                    <div class="text-2xl font-bold text-emerald-600 mt-2">₹{{ number_format($openDebts, 2) }}</div>
                </div>
                @if(($stats['voluntary_spend'] ?? 0) > 0)
                    <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-sm">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Voluntary this month</span>
                        <div class="text-2xl font-bold text-pink-600 mt-2">₹{{ number_format($stats['voluntary_spend'], 2) }}</div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">This Month Income</span>
                <div class="text-2xl font-bold text-emerald-600 mt-2">
                    ₹{{ number_format($stats['total_income'], 2) }}
                </div>
                <span class="text-xs text-slate-500">Money received</span>
            </div>

            <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">This Month Expenses</span>
                <div class="text-2xl font-bold text-rose-600 mt-2">
                    ₹{{ number_format($stats['total_expenses'], 2) }}
                </div>
                <span class="text-xs text-slate-500">Excludes archived & voluntary</span>
            </div>

            <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Net Savings</span>
                <div class="text-2xl font-bold {{ $stats['net_savings'] >= 0 ? 'text-slate-900' : 'text-amber-600' }} mt-2">
                    ₹{{ number_format($stats['net_savings'], 2) }}
                </div>
                <span class="text-xs text-slate-500">Cash balance</span>
            </div>

            <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Payments</span>
                <div class="text-2xl font-bold text-amber-600 mt-2">
                    ₹{{ number_format($stats['pending_payments'], 2) }}
                </div>
                <span class="text-xs text-slate-500">Awaiting reconciliation</span>
            </div>
        </div>

        <!-- Friend Settlements Summary Card -->
        <div class="p-5 rounded-3xl bg-slate-50 text-slate-900 shadow-sm border border-slate-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                <div>
                    <h2 class="font-bold text-base text-slate-900">Friends Debt & Settlement Overview</h2>
                    <p class="text-sm text-slate-500">Shared expense balances and repayments</p>
                </div>
                <a href="{{ route('friends.index') }}" class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-sm font-semibold text-white shadow-sm transition">Manage Friends &rarr;</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @forelse($friendBalances as $fb)
                    <div class="p-3.5 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-slate-900">{{ $fb['friend']->name }} <span class="text-xs text-slate-400">{{ $fb['friend']->role }}</span></span>
                            <span class="text-xs font-mono font-bold {{ $fb['balance']['net'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $fb['balance']['net'] >= 0 ? '+₹' . number_format($fb['balance']['net'], 2) : '-₹' . number_format(abs($fb['balance']['net']), 2) }}
                            </span>
                        </div>
                        <div class="text-xs text-slate-500 mt-2 space-y-0.5">
                            <p>You owe: ₹{{ number_format($fb['balance']['i_owe_friend'], 2) }}</p>
                            <p>Owes you: ₹{{ number_format($fb['balance']['friend_owes_me'], 2) }}</p>
                        </div>
                    </div>
                @empty
                    <div class="sm:col-span-3 text-center py-4 text-sm text-slate-400">
                        No friends added yet. <a href="{{ route('friends.index') }}" class="text-slate-900 font-semibold hover:underline">Add your first friend</a> to track split balances.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Category Breakdown & Recent Transactions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Expenses -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="font-bold text-sm text-slate-900 dark:text-white">Recent Expenses</h2>
                    <a href="{{ route('expenses.index') }}" class="text-xs text-indigo-600 hover:underline">All &rarr;</a>
                </div>
                <div class="space-y-2">
                    @foreach($recentExpenses as $exp)
                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $exp->description }}</span>
                                <span class="block text-[10px] text-slate-400">{{ $exp->date->format('M j') }} &bull; {{ $exp->category?->name ?? 'Other' }}</span>
                            </div>
                            <span class="font-bold text-rose-600 dark:text-rose-400">-₹{{ number_format($exp->amount, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Category-wise spending -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="font-bold text-sm text-slate-900 dark:text-white">Category Spending (This Month)</h2>
                </div>
                <div class="space-y-2.5">
                    @foreach($categorySpending as $cat)
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $cat->name }}</span>
                                <span class="font-bold text-slate-900 dark:text-white">₹{{ number_format($cat->total, 2) }}</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                @php
                                    $pct = $stats['total_expenses'] > 0 ? min(100, round(($cat->total / $stats['total_expenses']) * 100)) : 0;
                                @endphp
                                <div class="h-full rounded-full bg-indigo-600" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

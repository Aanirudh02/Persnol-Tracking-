<x-app-layout title="Finance Hub">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Finance Hub</h1>
                <p class="text-xs text-slate-500">Monthly overview, friend balances, and payment-method-driven totals.</p>
            </div>

            <div class="flex flex-wrap gap-2 text-xs">
                <a href="{{ route('expenses.index') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold text-slate-700">Expenses</a>
                <a href="{{ route('income.index') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold text-slate-700">Income</a>
                <a href="{{ route('payments.index') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold text-slate-700">Payments</a>
                <a href="{{ route('friends.index') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold text-slate-700">Friends & Splits</a>
                <a href="{{ route('settings.index') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold text-slate-700">Settings</a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-3xl border border-sky-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">This Week</span>
                    <span class="rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-semibold text-sky-700">7 Days</span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-semibold text-slate-400 block">Expense</span>
                        <div class="text-xl font-bold text-rose-600">₹{{ number_format($weeklyStats['total_expenses'] ?? 0, 2) }}</div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase font-semibold text-slate-400 block">Income</span>
                        <div class="text-base font-bold text-emerald-600">₹{{ number_format($weeklyStats['total_income'] ?? 0, 2) }}</div>
                    </div>
                </div>
                <div class="mt-2 border-t border-slate-100 pt-2 text-[11px] text-slate-500 flex justify-between">
                    <span>Week Net:</span>
                    <span class="font-semibold {{ (($weeklyStats['net_savings'] ?? 0) >= 0) ? 'text-emerald-600' : 'text-rose-600' }}">
                        ₹{{ number_format($weeklyStats['net_savings'] ?? 0, 2) }}
                    </span>
                </div>
            </div>

            @if($sections['show_total_expense'])
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">This Month Expense</span>
                        <span class="text-lg">💰</span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-rose-600">₹{{ number_format($stats['total_expenses'], 2) }}</div>
                    <div class="mt-2 border-t border-slate-100 pt-2 text-[11px] text-slate-500">
                        Voluntary: ₹{{ number_format($stats['voluntary_spend'] ?? 0, 2) }}
                    </div>
                </div>
            @endif

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">This Month Income</span>
                    <span class="text-lg">💵</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-emerald-600">₹{{ number_format($stats['total_income'], 2) }}</div>
                <div class="mt-2 border-t border-slate-100 pt-2 text-[11px] text-slate-500 flex justify-between">
                    <span>Net Savings:</span>
                    <span class="font-semibold {{ $stats['net_savings'] >= 0 ? 'text-slate-900' : 'text-amber-600' }}">₹{{ number_format($stats['net_savings'], 2) }}</span>
                </div>
            </div>

            @if($sections['show_current_balance'])
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Current Balance</span>
                        <span class="text-lg">🏦</span>
                    </div>
                    <div class="mt-2 text-2xl font-bold {{ $currentBalance >= 0 ? 'text-slate-900' : 'text-rose-600' }}">₹{{ number_format($currentBalance, 2) }}</div>
                    <div class="mt-2 border-t border-slate-100 pt-2 text-[11px] text-slate-500 flex justify-between">
                        <span>Pending to Pay:</span>
                        <span class="font-semibold text-amber-600">₹{{ number_format($stats['pending_payments'] ?? 0, 2) }}</span>
                    </div>
                </div>
            @endif
        </div>

        @if($sections['show_wallet_balances'])
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($wallets as $wallet)
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $wallet->payment_method }}</span>
                            @if($wallet->is_enabled)
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Enabled</span>
                            @endif
                        </div>
                        <div class="mt-2 text-xl font-bold {{ $wallet->current_balance >= 0 ? 'text-slate-900' : 'text-rose-600' }}">₹{{ number_format($wallet->current_balance, 2) }}</div>
                        <div class="text-xs text-slate-500">Opening ₹{{ number_format($wallet->opening_balance, 2) }}</div>
                    </div>
                @endforeach
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Open Credits</span>
                    <div class="mt-2 text-xl font-bold text-rose-600">₹{{ number_format($openCredits, 2) }}</div>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Open Debts</span>
                    <div class="mt-2 text-xl font-bold text-emerald-600">₹{{ number_format($openDebts, 2) }}</div>
                </div>
            </div>
        @endif

        @if($sections['show_friend_overview'])
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Friends Overview</h2>
                        <p class="text-xs text-slate-500">Canonical balance = splits + credit/debt remaining + settlements.</p>
                    </div>
                    <div class="text-xs text-slate-500">
                        Owed to you <strong class="text-emerald-600">₹{{ number_format($totalOwedToMe, 2) }}</strong>
                        <span class="mx-1">&bull;</span>
                        You owe <strong class="text-rose-600">₹{{ number_format($totalIOwe, 2) }}</strong>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @forelse($friendBalances as $row)
                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900">{{ $row['friend']->name }}</span>
                                <span class="text-xs font-bold {{ $row['balance']['net'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $row['balance']['net'] >= 0 ? '+₹'.number_format($row['balance']['net'], 2) : '-₹'.number_format(abs($row['balance']['net']), 2) }}
                                </span>
                            </div>
                            <div class="mt-2 text-xs text-slate-500">
                                <p>You owe: ₹{{ number_format($row['balance']['i_owe_friend'], 2) }}</p>
                                <p>Owes you: ₹{{ number_format($row['balance']['friend_owes_me'], 2) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">No friends added yet.</p>
                    @endforelse
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @if($sections['show_expense_by_payment_type'])
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-bold text-slate-900">Expense By Payment Type</h2>
                    <div class="space-y-3">
                        @forelse($paymentMethodSpending as $row)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-xs">
                                    <span class="font-medium text-slate-700">{{ $row->payment_method }}</span>
                                    <span class="font-bold text-slate-900">₹{{ number_format($row->total, 2) }}</span>
                                </div>
                                @php $pct = $stats['total_expenses'] > 0 ? min(100, round(($row->total / $stats['total_expenses']) * 100)) : 0; @endphp
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-sky-600" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400">No expense data yet.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if($sections['show_expense_by_category'])
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-bold text-slate-900">Expense By Category</h2>
                    <div class="space-y-3">
                        @forelse($categorySpending as $row)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-xs">
                                    <span class="font-medium text-slate-700">{{ $row->name }}</span>
                                    <span class="font-bold text-slate-900">₹{{ number_format($row->total, 2) }}</span>
                                </div>
                                @php $pct = $stats['total_expenses'] > 0 ? min(100, round(($row->total / $stats['total_expenses']) * 100)) : 0; @endphp
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-indigo-600" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400">No category data yet.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900">Recent Expenses</h2>
                    <a href="{{ route('expenses.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">All expenses</a>
                </div>
                <div class="space-y-2">
                    @foreach($recentExpenses as $expense)
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-3 text-xs">
                            <div>
                                <div class="font-semibold text-slate-900">{{ $expense->description }}</div>
                                <div class="text-slate-500">{{ $expense->date->format('d M') }} · {{ $expense->category?->name ?? 'Other' }} · {{ $expense->payment_method }}</div>
                                @if($expense->friendSplit)
                                    <div class="text-indigo-600">Split with {{ $expense->friendSplit->friend?->name }}</div>
                                @endif
                            </div>
                            <div class="font-bold text-rose-600">₹{{ number_format($expense->totalAmount(), 2) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900">Pending Payments</h2>
                    <a href="{{ route('payments.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">Open</a>
                </div>
                <div class="space-y-2">
                    @forelse($pendingPayments as $payment)
                        <div class="rounded-2xl bg-slate-50 px-3 py-3 text-xs">
                            <div class="font-semibold text-slate-900">{{ $payment->purpose }}</div>
                            <div class="text-slate-500">{{ $payment->paid_to }} · {{ $payment->payment_method }}</div>
                            <div class="mt-1 font-bold text-amber-700">₹{{ number_format($payment->amount, 2) }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">No pending payments.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout title="All Expenses">
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">All Expenses Overview</h1>
                <p class="text-xs text-slate-500">Comprehensive overview of Normal Expenses (active & archived) and Personal Expenses.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('expenses.create') }}" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-sm transition">
                    + Add Normal Expense
                </a>
                <a href="{{ route('personal-expenses.index') }}" class="px-4 py-2 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-xs font-semibold shadow-sm transition">
                    + Add Personal Expense
                </a>
            </div>
        </div>

        <!-- Metric KPI Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Combined Spend</span>
                    <span class="text-lg">💰</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-slate-900">₹{{ number_format($combinedTotal, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 pt-2 text-[11px] text-slate-500">
                    {{ $allTransactions->count() }} transaction(s)
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Normal Expenses</span>
                    <span class="text-lg">📘</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-slate-900">₹{{ number_format($normalTotal, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 pt-2 text-[11px] text-slate-500">
                    Regular college & tracked
                </div>
            </div>

            <div class="rounded-3xl border border-pink-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Personal Expenses</span>
                    <span class="text-lg">🛍️</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-pink-600">₹{{ number_format($personalTotal, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 pt-2 text-[11px] text-slate-500">
                    Family, tour & shopping
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Voluntary Spending</span>
                    <span class="text-lg">✨</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-amber-500">₹{{ number_format($voluntaryTotal, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 pt-2 text-[11px] text-slate-500">
                    Discretionary / Leisure
                </div>
            </div>
        </div>

        <!-- Filter Presets & Component -->
        <x-date-range-picker :period="$period" :fromDate="$fromDate" :toDate="$toDate" :extraParams="request()->only(['type'])" />

        <!-- Scope Switcher Tabs -->
        <div class="flex flex-wrap gap-2 text-xs">
            <a href="{{ route('all-expenses.index', array_merge(request()->query(), ['type' => 'all'])) }}"
                class="rounded-xl border px-3 py-2 font-semibold transition {{ $typeFilter === 'all' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                All Records ({{ $allTransactions->count() }})
            </a>
            <a href="{{ route('all-expenses.index', array_merge(request()->query(), ['type' => 'normal'])) }}"
                class="rounded-xl border px-3 py-2 font-semibold transition {{ $typeFilter === 'normal' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                📘 Normal Only
            </a>
            <a href="{{ route('all-expenses.index', array_merge(request()->query(), ['type' => 'personal'])) }}"
                class="rounded-xl border px-3 py-2 font-semibold transition {{ $typeFilter === 'personal' ? 'border-pink-600 bg-pink-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                🛍️ Personal Only
            </a>
            <a href="{{ route('all-expenses.index', array_merge(request()->query(), ['type' => 'voluntary'])) }}"
                class="rounded-xl border px-3 py-2 font-semibold transition {{ $typeFilter === 'voluntary' ? 'border-amber-500 bg-amber-500 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                ✨ Voluntary Only
            </a>
            <a href="{{ route('all-expenses.index', array_merge(request()->query(), ['type' => 'archived'])) }}"
                class="rounded-xl border px-3 py-2 font-semibold transition {{ $typeFilter === 'archived' ? 'border-purple-600 bg-purple-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                📦 Archived Categories
            </a>
        </div>

        <!-- Detailed Category Breakdown Cards -->
        @if($categoryBreakdown->isNotEmpty())
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                    <span>📊</span> Category-Wise Breakdown
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($categoryBreakdown as $cat)
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $cat['color'] }}"></span>
                                    {{ $cat['name'] }}
                                </span>
                                <span class="text-xs font-black text-slate-900">₹{{ number_format($cat['total'], 2) }}</span>
                            </div>
                            <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all" style="width: {{ min(100, $cat['percentage']) }}%; background-color: {{ $cat['color'] }}"></div>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-slate-500">
                                <span>{{ $cat['count'] }} transaction(s)</span>
                                <span>{{ $cat['percentage'] }}% of overall</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Transactions Stream Table -->
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            @if($allTransactions->isEmpty())
                <div class="text-center py-16 text-slate-400 text-xs">
                    <span class="text-3xl block mb-2">💸</span>
                    No records found matching this filter criteria.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4">Type</th>
                                <th class="py-3.5 px-4">Date</th>
                                <th class="py-3.5 px-4">Description</th>
                                <th class="py-3.5 px-4">Category</th>
                                <th class="py-3.5 px-4">Payment Method</th>
                                <th class="py-3.5 px-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($allTransactions as $item)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        @if($item['item_type'] === 'normal')
                                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-700">
                                                📘 Normal
                                            </span>
                                        @else
                                            <span class="rounded-full bg-pink-50 px-2.5 py-0.5 text-[10px] font-bold text-pink-700 border border-pink-200">
                                                🛍️ Personal
                                            </span>
                                        @endif

                                        @if($item['is_voluntary'])
                                            <span class="ml-1 rounded-full bg-amber-50 px-2 py-0.5 text-[9px] font-bold text-amber-700">
                                                Voluntary
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 font-mono text-slate-600 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($item['date'])->format('d M Y') }}
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-900">
                                        {{ $item['description'] }}
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-semibold text-slate-700">
                                            {{ $item['category'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 whitespace-nowrap font-medium">
                                        {{ $item['payment_method'] }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900 text-sm whitespace-nowrap">
                                        ₹{{ number_format($item['amount'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

<x-app-layout title="Personal Expenses">
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Personal Expenses</h1>
                <p class="text-xs text-slate-500">Track family, hotel, shopping, tour, and personal spending independently.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('statements.create', ['type' => 'personal']) }}" style="background-color: #db2777; color: #ffffff;" class="px-4 py-2 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <span>📄</span> Take Statement
                </a>
                <button type="button" onclick="openPersonalModal()" style="background-color: #be185d; color: #ffffff;" class="px-4 py-2 rounded-xl bg-pink-700 hover:bg-pink-800 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <span>+</span> Add Personal Expense
                </button>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-3xl border border-pink-100 dark:border-pink-950/40 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Filtered Spend</span>
                    <span class="text-lg">🛍️</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-pink-600">₹{{ number_format($totalAmount, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 text-[11px] text-slate-500">
                    {{ $expenses->total() }} recorded transaction(s)
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">This Month's Spend</span>
                    <span class="text-lg">📅</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">₹{{ number_format($monthAmount, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 text-[11px] text-slate-500">
                    Current calendar month
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Voluntary Spending</span>
                    <span class="text-lg">✨</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-amber-500">₹{{ number_format($voluntaryAmount, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 text-[11px] text-slate-500">
                    Discretionary / Leisure
                </div>
            </div>
        </div>

        <!-- Active vs Archived Navigation Tabs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
            <div class="flex items-center gap-2">
                <a href="{{ route('personal-expenses.index', array_merge(request()->except(['page', 'status']), ['status' => 'active'])) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ ($status ?? 'active') === 'active' ? 'bg-pink-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span>🛍️ Active Personal Expenses</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] {{ ($status ?? 'active') === 'active' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $activeCount }}</span>
                </a>
                <a href="{{ route('personal-expenses.index', array_merge(request()->except(['page', 'status']), ['status' => 'archived'])) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ ($status ?? 'active') === 'archived' ? 'bg-amber-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span>📁 Archived / Historical</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] {{ ($status ?? 'active') === 'archived' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $archivedCount }}</span>
                </a>
            </div>
            @if(($status ?? 'active') === 'archived')
                <div class="flex items-center gap-1.5 text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-3 py-1.5 rounded-xl border border-amber-200 dark:border-amber-900">
                    <span>ℹ️</span> <span>Historical Archive: Excluded from active statements. You can restore or delete items permanently.</span>
                </div>
            @endif
        </div>

        <!-- Filter Bar with Explicit Search & Reset -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <form action="{{ route('personal-expenses.index') }}" method="GET" class="space-y-3 text-xs">
                <input type="hidden" name="status" value="{{ $status ?? 'active' }}">
                <div class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-6 gap-3">
                    <div>
                        <label class="block text-slate-400 mb-1">Category</label>
                        <select name="category_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                            <option value="">All Methods</option>
                            @foreach($paymentMethods as $pm)
                                <option value="{{ $pm }}" {{ request('payment_method') == $pm ? 'selected' : '' }}>{{ $pm }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">Done By</label>
                        <input type="text" name="done_by" value="{{ request('done_by') }}" placeholder="e.g. Me" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">Done To</label>
                        <input type="text" name="done_to" value="{{ request('done_to') }}" placeholder="e.g. Mom" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">From Date</label>
                        <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">To Date</label>
                        <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('personal-expenses.index', ['status' => $status ?? 'active']) }}" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold transition">
                        🔄 Reset
                    </a>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-pink-600 hover:bg-pink-500 text-white font-bold transition shadow-sm cursor-pointer">
                        🔍 Search / Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Personal Expenses Table / Grouping Container -->
        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
            @if(($status ?? 'active') === 'active')
                <form id="group-personal-expenses-form" action="{{ route('personal-expenses.group') }}" method="POST" class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 p-4 text-xs">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-pink-600 hover:bg-pink-700 text-white font-semibold cursor-pointer transition shadow-xs">
                        Group selected expenses
                    </button>
                    <span class="text-slate-400">Select any two or more personal expenses that are not already in a group.</span>
                </form>
            @else
                <div class="flex items-center justify-between border-b border-amber-100 dark:border-amber-900/40 bg-amber-50/50 dark:bg-amber-950/20 p-4 text-xs">
                    <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300 font-semibold">
                        <span>📁</span>
                        <span>Archived / Historical Personal Records ({{ $archivedCount }})</span>
                    </div>
                    <span class="text-slate-400 text-[11px]">Click "Restore" to move back to active, or "Delete" to permanently remove.</span>
                </div>
            @endif

            @if($expenses->isEmpty())
                <div class="text-center py-16 text-slate-400 text-xs">
                    <span class="text-3xl block mb-2">🛍️</span>
                    No personal expenses recorded for this filter.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-3.5 px-4">Group</th>
                                <th class="py-3.5 px-4">Date</th>
                                <th class="py-3.5 px-4">Description</th>
                                <th class="py-3.5 px-4">Category</th>
                                <th class="py-3.5 px-4">Amount</th>
                                <th class="py-3.5 px-4">Method</th>
                                <th class="py-3.5 px-4">Done By / To</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @php $shownGroupIds = []; @endphp
                            @foreach($expenses as $exp)
                                @php
                                    $isGroup = $exp->personalExpenseGroup !== null;
                                    if ($isGroup && in_array($exp->personalExpenseGroup->id, $shownGroupIds, true)) {
                                        continue;
                                    }
                                    $groupExpenses = $isGroup ? $exp->personalExpenseGroup->personalExpenses : collect([$exp]);
                                    if ($isGroup) {
                                        $shownGroupIds[] = $exp->personalExpenseGroup->id;
                                    }
                                    $displayExpense = $groupExpenses->first();
                                    $displayDate = $groupExpenses->sortByDesc('date')->first()->date;
                                    $displayAmount = $groupExpenses->sum(fn ($item) => $item->totalAmount());
                                    $displayMethods = $groupExpenses->pluck('payment_method')->unique()->implode(' + ');
                                @endphp
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4">
                                        @if($isGroup)
                                            <button type="button" class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full border border-pink-200 bg-pink-50 text-sm font-bold leading-none text-pink-700 transition hover:bg-pink-100 cursor-pointer" aria-label="Show individual expenses" aria-expanded="false" onclick="togglePersonalExpenseGroup('{{ $exp->personalExpenseGroup->id }}', this)">›</button>
                                            <span class="text-xs font-bold text-pink-700">{{ $groupExpenses->count() }} entries</span>
                                        @elseif(($status ?? 'active') === 'active')
                                            <input type="checkbox" name="expense_ids[]" value="{{ $exp->id }}" form="group-personal-expenses-form" class="rounded border-slate-300 text-pink-600 focus:ring-pink-500">
                                        @else
                                            <span class="text-slate-400 text-[10px]">#{{ $exp->id }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                        {{ $displayDate->format('d M Y') }}
                                        @if($displayExpense->time)
                                            <span class="block text-[10px] text-slate-400">{{ $displayExpense->time }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-semibold text-slate-900 dark:text-white block">
                                            {{ $isGroup ? $exp->personalExpenseGroup->name : ($exp->description ?: ($exp->category?->name ?? 'Personal Expense')) }}
                                        </span>
                                        @if($isGroup)
                                            <span class="text-[11px] text-slate-400 block mt-0.5">{{ $groupExpenses->count() }} grouped transactions · {{ $displayMethods }}</span>
                                        @elseif($exp->notes)
                                            <span class="text-[11px] text-slate-400 italic block mt-0.5">{{ $exp->notes }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-pink-50 dark:bg-pink-950/40 text-pink-700 dark:text-pink-300 border border-pink-200 dark:border-pink-900">
                                            {{ $displayExpense->category?->name ?? 'Personal' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-pink-600 text-sm whitespace-nowrap">
                                        ₹{{ number_format($displayAmount, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400 whitespace-nowrap font-medium">
                                        {{ $displayMethods ?: 'Cash' }}
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        @if($isGroup)
                                            <span class="text-slate-500 text-xs">Grouped</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 font-semibold text-slate-700 dark:text-slate-300 text-xs">
                                                <span>{{ $exp->done_by ?: 'Me' }}</span>
                                                @if($exp->done_to)
                                                    <span class="text-slate-400 font-normal">→</span>
                                                    <span class="text-pink-600 dark:text-pink-400">{{ $exp->done_to }}</span>
                                                @endif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        @if(!$isGroup)
                                            @if($exp->expense_id)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                    ✓ In Main Expenses
                                                </span>
                                            @else
                                                <div class="flex items-center gap-1.5">
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                                        Separate
                                                    </span>
                                                    @if(($status ?? 'active') === 'active')
                                                        <form action="{{ route('personal-expenses.convert-to-normal', $exp) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-900 hover:bg-slate-800 text-white transition shadow-xs cursor-pointer" title="Record this to Main Expenses & opening balance">
                                                                + Add to Main
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-slate-400 text-xs">Group</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        @if($isGroup)
                                            <button type="button" onclick="openPersonalGroupNameModal('{{ $exp->personalExpenseGroup->id }}', @js($exp->personalExpenseGroup->name))" class="mr-1.5 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 hover:border-pink-300 hover:text-pink-700 cursor-pointer" title="Edit group name">✎</button>
                                            @if(($status ?? 'active') === 'active')
                                                <form action="{{ route('personal-expense-groups.destroy', $exp->personalExpenseGroup) }}" method="POST" class="inline" onsubmit="return confirm('Ungroup these expenses into individual rows?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-amber-600 dark:text-amber-400 hover:underline font-semibold mr-1.5 cursor-pointer" title="Dissolve group into individual entries">Ungroup</button>
                                                </form>
                                            @endif
                                            <button type="button" class="font-bold text-pink-700 dark:text-pink-400 hover:underline cursor-pointer" aria-expanded="false" onclick="togglePersonalExpenseGroup('{{ $exp->personalExpenseGroup->id }}', this)">Details</button>
                                        @elseif(($status ?? 'active') === 'archived')
                                            <form action="{{ route('personal-expenses.restore', $exp) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-emerald-600 dark:text-emerald-400 hover:underline font-bold mr-2 cursor-pointer">
                                                    ↩ Restore
                                                </button>
                                            </form>
                                            <button type="button" onclick="openPersonalDeleteModal({{ $exp->id }}, @js($exp->description ?: 'Personal Expense'), {{ $exp->totalAmount() }}, @js($displayDate->format('d M Y')), true)" class="text-rose-600 hover:underline font-bold cursor-pointer">
                                                Delete
                                            </button>
                                        @else
                                            <button type="button" onclick="openEditPersonalModal({{ json_encode($exp) }})" class="text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition mr-2 cursor-pointer">
                                                Edit
                                            </button>
                                            <button type="button" onclick="openPersonalDeleteModal({{ $exp->id }}, @js($exp->description ?: 'Personal Expense'), {{ $exp->totalAmount() }}, @js($displayDate->format('d M Y')), false)" class="text-xs font-semibold text-rose-600 hover:underline cursor-pointer">
                                                Delete
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @if($isGroup)
                                    <tr id="personal-expense-group-details-{{ $exp->personalExpenseGroup->id }}" class="hidden">
                                        <td colspan="9" class="bg-slate-50/70 dark:bg-slate-800/30 p-0 border-y border-pink-100 dark:border-slate-700">
                                            <!-- Group Summary Strip -->
                                            <div class="grid grid-cols-2 gap-3 border-b border-pink-100 dark:border-slate-700 bg-pink-50/80 dark:bg-slate-800 p-4 text-xs sm:grid-cols-3">
                                                <div><span class="block text-slate-500">Group Total Spend</span><strong class="text-base text-pink-600 dark:text-pink-400">₹{{ number_format($displayAmount, 2) }}</strong></div>
                                                <div><span class="block text-slate-500">Payment Methods</span><strong class="text-slate-800 dark:text-slate-200">{{ $displayMethods }}</strong></div>
                                                <div><span class="block text-slate-500">Transaction Count</span><strong class="text-slate-800 dark:text-slate-200">{{ $groupExpenses->count() }} personal items</strong></div>
                                            </div>

                                            <!-- Nested Individual Entries Table -->
                                            <div class="p-3.5 space-y-2">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                                        <span>📋</span> Individual Entries in "{{ $exp->personalExpenseGroup->name }}" ({{ $groupExpenses->count() }})
                                                    </span>
                                                    <span class="text-[11px] text-slate-400">Edit or detach any item directly</span>
                                                </div>
                                                <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xs">
                                                    <table class="w-full text-left text-xs">
                                                        <thead class="bg-slate-50 dark:bg-slate-800 text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-700">
                                                            <tr>
                                                                <th class="py-2.5 px-3">Date</th>
                                                                <th class="py-2.5 px-3">Description</th>
                                                                <th class="py-2.5 px-3">Category</th>
                                                                <th class="py-2.5 px-3">Amount</th>
                                                                <th class="py-2.5 px-3">Method</th>
                                                                <th class="py-2.5 px-3">Done By / To</th>
                                                                <th class="py-2.5 px-3 text-right">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                                            @foreach($groupExpenses as $child)
                                                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                                                    <td class="py-2 px-3 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                                                        {{ $child->date->format('d M Y') }}
                                                                        @if($child->time)<span class="block text-[10px] text-slate-400">{{ $child->time }}</span>@endif
                                                                    </td>
                                                                    <td class="py-2 px-3 font-medium text-slate-900 dark:text-white">
                                                                        {{ $child->description ?: ($child->category?->name ?? 'Personal Expense') }}
                                                                        @if($child->notes)<span class="block text-[10px] text-slate-400">{{ $child->notes }}</span>@endif
                                                                    </td>
                                                                    <td class="py-2 px-3 whitespace-nowrap">
                                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-pink-50 dark:bg-pink-950/40 text-pink-700 dark:text-pink-300 border border-pink-200 dark:border-pink-900">
                                                                            {{ $child->category?->name ?? 'Personal' }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="py-2 px-3 font-bold text-pink-600 dark:text-pink-400 whitespace-nowrap">
                                                                        ₹{{ number_format($child->totalAmount(), 2) }}
                                                                    </td>
                                                                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                                                        {{ $child->payment_method ?: 'Cash' }}
                                                                    </td>
                                                                    <td class="py-2 px-3 whitespace-nowrap font-medium text-slate-700 dark:text-slate-300">
                                                                        <span>{{ $child->done_by ?: 'Me' }}</span>
                                                                        @if($child->done_to)
                                                                            <span class="text-slate-400 font-normal">→</span>
                                                                            <span class="text-pink-600 dark:text-pink-400">{{ $child->done_to }}</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="py-2 px-3 text-right whitespace-nowrap space-x-2">
                                                                        <button type="button" onclick="openEditPersonalModal({{ json_encode($child) }})" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 cursor-pointer">
                                                                            Edit
                                                                        </button>
                                                                        <form action="{{ route('personal-expense-groups.expenses.detach', [$exp->personalExpenseGroup, $child]) }}" method="POST" class="inline" onsubmit="return confirm('Detach this expense from group?');">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="text-amber-600 hover:text-amber-500 font-semibold text-xs cursor-pointer">Detach</button>
                                                                        </form>
                                                                        <button type="button" onclick="openPersonalDeleteModal({{ $child->id }}, @js($child->description ?: 'Personal Expense'), {{ $child->totalAmount() }}, @js($child->date->format('d M Y')), {{ $child->is_archived ? 'true' : 'false' }})" class="text-rose-600 hover:text-rose-500 font-semibold text-xs cursor-pointer">
                                                                            Delete
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($expenses->hasPages())
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                        {{ $expenses->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- EDIT GROUP NAME MODAL -->
        <div id="personal-group-name-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4" onclick="if(event.target === this) closePersonalGroupNameModal()">
            <div class="w-full max-w-sm rounded-3xl bg-white dark:bg-slate-900 p-5 shadow-2xl border border-slate-200 dark:border-slate-800" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Edit group name</h2>
                    <button type="button" onclick="closePersonalGroupNameModal()" class="text-xl text-slate-400 hover:text-slate-600 cursor-pointer" aria-label="Close">&times;</button>
                </div>
                <form id="personal-group-name-form" action="" method="POST" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <input id="personal-group-name-input" type="text" name="name" required maxlength="255" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2.5 text-sm text-slate-900 dark:text-white">
                    <button type="submit" class="w-full rounded-xl bg-pink-600 py-2.5 text-sm font-semibold text-white hover:bg-pink-700 cursor-pointer">Save group name</button>
                </form>
            </div>
        </div>

        <!-- 3-BUTTON DELETE / ARCHIVE CONFIRMATION MODAL -->
        <div id="personal-expense-action-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4" onclick="if(event.target === this) closePersonalDeleteModal()">
            <div class="w-full max-w-md rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl" id="personal-action-modal-icon">⚠️</span>
                        <h3 id="personal-action-modal-title" class="font-bold text-base text-slate-900 dark:text-white">Delete or Archive Personal Expense</h3>
                    </div>
                    <button type="button" onclick="closePersonalDeleteModal()" class="text-2xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">&times;</button>
                </div>

                <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200/60 dark:border-slate-700/60 space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Expense:</span>
                        <span id="personal-action-modal-desc" class="font-bold text-slate-900 dark:text-white truncate max-w-[220px]"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Amount & Date:</span>
                        <span class="font-bold text-pink-600 dark:text-pink-400"><span id="personal-action-modal-amount"></span> · <span id="personal-action-modal-date" class="text-slate-500 font-normal"></span></span>
                    </div>
                </div>

                <p id="personal-action-modal-help" class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Choose whether to move this personal expense to <strong>Archive/Historical</strong> (keeps history without affecting regular records) or <strong>Delete Permanently</strong>.
                </p>

                <!-- 3 Action Buttons -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-end gap-2.5">
                    <button type="button" onclick="closePersonalDeleteModal()" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer transition">
                        Cancel
                    </button>

                    <!-- Archive Form -->
                    <form id="personal-action-archive-form" action="" method="POST" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" id="personal-action-archive-btn" class="w-full px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5">
                            <span>📁</span> <span>Archive</span>
                        </button>
                    </form>

                    <!-- Permanent Delete Form -->
                    <form id="personal-action-delete-form" action="" method="POST" class="w-full sm:w-auto">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5">
                            <span>🗑️</span> <span>Delete Permanently</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ADD/EDIT MODAL (VANILLA JS, NO OVERLAPPING) -->
        <div id="personal-expense-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" onclick="if(event.target === this) closePersonalModal()">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🛍️</span>
                        <h3 id="personal-modal-title" class="font-bold text-base text-slate-900 dark:text-white">Add Personal Expense</h3>
                    </div>
                    <button type="button" onclick="closePersonalModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer">&times;</button>
                </div>

                <form id="personal-expense-form" action="{{ route('personal-expenses.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" id="personal-method-input" value="POST">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                        <select name="category_id" id="personal-category-input" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ strcasecmp($cat->name, 'Me') === 0 ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Amount (₹)</label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="personal-amount-input" required placeholder="0.00" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:bg-white focus:ring-2 focus:ring-pink-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Date</label>
                            <input type="date" name="date" id="personal-date-input" value="{{ date('Y-m-d') }}" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Time</label>
                            <input type="time" name="time" id="personal-time-input" value="{{ date('H:i') }}" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Description (e.g. Hotel with Family, Shopping, Spotify)</label>
                        <input type="text" name="description" id="personal-desc-input" placeholder="e.g. Weekend snacks or Shopping" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Method</label>
                        <select name="payment_method" id="personal-pm-input" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                            @foreach($paymentMethods as $pm)
                                <option value="{{ $pm }}">{{ $pm }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Done By & Done To Fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Done By</label>
                            <input type="text" name="done_by" id="personal-done-by-input" value="Me" placeholder="e.g. Me" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Done To (Optional)</label>
                            <input type="text" name="done_to" id="personal-done-to-input" placeholder="e.g. Mom, Brother, Self" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Notes (Optional)</label>
                        <textarea name="notes" id="personal-notes-input" rows="2" placeholder="Optional notes..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500"></textarea>
                    </div>

                    <!-- Dual Record Link Toggle Card -->
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-1.5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="record_as_normal_expense" id="personal-dual-input" value="1" class="rounded border-slate-300 text-pink-600 focus:ring-pink-500">
                            <span class="text-xs font-bold text-slate-900 dark:text-white">Also record as normal expense</span>
                        </label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Automatically creates and connects a matching expense entry in your regular expenses and updates opening balance.</p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" onclick="closePersonalModal()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-xs font-semibold shadow-md transition cursor-pointer">
                            Save Expense
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePersonalExpenseGroup(groupId, button) {
            const details = document.getElementById(`personal-expense-group-details-${groupId}`);
            if (!details) return;
            const isHidden = details.classList.toggle('hidden');
            const toggleButton = button.matches('button[aria-label]') ? button : button.closest('tr')?.querySelector('button[aria-label]');
            if (toggleButton) toggleButton.textContent = isHidden ? '›' : '⌄';
            button.setAttribute('aria-expanded', String(!isHidden));
        }

        function openPersonalGroupNameModal(groupId, groupName) {
            document.getElementById('personal-group-name-form').action = `/finance/personal-expense-groups/${groupId}`;
            document.getElementById('personal-group-name-input').value = groupName;
            const modal = document.getElementById('personal-group-name-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('personal-group-name-input').focus();
        }

        function closePersonalGroupNameModal() {
            const modal = document.getElementById('personal-group-name-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openPersonalDeleteModal(id, description, amount, date, isArchived) {
            document.getElementById('personal-action-modal-desc').textContent = description || 'Personal Expense';
            document.getElementById('personal-action-modal-amount').textContent = '₹' + Number(amount).toFixed(2);
            document.getElementById('personal-action-modal-date').textContent = date;
            
            const archiveBtn = document.getElementById('personal-action-archive-btn');
            const archiveForm = document.getElementById('personal-action-archive-form');
            const deleteForm = document.getElementById('personal-action-delete-form');
            const helpText = document.getElementById('personal-action-modal-help');
            const modalTitle = document.getElementById('personal-action-modal-title');
            
            deleteForm.action = `/finance/personal-expenses/${id}`;

            if (isArchived) {
                modalTitle.textContent = 'Permanently Delete Personal Expense?';
                archiveForm.action = `/finance/personal-expenses/${id}/restore`;
                archiveBtn.innerHTML = '<span>↩</span> <span>Restore to Active</span>';
                archiveBtn.className = 'w-full px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5';
                helpText.innerHTML = 'This expense is currently in <strong>Archived/Historical</strong>. You can restore it to active tracking or permanently delete it from the database.';
            } else {
                modalTitle.textContent = 'Delete or Archive Personal Expense';
                archiveForm.action = `/finance/personal-expenses/${id}/archive`;
                archiveBtn.innerHTML = '<span>📁</span> <span>Archive</span>';
                archiveBtn.className = 'w-full px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5';
                helpText.innerHTML = 'Choose whether to move this personal expense to <strong>Archive/Historical</strong> (keeps history without affecting regular dashboard/statements) or <strong>Delete Permanently</strong>.';
            }
            
            const modal = document.getElementById('personal-expense-action-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closePersonalDeleteModal() {
            const modal = document.getElementById('personal-expense-action-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openPersonalModal() {
            document.getElementById('personal-modal-title').textContent = 'Add Personal Expense';
            document.getElementById('personal-expense-form').action = "{{ route('personal-expenses.store') }}";
            document.getElementById('personal-method-input').value = 'POST';
            document.getElementById('personal-amount-input').value = '';
            document.getElementById('personal-desc-input').value = '';
            document.getElementById('personal-done-by-input').value = 'Me';
            document.getElementById('personal-done-to-input').value = '';
            document.getElementById('personal-notes-input').value = '';
            document.getElementById('personal-date-input').value = "{{ date('Y-m-d') }}";
            document.getElementById('personal-time-input').value = "{{ date('H:i') }}";
            document.getElementById('personal-dual-input').checked = false;
            
            const meOption = Array.from(document.getElementById('personal-category-input').options).find(o => o.text.trim().toLowerCase() === 'me');
            if (meOption) {
                document.getElementById('personal-category-input').value = meOption.value;
            }
            
            const modal = document.getElementById('personal-expense-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function openEditPersonalModal(exp) {
            document.getElementById('personal-modal-title').textContent = 'Edit Personal Expense';
            document.getElementById('personal-expense-form').action = '/finance/personal-expenses/' + exp.id;
            document.getElementById('personal-method-input').value = 'PUT';
            
            if (exp.category_id) {
                document.getElementById('personal-category-input').value = exp.category_id;
            }
            document.getElementById('personal-amount-input').value = exp.amount;
            document.getElementById('personal-date-input').value = exp.date ? exp.date.substring(0, 10) : "{{ date('Y-m-d') }}";
            document.getElementById('personal-time-input').value = exp.time ? exp.time.substring(0, 5) : "{{ date('H:i') }}";
            document.getElementById('personal-desc-input').value = exp.description || '';
            document.getElementById('personal-done-by-input').value = exp.done_by || 'Me';
            document.getElementById('personal-done-to-input').value = exp.done_to || '';
            if (exp.payment_method) {
                document.getElementById('personal-pm-input').value = exp.payment_method;
            }
            document.getElementById('personal-notes-input').value = exp.notes || '';
            document.getElementById('personal-dual-input').checked = !!exp.expense_id;

            const modal = document.getElementById('personal-expense-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closePersonalModal() {
            const modal = document.getElementById('personal-expense-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</x-app-layout>

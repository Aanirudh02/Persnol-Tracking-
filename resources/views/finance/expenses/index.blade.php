<x-app-layout title="Expenses">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="expense-page-title text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Expenses</h1>
                <p class="text-xs text-slate-500">Total recorded: <span class="font-bold text-rose-600">₹{{ number_format($ownedTotalAmount ?? $totalAmount, 2) }}</span>@if(($totalFriendPaid ?? 0) > 0) <span class="text-slate-400 font-normal">(excludes ₹{{ number_format($totalFriendPaid, 2) }} non-owned)</span>@endif</p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('statements.create', ['type' => 'normal']) }}" style="background-color: #0d9488; color: #ffffff;" class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-bold text-xs shadow-md shadow-teal-600/20 flex items-center gap-1.5 transition active:scale-95">
                    <span>📄</span> Take Statement
                </a>
                <button type="button" onclick="openCalcModal()" style="background-color: #7c3aed; color: #ffffff;" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/20 flex items-center gap-1.5 transition active:scale-95 cursor-pointer">
                    <span>🧮</span> Calculate by Category
                </button>
                <a href="{{ route('expenses.create') }}" style="background-color: #0284c7; color: #ffffff;" class="px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-md shadow-sky-600/20 flex items-center gap-1.5 transition active:scale-95">
                    <span>+</span> Add Expense
                </a>
            </div>
        </div>

        <!-- Metric Summary Cards (Compact 3-Card Grid) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
            <!-- Card 1: Total Expense -->
            <div class="rounded-2xl border border-rose-100 dark:border-rose-950/40 bg-white dark:bg-slate-900 p-4 shadow-sm flex flex-col justify-between hover:shadow transition">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-rose-50 dark:bg-rose-950/60 text-rose-600 text-xs">💰</span>
                            Total Expense
                        </span>
                        @if(request()->filled('category_id') || request()->filled('payment_method') || request()->filled('from_date') || request()->filled('to_date') || request()->boolean('voluntary_only'))
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-600">Filtered</span>
                        @else
                            <span class="text-[10px] font-medium text-slate-400">All recorded</span>
                        @endif
                    </div>
                    <div class="mt-2 text-2xl font-black tracking-tight text-rose-600 dark:text-rose-400">
                        ₹{{ number_format($ownedTotalAmount ?? $totalAmount, 2) }}
                    </div>
                    @if(($totalFriendPaid ?? 0) > 0)
                        <div class="mt-2 flex items-center justify-between text-[11px] font-medium bg-slate-50 dark:bg-slate-800/80 px-2.5 py-1 rounded-lg border border-slate-200/60 dark:border-slate-700/60">
                            <span class="text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">👥</span> Non-owned (Friend paid):
                            </span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-400">₹{{ number_format($totalFriendPaid, 2) }}</span>
                        </div>
                    @endif
                </div>
                <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500">
                    <span>{{ $totalCount }} transaction(s)</span>
                    @if(request()->filled('category_id') || request()->filled('payment_method') || request()->filled('from_date') || request()->filled('to_date'))
                        <a href="{{ route('expenses.index') }}" class="text-[10px] font-semibold text-rose-600 hover:underline">Clear Filter</a>
                    @else
                        <span class="text-[10px] text-slate-400">Excludes non-owned</span>
                    @endif
                </div>
            </div>

            <!-- Card 2: Expense by Payment Method -->
            <div class="rounded-2xl border border-sky-100 dark:border-sky-950/40 bg-white dark:bg-slate-900 p-4 shadow-sm flex flex-col justify-between hover:shadow transition">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-sky-50 dark:bg-sky-950/60 text-sky-600 text-xs">💳</span>
                            Expense by Payment
                        </span>
                        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-0.5 rounded-lg text-[10px] font-bold">
                            <button type="button" id="pay-btn-owned" onclick="switchPaymentCardTab('owned')" class="px-2 py-0.5 rounded-md bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-xs cursor-pointer">Done by me</button>
                            <button type="button" id="pay-btn-non-owned" onclick="switchPaymentCardTab('non_owned')" class="px-2 py-0.5 rounded-md text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 cursor-pointer">Non-owned</button>
                        </div>
                    </div>

                    <!-- 1. Done by Me (Owned) List -->
                    <div id="payment-card-owned" class="mt-2 space-y-1.5 max-h-24 overflow-y-auto pr-1">
                        @php $ownedCount = 0; @endphp
                        @foreach($expensesByPayment as $method => $data)
                            @php
                                $owned = is_array($data) ? ($data['owned'] ?? 0) : $data;
                            @endphp
                            @if($owned > 0)
                                @php $ownedCount++; @endphp
                                <div class="flex items-center justify-between text-xs py-1 border-b border-slate-50 dark:border-slate-800/40 last:border-b-0">
                                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                                        @if(stripos($method, 'upi') !== false)
                                            <span class="text-xs">⚡</span> <span class="font-semibold">UPI</span>
                                        @elseif(stripos($method, 'cash') !== false || stripos($method, 'money') !== false)
                                            <span class="text-xs">💵</span> <span>Cash / Money</span>
                                        @elseif(stripos($method, 'card') !== false)
                                            <span class="text-xs">💳</span> <span>Card</span>
                                        @else
                                            <span class="text-xs">🏦</span> <span>{{ $method }}</span>
                                        @endif
                                    </span>
                                    <span class="font-bold text-slate-900 dark:text-white">₹{{ number_format($owned, 2) }}</span>
                                </div>
                            @endif
                        @endforeach
                        @if($ownedCount === 0)
                            <div class="text-[11px] text-slate-400 py-1">No self-paid expenses found</div>
                        @endif
                    </div>

                    <!-- 2. Non-Owned (Friend-Paid / Split) List -->
                    <div id="payment-card-non-owned" class="hidden mt-2 space-y-1.5 max-h-24 overflow-y-auto pr-1">
                        @php $friendCount = 0; @endphp
                        @foreach($expensesByPayment as $method => $data)
                            @php
                                $friend = is_array($data) ? ($data['friend'] ?? 0) : 0;
                            @endphp
                            @if($friend > 0)
                                @php $friendCount++; @endphp
                                <div class="flex items-center justify-between text-xs py-1 border-b border-slate-50 dark:border-slate-800/40 last:border-b-0">
                                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                                        <span class="text-xs">👥</span> <span>{{ $method }}</span>
                                    </span>
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">₹{{ number_format($friend, 2) }}</span>
                                </div>
                            @endif
                        @endforeach
                        @if($friendCount === 0 && ($totalFriendPaid ?? 0) > 0)
                            @foreach($friendPaidBreakdown as $fName => $fAmt)
                                <div class="flex items-center justify-between text-xs py-1 border-b border-slate-50 dark:border-slate-800/40 last:border-b-0">
                                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                                        <span class="text-xs">👤</span> <span>{{ $fName }} (Split)</span>
                                    </span>
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">₹{{ number_format($fAmt, 2) }}</span>
                                </div>
                            @endforeach
                        @elseif($friendCount === 0)
                            <div class="text-[11px] text-slate-400 py-1">No non-owned friend expenses</div>
                        @endif
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500">
                    <span id="payment-card-stat">Self: ₹{{ number_format($ownedTotalAmount ?? $totalAmount, 2) }}</span>
                    <span class="text-[10px] text-sky-600 font-semibold cursor-pointer" onclick="togglePaymentCardTabDirect()">⇄ Switch View</span>
                </div>
            </div>

            <!-- Card 3: Non-Owned / Friend-Paid Expenses -->
            <div class="rounded-2xl border border-indigo-100 dark:border-indigo-950/40 bg-white dark:bg-slate-900 p-4 shadow-sm flex flex-col justify-between hover:shadow transition">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 text-xs">👥</span>
                            Non-Owned Expenses
                        </span>
                        <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">Friend Paid</span>
                    </div>
                    <div class="mt-1 flex items-baseline justify-between">
                        <span class="text-[11px] text-slate-500">Friends paid total:</span>
                        <span class="text-base font-black text-indigo-600 dark:text-indigo-400">₹{{ number_format($totalFriendPaid, 2) }}</span>
                    </div>
                    <div class="mt-1 space-y-1 max-h-16 overflow-y-auto pr-1">
                        @forelse($friendPaidBreakdown as $friendName => $paidAmount)
                            <div class="flex items-center justify-between text-xs py-0.5 border-b border-slate-50 dark:border-slate-800/40 last:border-b-0">
                                <span class="inline-flex items-center gap-1 text-slate-700 dark:text-slate-300 font-medium truncate max-w-[130px]" title="{{ $friendName }}">
                                    <span class="text-[11px]">👤</span> {{ $friendName }}
                                </span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                    - ₹{{ number_format($paidAmount, 2) }} paid
                                </span>
                            </div>
                        @empty
                            <div class="text-[11px] text-slate-400 py-1 flex items-center gap-1">
                                <span>✓</span> No friend-paid expenses (100% self)
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500">
                    <span>{{ count($friendPaidBreakdown) }} friend(s) paid</span>
                    <a href="{{ route('friends.index') }}" class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">Friends Balance →</a>
                </div>
            </div>
        </div>

        <!-- Active vs Archived Navigation Tabs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
            <div class="flex items-center gap-2">
                <a href="{{ route('expenses.index', array_merge(request()->except(['page', 'status']), ['status' => 'active'])) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ ($status ?? 'active') === 'active' ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span>💸 Active Expenses</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] {{ ($status ?? 'active') === 'active' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $activeCount }}</span>
                </a>
                <a href="{{ route('expenses.index', array_merge(request()->except(['page', 'status']), ['status' => 'archived'])) }}"
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
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-sky-100 dark:border-slate-800 shadow-sm">
            <form action="{{ route('expenses.index') }}" method="GET" class="space-y-3 text-xs">
                <input type="hidden" name="status" value="{{ $status ?? 'active' }}">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-slate-400 mb-1">Category</label>
                        <select name="category_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                            <option value="">All Categories</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">Method</label>
                        <select name="payment_method" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                            <option value="">All Methods</option>
                            @foreach($paymentMethods as $m)
                                <option value="{{ $m }}" {{ request('payment_method') == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
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
                    <a href="{{ route('expenses.index', ['status' => $status ?? 'active']) }}" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold transition">
                        🔄 Reset
                    </a>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold transition shadow-sm cursor-pointer">
                        🔍 Search / Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Expenses Table / List -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            @if(($status ?? 'active') === 'active')
                <form id="group-expenses-form" action="{{ route('expenses.group') }}" method="POST" class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 p-4 text-xs">
                    @csrf
                    <button type="submit" class="px-3 py-2 rounded-xl bg-sky-600 text-white font-semibold cursor-pointer">Group selected expenses</button>
                    <span class="text-slate-400">Select any two or more expenses that are not already in a group.</span>
                </form>
            @else
                <div class="flex items-center justify-between border-b border-amber-100 dark:border-amber-900/40 bg-amber-50/50 dark:bg-amber-950/20 p-4 text-xs">
                    <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300 font-semibold">
                        <span>📁</span>
                        <span>Archived / Historical Records ({{ $archivedCount }})</span>
                    </div>
                    <span class="text-slate-400 text-[11px]">Click "Restore" to move back to active, or "Delete" to permanently remove.</span>
                </div>
            @endif
            @if($expenses->isEmpty())
                <div class="text-center py-16 text-slate-400 text-xs">
                    <span class="text-3xl block mb-2">💸</span>
                    No expense records found.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="py-3.5 px-4">Group</th>
                                <th class="py-3.5 px-4">Date</th>
                                <th class="py-3.5 px-4">Description</th>
                                <th class="py-3.5 px-4">Category</th>
                                <th class="py-3.5 px-4">Amount</th>
                                <th class="py-3.5 px-4">Method</th>
                                <th class="py-3.5 px-4">Paid By</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @php $shownGroupIds = []; @endphp
                            @foreach($expenses as $exp)
                                @php
                                    $isGroup = $exp->expenseGroup !== null;
                                    if ($isGroup && in_array($exp->expenseGroup->id, $shownGroupIds, true)) {
                                        continue;
                                    }
                                    $groupExpenses = $isGroup ? $exp->expenseGroup->expenses : collect([$exp]);
                                    if ($isGroup) {
                                        $shownGroupIds[] = $exp->expenseGroup->id;
                                    }
                                    $displayExpense = $groupExpenses->first();
                                    $displayDate = $groupExpenses->sortByDesc('date')->first()->date;
                                    $displayAmount = $groupExpenses->sum(fn ($item) => $item->totalAmount());
                                    $displayMethods = $groupExpenses->pluck('payment_method')->unique()->implode(' + ');
                                    $displayPaidBy = $groupExpenses->pluck('paid_by')->unique()->implode(' + ');
                                    $friendsPaid = $groupExpenses->sum(fn ($item) => $item->totalPaidByFriends());
                                    $myPaid = max(0, $displayAmount - $friendsPaid);
                                    $displayBreakdown = $isGroup
                                        ? $groupExpenses->groupBy('payment_method')->map(fn ($items, $method) => $method.' ₹'.number_format($items->sum(fn ($item) => $item->totalAmount()), 2))->implode(' · ')
                                        : null;
                                    $isEditable = $displayExpense->isEditableByUser();
                                @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4">
                                        @if($isGroup)
                                            <button type="button" class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full border border-sky-200 bg-sky-50 text-sm font-bold leading-none text-sky-700 transition hover:bg-sky-100 cursor-pointer" aria-label="Show individual expenses" aria-expanded="false" onclick="toggleExpenseGroup('{{ $exp->expenseGroup->id }}', this)">›</button>
                                            <span class="text-xs font-bold text-sky-700">{{ $groupExpenses->count() }} entries</span>
                                        @else
                                            <input type="checkbox" name="expense_ids[]" value="{{ $exp->id }}" form="group-expenses-form" class="rounded border-slate-300">
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                        {{ $displayDate->format('d M Y') }}
                                        <span class="block text-[10px] text-slate-400">{{ $displayExpense->time }}</span>
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                        <a href="{{ route('expenses.show', $displayExpense) }}" class="font-semibold text-slate-900 dark:text-white hover:underline">{{ $isGroup ? $exp->expenseGroup->name : $exp->description }}</a>
                                        @if(!$isGroup && $exp->parent)
                                            <span class="block text-[10px] font-normal text-slate-400">Sub-expense under: {{ $exp->parent->description }}</span>
                                        @endif
                                        @if($isGroup)
                                            <span class="mt-1 block text-xs font-normal text-slate-500">{{ $groupExpenses->count() }} transactions · {{ $displayMethods }}</span>
                                        @elseif($exp->receipt_image)
                                            <a href="{{ asset('storage/' . $exp->receipt_image) }}" target="_blank" class="inline-block ml-1 text-indigo-500 hover:underline text-[10px]">📷 receipt</a>
                                        @endif
                                        @if(!$isGroup && $exp->friendSplits->isNotEmpty())
                                            <span class="block text-[10px] font-medium text-indigo-600 dark:text-indigo-400">
                                                👥 Split: {{ $exp->friendSplits->map(fn ($s) => ($s->friend?->name ?? 'Friend') . ' (share ₹' . number_format($s->friend_share, 2) . ($s->paid_by_friend_amount > 0 ? ', paid ₹' . number_format($s->paid_by_friend_amount, 2) : '') . ')')->implode(', ') }}
                                            </span>
                                        @elseif(!$isGroup && $exp->friendSplit)
                                            <span class="block text-[10px] font-medium text-indigo-600 dark:text-indigo-400">
                                                👥 Split with {{ $exp->friendSplit->friend?->name }} (share ₹{{ number_format($exp->friendSplit->friend_share, 2) }}{{ $exp->friendSplit->paid_by_friend_amount > 0 ? ', paid ₹' . number_format($exp->friendSplit->paid_by_friend_amount, 2) : '' }})
                                            </span>
                                        @endif
                                        @if(!$isGroup && $exp->notes)
                                            <span class="block text-[10px] text-slate-400 font-normal">{{ $exp->notes }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ $displayExpense->category?->name ?? 'Other' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-rose-600 dark:text-rose-400 text-sm whitespace-nowrap">
                                        @if($isGroup)
                                            ₹{{ number_format($myPaid, 2) }} <span class="block text-[10px] font-normal text-slate-400">your spend</span>
                                        @elseif($friendsPaid > 0)
                                            ₹{{ number_format($myPaid, 2) }}
                                        @else
                                            ₹{{ number_format($displayAmount, 2) }}
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                                        @if($isGroup)
                                            <div class="flex flex-wrap gap-1.5">@foreach(explode(' + ', $displayMethods) as $method)<span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-1 text-[10px] font-semibold">{{ $method }}</span>@endforeach</div>
                                        @else
                                            {{ $displayMethods }}
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                                        @if($isGroup)
                                            <span class="block font-semibold text-emerald-700 dark:text-emerald-400">You ₹{{ number_format($myPaid, 2) }}</span>
                                            @if($friendsPaid > 0)<span class="block text-[10px] text-indigo-600 dark:text-indigo-400">Friends ₹{{ number_format($friendsPaid, 2) }}</span>@endif
                                        @else
                                            {{ $displayPaidBy }}
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        @if($isGroup)
                                            <button type="button" onclick="openGroupNameModal('{{ $exp->expenseGroup->id }}', @js($exp->expenseGroup->name))" class="mr-1.5 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 hover:border-sky-300 hover:text-sky-700 cursor-pointer" title="Edit group name">✎</button>
                                            @if(($status ?? 'active') === 'active')
                                                <form action="{{ route('expense-groups.destroy', $exp->expenseGroup) }}" method="POST" class="inline" onsubmit="return confirm('Ungroup these expenses into individual rows?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-amber-600 dark:text-amber-400 hover:underline font-semibold mr-1.5 cursor-pointer" title="Dissolve group into individual entries">Ungroup</button>
                                                </form>
                                            @endif
                                            <button type="button" class="font-bold text-sky-700 dark:text-sky-400 hover:underline cursor-pointer" aria-expanded="false" onclick="toggleExpenseGroup('{{ $exp->expenseGroup->id }}', this)">Details</button>
                                        @elseif(($status ?? 'active') === 'archived')
                                            <form action="{{ route('expenses.restore', $exp) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-emerald-600 dark:text-emerald-400 hover:underline font-bold mr-2 cursor-pointer">
                                                    ↩ Restore
                                                </button>
                                            </form>
                                            <button type="button" onclick="openExpenseDeleteModal({{ $exp->id }}, @js($exp->description), {{ $exp->totalAmount() }}, @js($displayDate->format('d M Y')), true)" class="text-rose-600 hover:underline font-bold cursor-pointer">
                                                Delete
                                            </button>
                                        @elseif($isEditable)
                                            <a href="{{ route('expenses.edit', $exp) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 font-semibold mr-2">Edit</a>
                                            <button type="button" onclick="openExpenseDeleteModal({{ $exp->id }}, @js($exp->description), {{ $exp->totalAmount() }}, @js($displayDate->format('d M Y')), false)" class="text-rose-600 hover:underline font-semibold cursor-pointer">
                                                Delete
                                            </button>
                                        @else
                                            <span class="text-slate-400 text-[11px] font-medium" title="Edit window expired">🔒 Locked</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($isGroup)
                                    <tr id="expense-group-details-{{ $exp->expenseGroup->id }}" class="hidden">
                                        <td colspan="8" class="bg-slate-50/70 dark:bg-slate-800/30 p-0 border-y border-sky-100 dark:border-slate-700">
                                            <!-- Group Summary Strip -->
                                            <div class="grid grid-cols-2 gap-3 border-b border-sky-100 dark:border-slate-700 bg-sky-50/80 dark:bg-slate-800 p-4 text-xs sm:grid-cols-4">
                                                <div><span class="block text-slate-500">Group Total</span><strong class="text-base text-slate-900 dark:text-white">₹{{ number_format($displayAmount, 2) }}</strong></div>
                                                <div><span class="block text-slate-500">Your Spend</span><strong class="text-base text-emerald-700 dark:text-emerald-400">₹{{ number_format($myPaid, 2) }}</strong></div>
                                                <div><span class="block text-slate-500">Friends Paid</span><strong class="text-base text-indigo-700 dark:text-indigo-400">₹{{ number_format($friendsPaid, 2) }}</strong></div>
                                                <div><span class="block text-slate-500">Payment Breakdown</span><strong class="text-slate-800 dark:text-slate-200">{{ $displayBreakdown }}</strong></div>
                                            </div>

                                            <!-- Nested Individual Entries Table -->
                                            <div class="p-3.5 space-y-2">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                                        <span>📋</span> Individual Entries in "{{ $exp->expenseGroup->name }}" ({{ $groupExpenses->count() }})
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
                                                                <th class="py-2.5 px-3">Paid By</th>
                                                                <th class="py-2.5 px-3 text-right">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                                            @foreach($groupExpenses as $child)
                                                                @php
                                                                    $childFriendsPaid = $child->totalPaidByFriends();
                                                                    $childMyPaid = max(0, $child->totalAmount() - $childFriendsPaid);
                                                                @endphp
                                                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                                                    <td class="py-2 px-3 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                                                        {{ $child->date->format('d M Y') }}
                                                                        @if($child->time)<span class="block text-[10px] text-slate-400">{{ $child->time }}</span>@endif
                                                                    </td>
                                                                    <td class="py-2 px-3 font-medium text-slate-900 dark:text-white">
                                                                        {{ $child->description }}
                                                                        @if($child->notes)<span class="block text-[10px] text-slate-400">{{ $child->notes }}</span>@endif
                                                                        @if($child->friendSplits->isNotEmpty())
                                                                            <span class="block text-[10px] text-indigo-600 dark:text-indigo-400">
                                                                                Split: {{ $child->friendSplits->map(fn ($s) => ($s->friend?->name ?? 'Friend') . ' (₹' . number_format($s->friend_share, 2) . ')')->implode(', ') }}
                                                                            </span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="py-2 px-3 whitespace-nowrap">
                                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                                            {{ $child->category?->name ?? 'Other' }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="py-2 px-3 font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                                                        ₹{{ number_format($childMyPaid, 2) }}
                                                                    </td>
                                                                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                                                        {{ $child->payment_method ?: 'Cash' }}
                                                                    </td>
                                                                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                                                        {{ $child->paid_by ?: 'Me' }}
                                                                    </td>
                                                                    <td class="py-2 px-3 text-right whitespace-nowrap space-x-2">
                                                                        <a href="{{ route('expenses.edit', $child) }}" class="text-indigo-600 hover:text-indigo-500 font-semibold text-xs">Edit</a>
                                                                        <form action="{{ route('expense-groups.expenses.detach', [$exp->expenseGroup, $child]) }}" method="POST" class="inline" onsubmit="return confirm('Detach this expense from group?');">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="text-amber-600 hover:text-amber-500 font-semibold text-xs cursor-pointer">Detach</button>
                                                                        </form>
                                                                        <button type="button" onclick="openExpenseDeleteModal({{ $child->id }}, @js($child->description), {{ $child->totalAmount() }}, @js($child->date->format('d M Y')), {{ $child->is_archived ? 'true' : 'false' }})" class="text-rose-600 hover:text-rose-500 font-semibold text-xs cursor-pointer">
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
                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>

        <!-- EDIT GROUP NAME MODAL -->
        <div id="group-name-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4" onclick="if(event.target === this) closeGroupNameModal()">
            <div class="w-full max-w-sm rounded-3xl bg-white dark:bg-slate-900 p-5 shadow-2xl border border-slate-200 dark:border-slate-800" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Edit group name</h2>
                    <button type="button" onclick="closeGroupNameModal()" class="text-xl text-slate-400 hover:text-slate-600 cursor-pointer" aria-label="Close">&times;</button>
                </div>
                <form id="group-name-form" action="" method="POST" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <input id="group-name-input" type="text" name="name" required maxlength="255" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2.5 text-sm text-slate-900 dark:text-white">
                    <button type="submit" class="w-full rounded-xl bg-sky-600 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 cursor-pointer">Save group name</button>
                </form>
            </div>
        </div>

        <!-- 3-BUTTON DELETE / ARCHIVE CONFIRMATION MODAL -->
        <div id="expense-action-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4" onclick="if(event.target === this) closeExpenseActionModal()">
            <div class="w-full max-w-md rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl" id="action-modal-icon">⚠️</span>
                        <h3 id="action-modal-title" class="font-bold text-base text-slate-900 dark:text-white">Delete or Archive Expense</h3>
                    </div>
                    <button type="button" onclick="closeExpenseActionModal()" class="text-2xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">&times;</button>
                </div>

                <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200/60 dark:border-slate-700/60 space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Expense:</span>
                        <span id="action-modal-desc" class="font-bold text-slate-900 dark:text-white truncate max-w-[220px]"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Amount & Date:</span>
                        <span class="font-bold text-rose-600 dark:text-rose-400"><span id="action-modal-amount"></span> · <span id="action-modal-date" class="text-slate-500 font-normal"></span></span>
                    </div>
                </div>

                <p id="action-modal-help" class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Choose whether to move this expense to <strong>Archive/Historical</strong> or <strong>Delete Permanently</strong>.
                </p>

                <!-- 3 Action Buttons -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-end gap-2.5">
                    <button type="button" onclick="closeExpenseActionModal()" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer transition">
                        Cancel
                    </button>

                    <!-- Archive Form -->
                    <form id="action-archive-form" action="" method="POST" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" id="action-archive-btn" class="w-full px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5">
                            <span>📁</span> <span>Archive</span>
                        </button>
                    </form>

                    <!-- Permanent Delete Form -->
                    <form id="action-delete-form" action="" method="POST" class="w-full sm:w-auto">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5">
                            <span>🗑️</span> <span>Delete Permanently</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- CALCULATE EXPENSE BY CATEGORY MODAL (PURE VANILLA JS AJAX) -->
        <div id="calc-category-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto" onclick="if(event.target === this) closeCalcModal()">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 max-w-lg w-full border border-slate-200 dark:border-slate-800 shadow-2xl space-y-5 my-8" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🧮</span>
                        <h3 class="font-bold text-base text-slate-900 dark:text-white">Calculate Expenses by Category</h3>
                    </div>
                    <button type="button" onclick="closeCalcModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer">&times;</button>
                </div>

                <!-- Parameters Controls -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-purple-50/60 dark:bg-slate-800/50 p-4 rounded-2xl border border-purple-100 dark:border-slate-700">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Time Period</label>
                        <select id="calc-period-select" onchange="onCalcPeriodChange(this.value)" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                            <option value="month" selected>Month (This Month)</option>
                            <option value="day">Day (Today)</option>
                            <option value="week">Weekly</option>
                            <option value="year">Yearly</option>
                            <option value="custom">Custom Period</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Category Filter</label>
                        <select id="calc-category-select" onchange="fetchCategoryCalc()" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                            <option value="all">All Categories</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="calc-custom-dates-box" class="hidden col-span-1 sm:col-span-2 grid grid-cols-2 gap-2 pt-2 border-t border-purple-200/60 dark:border-slate-700">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Start Date</label>
                            <input type="date" id="calc-from-date" onchange="fetchCategoryCalc()" class="w-full text-xs px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">End Date</label>
                            <input type="date" id="calc-to-date" onchange="fetchCategoryCalc()" class="w-full text-xs px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Results Output Area -->
                <div class="space-y-4">
                    <div id="calc-loading" class="py-8 text-center text-slate-400 text-xs flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-purple-600" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span>Calculating category expenses...</span>
                    </div>

                    <div id="calc-results-container" class="hidden space-y-4">
                        <!-- Overall Banner -->
                        <div class="p-4 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white flex items-center justify-between shadow-md">
                            <div>
                                <span class="block text-[10px] font-bold uppercase tracking-wider text-purple-200">Total Calculated Spend</span>
                                <span id="calc-overall-total" class="text-2xl font-black">₹0.00</span>
                            </div>
                            <div class="text-right">
                                <span id="calc-item-count" class="text-xs font-semibold block text-purple-100">0 items</span>
                                <span id="calc-period-badge" class="text-[10px] text-purple-200 capitalize">month range</span>
                            </div>
                        </div>

                        <!-- Category Breakdown List -->
                        <div id="calc-breakdown-list" class="space-y-2.5 max-h-60 overflow-y-auto pr-1"></div>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeCalcModal()" class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-200 cursor-pointer">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleExpenseGroup(groupId, button) {
            const details = document.getElementById(`expense-group-details-${groupId}`);
            const isHidden = details.classList.toggle('hidden');
            const toggleButton = button.matches('button[aria-label]') ? button : button.closest('tr')?.querySelector('button[aria-label]');
            if (toggleButton) toggleButton.textContent = isHidden ? '›' : '⌄';
            button.setAttribute('aria-expanded', String(!isHidden));
        }

        function switchPaymentCardTab(tab) {
            const ownedList = document.getElementById('payment-card-owned');
            const nonOwnedList = document.getElementById('payment-card-non-owned');
            const btnOwned = document.getElementById('pay-btn-owned');
            const btnNonOwned = document.getElementById('pay-btn-non-owned');
            const stat = document.getElementById('payment-card-stat');
            
            if (!ownedList || !nonOwnedList) return;
            
            if (tab === 'owned') {
                ownedList.classList.remove('hidden');
                nonOwnedList.classList.add('hidden');
                btnOwned.className = 'px-2 py-0.5 rounded-md bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-xs cursor-pointer font-bold';
                btnNonOwned.className = 'px-2 py-0.5 rounded-md text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 cursor-pointer';
                if (stat) stat.textContent = 'Self: ₹{{ number_format($ownedTotalAmount ?? $totalAmount, 2) }}';
            } else {
                ownedList.classList.add('hidden');
                nonOwnedList.classList.remove('hidden');
                btnNonOwned.className = 'px-2 py-0.5 rounded-md bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-xs cursor-pointer font-bold';
                btnOwned.className = 'px-2 py-0.5 rounded-md text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 cursor-pointer';
                if (stat) stat.textContent = 'Non-owned: ₹{{ number_format($totalFriendPaid ?? 0, 2) }}';
            }
        }

        function togglePaymentCardTabDirect() {
            const ownedList = document.getElementById('payment-card-owned');
            if (ownedList) {
                switchPaymentCardTab(ownedList.classList.contains('hidden') ? 'owned' : 'non_owned');
            }
        }

        function openGroupNameModal(groupId, groupName) {
            document.getElementById('group-name-form').action = `/finance/expense-groups/${groupId}`;
            document.getElementById('group-name-input').value = groupName;
            const modal = document.getElementById('group-name-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('group-name-input').focus();
        }

        function closeGroupNameModal() {
            const modal = document.getElementById('group-name-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openExpenseDeleteModal(id, description, amount, date, isArchived) {
            document.getElementById('action-modal-desc').textContent = description || 'Expense';
            document.getElementById('action-modal-amount').textContent = '₹' + Number(amount).toFixed(2);
            document.getElementById('action-modal-date').textContent = date;
            
            const archiveBtn = document.getElementById('action-archive-btn');
            const archiveForm = document.getElementById('action-archive-form');
            const deleteForm = document.getElementById('action-delete-form');
            const helpText = document.getElementById('action-modal-help');
            const modalTitle = document.getElementById('action-modal-title');
            
            deleteForm.action = `/finance/expenses/${id}`;

            if (isArchived) {
                modalTitle.textContent = 'Permanently Delete Expense?';
                archiveForm.action = `/finance/expenses/${id}/restore`;
                archiveBtn.innerHTML = '<span>↩</span> <span>Restore to Active</span>';
                archiveBtn.className = 'w-full px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5';
                helpText.innerHTML = 'This expense is currently in <strong>Archived/Historical</strong>. You can restore it to active tracking or permanently delete it from the database.';
            } else {
                modalTitle.textContent = 'Delete or Archive Expense';
                archiveForm.action = `/finance/expenses/${id}/archive`;
                archiveBtn.innerHTML = '<span>📁</span> <span>Archive</span>';
                archiveBtn.className = 'w-full px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5';
                helpText.innerHTML = 'Choose whether to move this expense to <strong>Archive/Historical</strong> (keeps history without affecting regular dashboard/statements) or <strong>Delete Permanently</strong>.';
            }
            
            const modal = document.getElementById('expense-action-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeExpenseActionModal() {
            const modal = document.getElementById('expense-action-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // --- Calculate by Category Modal Flow ---
        function openCalcModal() {
            document.getElementById('calc-category-modal').classList.remove('hidden');
            fetchCategoryCalc();
        }

        function closeCalcModal() {
            document.getElementById('calc-category-modal').classList.add('hidden');
        }

        function onCalcPeriodChange(period) {
            const customBox = document.getElementById('calc-custom-dates-box');
            if (period === 'custom') {
                customBox.classList.remove('hidden');
                customBox.classList.add('grid');
            } else {
                customBox.classList.add('hidden');
                customBox.classList.remove('grid');
            }
            fetchCategoryCalc();
        }

        function fetchCategoryCalc() {
            const period = document.getElementById('calc-period-select').value;
            const category = document.getElementById('calc-category-select').value;
            const fromDate = document.getElementById('calc-from-date').value;
            const toDate = document.getElementById('calc-to-date').value;

            const loading = document.getElementById('calc-loading');
            const results = document.getElementById('calc-results-container');
            loading.classList.remove('hidden');
            results.classList.add('hidden');

            let url = '{{ route('expenses.calculate-category') }}?period=' + encodeURIComponent(period) + '&category_id=' + encodeURIComponent(category);
            if (period === 'custom') {
                url += '&from_date=' + encodeURIComponent(fromDate) + '&to_date=' + encodeURIComponent(toDate);
            }

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    loading.classList.add('hidden');
                    results.classList.remove('hidden');

                    document.getElementById('calc-overall-total').textContent = '₹' + Number(data.overall_total).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    document.getElementById('calc-item-count').textContent = data.transaction_count + ' items';
                    document.getElementById('calc-period-badge').textContent = data.period + ' range';

                    const list = document.getElementById('calc-breakdown-list');
                    list.innerHTML = '';

                    if (!data.breakdown || data.breakdown.length === 0) {
                        list.innerHTML = '<div class="py-6 text-center text-slate-400 text-xs">No expenses found for this period and category.</div>';
                        return;
                    }

                    data.breakdown.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 space-y-1.5';
                        div.innerHTML = `
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-900 dark:text-white">${item.category}</span>
                                <span class="font-extrabold text-purple-600 dark:text-purple-400">₹${Number(item.total).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div class="h-full bg-purple-500 rounded-full transition-all" style="width: ${Math.min(100, item.percentage)}%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-slate-400">
                                <span>${item.count} transaction(s)</span>
                                <span>${item.percentage}% of calculated</span>
                            </div>
                        `;
                        list.appendChild(div);
                    });
                })
                .catch(err => {
                    loading.classList.add('hidden');
                    results.classList.remove('hidden');
                    document.getElementById('calc-breakdown-list').innerHTML = '<div class="text-rose-500 text-xs py-4 text-center">Failed to load calculations. Please try again.</div>';
                });
        }
    </script>
</x-app-layout>

<x-app-layout title="Expenses">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Expenses</h1>
                <p class="text-xs text-slate-500">Total recorded: <span class="font-bold text-rose-600">₹{{ number_format($totalAmount, 2) }}</span></p>
            </div>
            <a href="{{ route('expenses.create') }}" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-semibold text-xs shadow-md shadow-sky-600/20 flex items-center gap-1.5 self-start transition active:scale-95">
                <span>+</span> Add Expense
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-sky-100 dark:border-slate-800 shadow-sm">
            <form action="{{ route('expenses.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1">Category</label>
                    <select name="category_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                        <option value="">All Categories</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 mb-1">Method</label>
                    <select name="payment_method" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                        <option value="">All Methods</option>
                        @foreach($paymentMethods as $m)
                            <option value="{{ $m }}" {{ request('payment_method') == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                </div>
            </form>
        </div>

        <!-- Expenses Table / List -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <form id="group-expenses-form" action="{{ route('expenses.group') }}" method="POST" class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 p-4 text-xs">
                @csrf
                <button type="submit" class="px-3 py-2 rounded-xl bg-sky-600 text-white font-semibold">Group selected expenses</button>
                <span class="text-slate-400">The group name will use the selected expense description.</span>
            </form>
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
                                    $displayBreakdown = $isGroup
                                        ? $groupExpenses->groupBy('payment_method')->map(fn ($items, $method) => $method.' ₹'.number_format($items->sum(fn ($item) => $item->totalAmount()), 2))->implode(' · ')
                                        : null;
                                    $isEditable = $displayExpense->isEditableByUser();
                                @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4">
                                        @if($isGroup)
                                            <button type="button" class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full border border-sky-200 bg-sky-50 text-sm font-bold leading-none text-sky-700 transition hover:bg-sky-100" aria-label="Show individual expenses" aria-expanded="false" onclick="toggleExpenseGroup('{{ $exp->expenseGroup->id }}', this)">›</button>
                                            <span class="text-[10px] font-semibold text-sky-700">Group · {{ $groupExpenses->count() }}</span>
                                        @else
                                            <input type="checkbox" name="expense_ids[]" value="{{ $exp->id }}" form="group-expenses-form" class="rounded border-slate-300">
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                        {{ $displayDate->format('d M Y') }}
                                        <span class="block text-[10px] text-slate-400">{{ $displayExpense->time }}</span>
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                        <a href="{{ route('expenses.show', $displayExpense) }}" class="font-semibold text-slate-900 hover:underline">{{ $isGroup ? $exp->expenseGroup->name : $exp->description }}</a>
                                        @if($isGroup)
                                            <form action="{{ route('expense-groups.update', $exp->expenseGroup) }}" method="POST" class="mt-1 flex items-center gap-1">
                                                @csrf
                                                @method('PUT')
                                                <input type="text" name="name" value="{{ $exp->expenseGroup->name }}" class="w-40 px-2 py-1 text-[10px] font-normal bg-slate-50 border border-slate-200 rounded-lg">
                                                <button type="submit" class="text-[10px] text-sky-700 hover:underline">Save name</button>
                                            </form>
                                            <span class="block text-[10px] font-normal text-slate-500">{{ $displayBreakdown }}</span>
                                        @elseif($exp->receipt_image)
                                            <a href="{{ asset('storage/' . $exp->receipt_image) }}" target="_blank" class="inline-block ml-1 text-indigo-500 hover:underline text-[10px]">📷 receipt</a>
                                        @endif
                                        @if(!$isGroup && $exp->friendSplits->isNotEmpty())
                                            <span class="block text-[10px] font-medium text-indigo-600">
                                                👥 Split: {{ $exp->friendSplits->map(fn ($s) => ($s->friend?->name ?? 'Friend') . ' (share ₹' . number_format($s->friend_share, 2) . ($s->paid_by_friend_amount > 0 ? ', paid ₹' . number_format($s->paid_by_friend_amount, 2) : '') . ')')->implode(', ') }}
                                            </span>
                                        @elseif(!$isGroup && $exp->friendSplit)
                                            <span class="block text-[10px] font-medium text-indigo-600">
                                                👥 Split with {{ $exp->friendSplit->friend?->name }} (share ₹{{ number_format($exp->friendSplit->friend_share, 2) }}{{ $exp->friendSplit->paid_by_friend_amount > 0 ? ', paid ₹' . number_format($exp->friendSplit->paid_by_friend_amount, 2) : '' }}) · net {{ $exp->friendSplit->netAmount() >= 0 ? '+' : '-' }}₹{{ number_format(abs($exp->friendSplit->netAmount()), 2) }}
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
                                        ₹{{ number_format($displayAmount, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $displayMethods }}</td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $displayPaidBy }}</td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        @if($isGroup)
                                            <a href="{{ route('expenses.show', $displayExpense) }}" class="text-sky-700 hover:text-sky-600 font-semibold">Open details</a>
                                        @elseif($isEditable)
                                            <a href="{{ route('expenses.edit', $exp) }}" class="text-indigo-600 hover:text-indigo-500 font-semibold mr-2">Edit</a>
                                            <form action="{{ route('expenses.destroy', $exp) }}" method="POST" class="inline" onsubmit="return confirm('Archive this expense?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-500 font-semibold cursor-pointer">Delete</button>
                                            </form>
                                        @else
                                            <span class="text-slate-400 text-[11px] font-medium" title="Edit window expired">🔒 Locked</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($isGroup)
                                    <tr id="expense-group-details-{{ $exp->expenseGroup->id }}" class="hidden">
                                        <td colspan="8" class="bg-slate-50 px-4 py-3 sm:px-8">
                                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                                                <table class="w-full text-left text-xs">
                                                    <thead class="bg-slate-100 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                                        <tr>
                                                            <th class="px-4 py-2">Individual expense</th>
                                                            <th class="px-4 py-2">Method</th>
                                                            <th class="px-4 py-2 text-right">Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        @foreach($groupExpenses as $member)
                                                            <tr class="hover:bg-sky-50/50">
                                                                <td class="px-4 py-2.5 font-medium text-slate-700">
                                                                    <a href="{{ route('expenses.show', $member) }}" class="text-sky-700 hover:underline">{{ $member->description }}</a>
                                                                </td>
                                                                <td class="px-4 py-2.5 text-slate-600">{{ $member->payment_method }}</td>
                                                                <td class="px-4 py-2.5 text-right font-semibold text-rose-600">₹{{ number_format($member->totalAmount(), 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
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
    </div>
    <script>
        function toggleExpenseGroup(groupId, button) {
            const details = document.getElementById(`expense-group-details-${groupId}`);
            const isHidden = details.classList.toggle('hidden');
            button.textContent = isHidden ? '›' : '⌄';
            button.setAttribute('aria-expanded', String(!isHidden));
        }
    </script>
</x-app-layout>

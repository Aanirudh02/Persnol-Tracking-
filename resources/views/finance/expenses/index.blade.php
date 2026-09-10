<x-app-layout title="Expenses">
    <div class="expense-page space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="expense-page-title text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Expenses</h1>
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
                <span class="text-slate-400">Select any two or more expenses that are not already in a group. Parent, sub-expense, and friend-split expenses are supported.</span>
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
                                            <button type="button" class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full border border-sky-200 bg-sky-50 text-sm font-bold leading-none text-sky-700 transition hover:bg-sky-100" aria-label="Show individual expenses" aria-expanded="false" onclick="toggleExpenseGroup('{{ $exp->expenseGroup->id }}', this)">›</button>
                                            <span class="text-xs font-bold text-sky-700">{{ $groupExpenses->count() }} entries</span>
                                        @else
                                            <input type="checkbox" name="expense_ids[]" value="{{ $exp->id }}" form="group-expenses-form" class="rounded border-slate-300">
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                        <span class="block text-sm sm:text-[15px] font-medium leading-snug">{{ $displayDate->format('d M Y') }}</span>
                                        <span class="block text-[11px] sm:text-xs text-slate-400 leading-snug">{{ $displayExpense->time }}</span>
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                        <a href="{{ route('expenses.show', $displayExpense) }}" class="font-semibold text-slate-900 hover:underline">{{ $isGroup ? $exp->expenseGroup->name : $exp->description }}</a>
                                        @if(!$isGroup && $exp->parent)
                                            <span class="block text-[10px] font-normal text-slate-400">Sub-expense under: {{ $exp->parent->description }}</span>
                                        @endif
                                        @if($isGroup)
                                            <span class="mt-1 block text-xs font-normal text-slate-500">{{ $groupExpenses->count() }} transactions · {{ $displayMethods }}</span>
                                        @elseif($exp->receipt_image)
                                            <a href="{{ asset('storage/' . $exp->receipt_image) }}" target="_blank" class="inline-block ml-1 text-indigo-500 hover:underline text-[10px]">📷 receipt</a>
                                        @endif
                                        @if(!$isGroup && $exp->friendSplits->isNotEmpty())
                                            <span class="block text-[11px] sm:text-xs font-medium text-indigo-600">
                                                👥 Split: {{ $exp->friendSplits->map(fn ($s) => ($s->friend?->name ?? 'Friend') . ' (share ₹' . number_format($s->friend_share, 2) . ($s->paid_by_friend_amount > 0 ? ', paid ₹' . number_format($s->paid_by_friend_amount, 2) : '') . ')')->implode(', ') }}
                                            </span>
                                        @elseif(!$isGroup && $exp->friendSplit)
                                            <span class="block text-[11px] sm:text-xs font-medium text-indigo-600">
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
                                        @if($isGroup)
                                            ₹{{ number_format($myPaid, 2) }} <span class="block text-[10px] font-normal text-slate-400">your spend</span>
                                        @else
                                            ₹{{ number_format($displayAmount, 2) }}
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                                        @if($isGroup)
                                            <div class="flex flex-wrap gap-1.5">@foreach(explode(' + ', $displayMethods) as $method)<span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold">{{ $method }}</span>@endforeach</div>
                                        @else
                                            {{ $displayMethods }}
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                                        @if($isGroup)
                                            <span class="block font-semibold text-emerald-700">You ₹{{ number_format($myPaid, 2) }}</span>
                                            @if($friendsPaid > 0)<span class="block text-[10px] text-indigo-600">Friends ₹{{ number_format($friendsPaid, 2) }}</span>@else<span class="block text-[10px] text-slate-400">You paid alone</span>@endif
                                        @else
                                            {{ $displayPaidBy }}
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        @if($isGroup)
                                            <button type="button" onclick="openGroupNameModal('{{ $exp->expenseGroup->id }}', @js($exp->expenseGroup->name))" class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:border-sky-300 hover:text-sky-700" title="Edit group name" aria-label="Edit group name">✎</button>
                                            <button type="button" class="font-semibold text-sky-700 hover:text-sky-600" aria-expanded="false" onclick="toggleExpenseGroup('{{ $exp->expenseGroup->id }}', this)">Open details</button>
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
                                        <td colspan="8" class="bg-slate-50 p-0">
                                            <div class="grid grid-cols-2 gap-3 border-b border-slate-200 bg-sky-50/70 p-4 text-xs sm:grid-cols-4">
                                                <div><span class="block text-slate-500">Group total</span><strong class="text-base text-slate-900">₹{{ number_format($displayAmount, 2) }}</strong></div>
                                                <div><span class="block text-slate-500">Your spend</span><strong class="text-base text-emerald-700">₹{{ number_format($myPaid, 2) }}</strong></div>
                                                <div><span class="block text-slate-500">Friends paid</span><strong class="text-base text-indigo-700">₹{{ number_format($friendsPaid, 2) }}</strong></div>
                                                <div><span class="block text-slate-500">Payment breakdown</span><strong class="text-slate-800">{{ $displayBreakdown }}</strong></div>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full min-w-[900px] text-left text-xs">
                                                    <tbody class="divide-y divide-slate-200 border-y border-slate-200 bg-white">
                                                        @foreach($groupExpenses as $member)
                                                            @php $memberEditable = $member->isEditableByUser(); @endphp
                                                            <tr class="hover:bg-sky-50/50">
                                                                <td class="w-24 px-4 py-3 text-[10px] font-semibold text-sky-700">Grouped</td>
                                                                <td class="px-4 py-3 font-mono text-slate-600 whitespace-nowrap">
                                                                    {{ $member->date->format('d M Y') }}
                                                                    <span class="block text-[10px] text-slate-400">{{ $member->time }}</span>
                                                                </td>
                                                                <td class="px-4 py-3 font-semibold text-slate-900">
                                                                    <a href="{{ route('expenses.show', $member) }}" class="hover:underline">{{ $member->description }}</a>
                                                                    @if($member->parent)
                                                                        <span class="block text-[10px] font-normal text-slate-400">Sub-expense under: {{ $member->parent->description }}</span>
                                                                    @endif
                                                                    @if($member->notes)
                                                                        <span class="block text-[10px] font-normal text-slate-400">{{ $member->notes }}</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-700">{{ $member->category?->name ?? 'Other' }}</span></td>
                                                                <td class="px-4 py-3 font-bold text-rose-600 whitespace-nowrap">₹{{ number_format($member->totalAmount(), 2) }}</td>
                                                                <td class="px-4 py-3 text-slate-600">{{ $member->payment_method }}</td>
                                                                <td class="px-4 py-3 text-slate-600">{{ $member->paid_by }}</td>
                                                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                                                    @if($memberEditable)
                                                                        <a href="{{ route('expenses.edit', $member) }}" class="mr-2 font-semibold text-indigo-600 hover:text-indigo-500">Edit</a>
                                                                        <form action="{{ route('expenses.destroy', $member) }}" method="POST" class="inline" onsubmit="return confirm('Archive this expense?');">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="font-semibold text-rose-600 hover:text-rose-500">Delete</button>
                                                                        </form>
                                                                    @else
                                                                        <span class="text-[11px] font-medium text-slate-400">Locked</span>
                                                                    @endif
                                                                </td>
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
        <div id="group-name-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" onclick="if(event.target === this) closeGroupNameModal()">
            <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Edit group name</h2>
                    <button type="button" onclick="closeGroupNameModal()" class="text-xl text-slate-400" aria-label="Close">&times;</button>
                </div>
                <form id="group-name-form" action="" method="POST" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <input id="group-name-input" type="text" name="name" required maxlength="255" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm">
                    <button type="submit" class="w-full rounded-xl bg-sky-600 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Save group name</button>
                </form>
            </div>
        </div>
    </div>
    <style>
        .expense-page {
            font-size: 1.12rem;
        }

        .expense-page .expense-page-title {
            font-size: 2.2rem !important;
        }

        .expense-page th,
        .expense-page td,
        .expense-page .text-xs,
        .expense-page .text-[10px],
        .expense-page .text-[11px],
        .expense-page .text-sm,
        .expense-page .text-base,
        .expense-page .text-2xl {
            transition: font-size 0.15s ease;
        }

        .expense-page th,
        .expense-page td {
            font-size: 0.98rem;
        }

        .expense-page .text-xs { font-size: 0.9rem !important; }
        .expense-page .text-[10px] { font-size: 0.78rem !important; }
        .expense-page .text-[11px] { font-size: 0.84rem !important; }
        .expense-page .text-sm { font-size: 1.04rem !important; }
        .expense-page .text-base { font-size: 1.14rem !important; }
        .expense-page .text-2xl { font-size: 1.98rem !important; }
    </style>
    <script>
        function toggleExpenseGroup(groupId, button) {
            const details = document.getElementById(`expense-group-details-${groupId}`);
            const isHidden = details.classList.toggle('hidden');
            const toggleButton = button.matches('button[aria-label]') ? button : button.closest('tr')?.querySelector('button[aria-label]');
            if (toggleButton) toggleButton.textContent = isHidden ? '›' : '⌄';
            button.setAttribute('aria-expanded', String(!isHidden));
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
    </script>
</x-app-layout>

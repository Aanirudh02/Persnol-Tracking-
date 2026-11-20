<x-app-layout title="Income Details & Expense Tally">
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <a href="{{ route('income.index') }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600">&larr; Back to Income</a>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-900">Income Stream & Expense Tally</h1>
            <p class="text-xs text-slate-500">Track which expenses were funded from this income without affecting regular expense statements.</p>
        </div>

        <!-- Income Summary Card -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <span class="rounded-lg bg-emerald-100 border border-emerald-200 px-2.5 py-0.5 text-xs font-bold text-emerald-800 uppercase">{{ $income->category?->name ?? 'Income' }}</span>
                    <h2 class="mt-2 text-2xl font-black text-slate-900">{{ $income->source }}</h2>
                    <p class="text-xs text-slate-500">{{ $income->date->format('d M Y') }} · {{ $income->payment_method }}</p>
                </div>
                <div class="text-left sm:text-right">
                    <span class="text-xs font-bold uppercase text-slate-400">Total Income Amount</span>
                    <div class="text-3xl font-black text-emerald-600">₹{{ number_format($income->amount, 2) }}</div>
                </div>
            </div>

            <!-- Tally Progress Bar -->
            @php
                $pct = $income->amount > 0 ? min(100, round(($talliedTotal / (float)$income->amount) * 100, 1)) : 0;
            @endphp
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs font-bold">
                    <span class="text-slate-600">Expense Tally Utilization: {{ $pct }}%</span>
                    <span>
                        <span class="text-indigo-600">₹{{ number_format($talliedTotal, 2) }} Tallied</span>
                        <span class="text-slate-400"> / </span>
                        <span class="text-emerald-700">₹{{ number_format($untalliedTotal, 2) }} Surplus</span>
                    </span>
                </div>
                <div class="h-3 w-full rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        </div>

        <!-- Currently Tallied Expenses List -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span>🧾</span> Expenses Funded From This Income ({{ $income->tallies->count() }})
            </h3>

            @if($income->tallies->isNotEmpty())
                <div class="divide-y divide-slate-100 rounded-2xl border border-slate-100 bg-slate-50/50 overflow-hidden">
                    @foreach($income->tallies as $tally)
                        <div class="flex items-center justify-between p-3.5 text-xs">
                            <div class="space-y-0.5">
                                <span class="font-bold text-slate-800 text-sm">{{ $tally->expense?->description ?: 'Expense' }}</span>
                                <div class="text-slate-500">
                                    {{ $tally->expense?->date?->format('d M Y') }} · {{ $tally->expense?->category?->name ?? 'Uncategorized' }} · {{ $tally->expense?->payment_method }} · Paid by {{ $tally->expense?->paid_by ?: 'Me' }}
                                    @if($tally->notes)
                                        <span class="text-slate-400">({{ $tally->notes }})</span>
                                    @endif
                                </div>
                                @if($tally->expense?->friendSplits->isNotEmpty())
                                    <div class="text-[10px] text-indigo-600">
                                        @foreach($tally->expense->friendSplits as $split)
                                            <strong>{{ $split->friend?->name ?? 'Friend' }}</strong> (share ₹{{ number_format($split->friend_share, 2) }}@if((float) $split->paid_by_friend_amount > 0)<strong>, paid ₹{{ number_format($split->paid_by_friend_amount, 2) }}{{ $split->payment_method ? ' via '.$split->payment_method : '' }}</strong>@endif){{ ! $loop->last ? ', ' : '' }}
                                        @endforeach
                                    </div>
                                @elseif($tally->expense?->friendSplit)
                                    <div class="text-[10px] text-indigo-600">
                                        <strong>{{ $tally->expense->friendSplit->friend?->name ?? 'Friend' }}</strong> (share ₹{{ number_format($tally->expense->friendSplit->friend_share, 2) }}@if((float) $tally->expense->friendSplit->paid_by_friend_amount > 0)<strong>, paid ₹{{ number_format($tally->expense->friendSplit->paid_by_friend_amount, 2) }}{{ $tally->expense->friendSplit->payment_method ? ' via '.$tally->expense->friendSplit->payment_method : '' }}</strong>@endif)
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <div class="font-bold text-indigo-700 text-sm">₹{{ number_format($tally->allocated_amount, 2) }}</div>
                                    <span class="text-[10px] text-slate-400">of total ₹{{ number_format($tally->expense?->totalAmount() ?? 0, 2) }}</span>
                                </div>
                                <form action="{{ route('income.untally', [$income, $tally]) }}" method="POST" onsubmit="return confirm('Remove tally link for this expense?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg border border-rose-200 bg-white px-2 py-1 font-bold text-rose-600 hover:bg-rose-50">&times;</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-400 py-2">No expenses tallied against this income yet.</p>
            @endif

            @if($expenses->isNotEmpty())
                <form action="{{ route('income.tally', $income) }}" method="POST" class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4 text-xs space-y-3">
                    @csrf
                    <div>
                        <div class="font-bold text-indigo-950">Expenses paid via {{ $income->payment_method }}</div>
                        <p class="mt-1 text-slate-600">Already tallied expenses stay visible at their full amount but cannot be selected. Income available: ₹{{ number_format($untalliedTotal, 2) }}</p>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                        <table class="w-full min-w-[900px] text-left text-xs">
                            <thead class="border-b border-slate-200 bg-slate-50 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-3 py-3">Select</th>
                                    <th class="px-3 py-3">Date</th>
                                    <th class="px-3 py-3">Description</th>
                                    <th class="px-3 py-3">Category</th>
                                    <th class="px-3 py-3">Amount</th>
                                    <th class="px-3 py-3">Method</th>
                                    <th class="px-3 py-3">Paid By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($expenses as $exp)
                                    <tr class="{{ $exp->is_selectable ? 'hover:bg-indigo-50/50' : 'bg-slate-50/70 text-slate-400' }}">
                                        <td class="px-3 py-3">
                                            <input
                                                type="checkbox"
                                                @if($exp->is_selectable)
                                                    name="expense_ids[]"
                                                    value="{{ $exp->id }}"
                                                    data-amount="{{ $exp->available_to_tally }}"
                                                    @checked(in_array($exp->id, old('expense_ids', [])))
                                                    @disabled($untalliedTotal <= 0)
                                                @else
                                                    value="{{ $exp->id }}"
                                                    disabled
                                                    aria-label="Already tallied expense"
                                                @endif
                                                class="tally-expense-checkbox rounded border-slate-300 text-indigo-600"
                                            >
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3 font-mono text-slate-500">
                                            {{ $exp->date->format('d M Y') }}
                                            @if($exp->time)
                                                <span class="block text-[10px] text-slate-400">{{ $exp->time }}</span>
                                            @endif
                                        </td>
                                        <td class="max-w-sm px-3 py-3">
                                            <span class="block font-semibold {{ $exp->is_selectable ? 'text-slate-900' : 'text-slate-500' }}">{{ $exp->description ?: 'Expense' }}</span>
                                            @if($exp->expenseGroup)
                                                <span class="block text-[10px] text-slate-400">Group: {{ $exp->expenseGroup->name }}</span>
                                            @endif
                                            @if($exp->friendSplits->isNotEmpty())
                                                <span class="mt-1 block text-[10px] text-indigo-600">
                                                    Split:
                                                    @foreach($exp->friendSplits as $split)
                                                        @if((float) $split->paid_by_friend_amount > 0)
                                                            <strong>{{ $split->friend?->name ?? 'Friend' }}: ₹{{ number_format($split->paid_by_friend_amount, 2) }}{{ $split->payment_method ? ' via '.$split->payment_method : '' }}</strong>{{ ! $loop->last ? ', ' : '' }}
                                                        @endif
                                                    @endforeach
                                                </span>
                                            @elseif($exp->friendSplit && (float) $exp->friendSplit->paid_by_friend_amount > 0)
                                                <span class="mt-1 block text-[10px] text-indigo-600">
                                                    Split: <strong>{{ $exp->friendSplit->friend?->name ?? 'Friend' }}: ₹{{ number_format($exp->friendSplit->paid_by_friend_amount, 2) }}{{ $exp->friendSplit->payment_method ? ' via '.$exp->friendSplit->payment_method : '' }}</strong>
                                                </span>
                                            @endif
                                            @if(!$exp->is_selectable)
                                                <span class="mt-1 block text-[10px] font-semibold text-slate-500">{{ $exp->tally_badge }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-700">{{ $exp->category?->name ?? 'Uncategorized' }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3 font-bold {{ $exp->is_selectable ? 'text-rose-600' : 'text-slate-500' }}">
                                            ₹{{ number_format($exp->totalAmount(), 2) }}
                                            @if($exp->is_selectable)
                                                <span class="block text-[10px] font-normal text-slate-400">full amount</span>
                                            @else
                                                <span class="block text-[10px] font-normal text-slate-400">already tallied</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-slate-600">{{ $exp->payment_method }}</td>
                                        <td class="px-3 py-3 text-slate-600">{{ $exp->paid_by ?: 'Me' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($expenses->contains('is_selectable', true) && $untalliedTotal > 0)
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <div id="bulk-tally-summary" class="rounded-xl bg-white px-3 py-2 font-semibold text-indigo-800">
                                0 expenses selected · ₹0.00 total
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold text-slate-700">Notes (optional)</label>
                                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="e.g. Paid from salary" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                            </div>
                        </div>
                        <div class="flex justify-end pt-1">
                            <button type="submit" id="bulk-tally-submit" disabled class="rounded-xl bg-indigo-600 px-5 py-2 font-bold text-white transition disabled:cursor-not-allowed disabled:opacity-50">Attach Selected Expenses</button>
                        </div>
                    @endif
                </form>
            @endif
        </div>
    </div>

    <script>
        const tallyCheckboxes = Array.from(document.querySelectorAll('.tally-expense-checkbox:not(:disabled)'));
        const tallyLimit = {{ $untalliedTotal }};
        const tallySubmit = document.getElementById('bulk-tally-submit');
        const tallySummary = document.getElementById('bulk-tally-summary');

        function updateBulkTallySummary() {
            const selected = tallyCheckboxes.filter(checkbox => checkbox.checked);
            const total = selected.reduce((sum, checkbox) => sum + Number(checkbox.dataset.amount || 0), 0);
            tallySummary.textContent = `${selected.length} expense(s) selected · ₹${total.toFixed(2)} total${total > tallyLimit ? ' (over available income)' : ''}`;
            tallySubmit.disabled = selected.length === 0 || total > tallyLimit + 0.01;
        }

        tallyCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkTallySummary);
        });

        if (tallyCheckboxes.length > 0 && tallySubmit && tallySummary) {
            updateBulkTallySummary();
        }
    </script>
</x-app-layout>

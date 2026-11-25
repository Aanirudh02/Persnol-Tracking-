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
                <div class="space-y-3">
                    @foreach($income->tallies as $tally)
                        @php $isPartlyTallied = ($tally->expense?->untalliedAmount() ?? 0) > 0; @endphp
                        <div class="rounded-2xl border p-4 text-xs space-y-3 {{ $isPartlyTallied ? 'border-amber-200 bg-amber-50/40' : 'border-slate-200 bg-slate-50/50' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 space-y-0.5">
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
                                    <div class="text-lg font-black text-indigo-700">₹{{ number_format($tally->allocated_amount, 2) }}</div>
                                    <span class="text-[10px] font-semibold uppercase text-slate-400">from this income</span>
                                </div>
                                <form action="{{ route('income.untally', [$income, $tally]) }}" method="POST" data-confirm-remove="{{ $tally->expense?->description ?: 'Expense' }}" data-confirm-amount="{{ number_format($tally->allocated_amount, 2) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg border border-rose-200 bg-white px-2 py-1 font-bold text-rose-600 hover:bg-rose-50" aria-label="Remove tally">&times;</button>
                                </form>
                            </div>
                        </div>
                        @if($tally->expense)
                            <x-tally-progress :expense="$tally->expense" :current-income-id="$income->id" />
                        @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-400 py-2">No expenses tallied against this income yet.</p>
            @endif

            @php
                $toggleUrl = request()->fullUrlWithQuery(['show_tallied' => $showFullyTallied ? null : 1]);
                $otherMonthsUnpaidTotal = $unpaidInOtherMonths->sum('count');
            @endphp
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4 text-xs space-y-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-bold text-indigo-950">Expenses paid via {{ $income->payment_method }} · {{ $monthFilter->label() }}</div>
                        <p class="mt-1 text-slate-600">Partly tallied expenses can be selected for their remaining amount. Selected expenses stay selected when you change month. Income available: ₹{{ number_format($untalliedTotal, 2) }}</p>
                    </div>
                    <x-month-pager :filter="$monthFilter" class="shrink-0" data-carry-selection />
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2" data-carry-selection>
                    <a href="{{ $toggleUrl }}" class="inline-flex items-center gap-2 font-semibold text-slate-600 hover:text-indigo-700" role="switch" aria-checked="{{ $showFullyTallied ? 'true' : 'false' }}">
                        <span class="relative inline-flex h-4 w-7 shrink-0 items-center rounded-full transition {{ $showFullyTallied ? 'bg-indigo-600' : 'bg-slate-300' }}">
                            <span class="absolute h-3 w-3 rounded-full bg-white shadow transition {{ $showFullyTallied ? 'left-3.5' : 'left-0.5' }}"></span>
                        </span>
                        Show fully tallied ({{ $fullyTalliedCount }})
                    </a>
                    @if($otherMonthsUnpaidTotal > 0)
                        <div class="flex flex-wrap items-center gap-1.5 text-amber-800">
                            <span class="font-semibold">{{ $otherMonthsUnpaidTotal }} {{ Str::plural('expense', $otherMonthsUnpaidTotal) }} in other months still {{ $otherMonthsUnpaidTotal === 1 ? 'has' : 'have' }} an unpaid amount:</span>
                            @foreach($unpaidInOtherMonths as $unpaidMonth)
                                <a href="{{ request()->fullUrlWithQuery(['month' => $unpaidMonth['month']]) }}" class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 font-semibold hover:bg-amber-100">{{ $unpaidMonth['label'] }} · {{ $unpaidMonth['count'] }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <form action="{{ route('income.tally', $income) }}" method="POST" class="space-y-3">
                    @csrf
                    <input type="hidden" name="confirm_partial" id="confirm-partial" value="0">
                    <input type="hidden" name="month" value="{{ $monthFilter->value() }}">
                    @if($showFullyTallied)
                        <input type="hidden" name="show_tallied" value="1">
                    @endif
                    @if($errors->hasAny(['expense_ids', 'expense_ids.*']))
                        <p class="font-semibold text-rose-600">{{ $errors->first('expense_ids') ?: $errors->first('expense_ids.*') }}</p>
                    @endif

                    @if($selectedInOtherMonths->isNotEmpty())
                        <div class="rounded-xl border border-indigo-200 bg-white p-3 space-y-2">
                            <div class="font-bold text-indigo-950">Selected from other months ({{ $selectedInOtherMonths->count() }})</div>
                            <ul class="divide-y divide-slate-100" data-carry-selection>
                                @foreach($selectedInOtherMonths as $exp)
                                    <li class="flex flex-wrap items-center gap-x-3 gap-y-1 py-1.5">
                                        <input
                                            type="checkbox"
                                            name="expense_ids[]"
                                            value="{{ $exp->id }}"
                                            data-amount="{{ $exp->available_to_tally }}"
                                            data-id="{{ $exp->id }}"
                                            data-date="{{ $exp->date->format('Y-m-d') }}"
                                            data-description="{{ $exp->description ?: 'Expense' }}"
                                            checked
                                            @disabled($untalliedTotal <= 0)
                                            aria-label="Keep {{ $exp->description ?: 'Expense' }} selected"
                                            class="tally-expense-checkbox rounded border-slate-300 text-indigo-600"
                                        >
                                        <span class="font-mono text-slate-500">{{ $exp->date->format('d M Y') }}</span>
                                        <span class="font-semibold text-slate-900">{{ $exp->description ?: 'Expense' }}</span>
                                        <span class="text-slate-400">{{ $exp->category?->name ?? 'Uncategorized' }}</span>
                                        <span class="ml-auto font-bold text-rose-600">₹{{ number_format($exp->available_to_tally, 2) }}{{ $exp->total_tallied_amount > 0 ? ' remaining' : '' }}</span>
                                        <a href="{{ request()->fullUrlWithQuery(['month' => $exp->date->format('Y-m')]) }}" class="font-semibold text-indigo-600 hover:underline">{{ $exp->date->format('M Y') }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($expenses->isEmpty())
                        <p class="rounded-xl border border-dashed border-slate-300 bg-white px-3 py-6 text-center text-slate-500">
                            @if($fullyTalliedCount > 0)
                                Every {{ $income->payment_method }} expense in {{ $monthFilter->label() }} is fully tallied.
                            @else
                                No {{ $income->payment_method }} expenses to tally in {{ $monthFilter->label() }}.
                            @endif
                        </p>
                    @else
                    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                        <table class="w-full min-w-[760px] text-left text-xs">
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
                                                    data-id="{{ $exp->id }}"
                                                    data-date="{{ $exp->date->format('Y-m-d') }}"
                                                    data-description="{{ $exp->description ?: 'Expense' }}"
                                                    @checked(in_array($exp->id, $selectedExpenseIds, true))
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
                                            @if($exp->is_selectable && $exp->total_tallied_amount > 0)
                                                <x-tally-progress :expense="$exp" :current-income-id="$income->id" class="mt-2 w-56 whitespace-normal rounded-xl border border-amber-200 bg-amber-50/60 p-2.5" />
                                            @elseif(!$exp->is_selectable)
                                                <span class="mt-1 block text-[10px] font-semibold text-slate-500">{{ $exp->tally_badge }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-700">{{ $exp->category?->name ?? 'Uncategorized' }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3 font-bold {{ $exp->is_selectable ? 'text-rose-600' : 'text-slate-500' }}">
                                            ₹{{ number_format($exp->totalAmount(), 2) }}
                                            @if($exp->is_selectable && $exp->total_tallied_amount > 0)
                                                <span class="block text-[10px] font-semibold text-amber-600">₹{{ number_format($exp->available_to_tally, 2) }} remaining</span>
                                            @elseif($exp->is_selectable)
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
                    @endif
                    @if(($expenses->contains('is_selectable', true) || $selectedInOtherMonths->isNotEmpty()) && $untalliedTotal > 0)
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
            </div>
        </div>
    </div>

    <x-confirm-dialog />

    <script>
        document.querySelectorAll('form[data-confirm-remove]').forEach(form => {
            form.addEventListener('submit', async event => {
                event.preventDefault();
                const confirmed = await window.confirmDialog({
                    title: 'Remove this tally?',
                    message: `${form.dataset.confirmRemove} will no longer be funded from this income. ₹${form.dataset.confirmAmount} goes back to the income's available amount.`,
                    confirmLabel: 'Remove tally',
                    tone: 'danger',
                });

                if (confirmed) {
                    form.submit();
                }
            });
        });

        const tallyCheckboxes = Array.from(document.querySelectorAll('.tally-expense-checkbox:not(:disabled)'));
        const tallyLimit = {{ $untalliedTotal }};
        const tallySubmit = document.getElementById('bulk-tally-submit');
        const tallySummary = document.getElementById('bulk-tally-summary');

        const confirmPartialInput = document.getElementById('confirm-partial');
        const toPaise = value => Math.round(Number(value || 0) * 100);
        const formatRupees = paise => `₹${(paise / 100).toFixed(2)}`;

        // Mirrors the server: oldest expenses are covered first.
        function planAllocations() {
            let incomeLeft = toPaise(tallyLimit);

            return tallyCheckboxes
                .filter(checkbox => checkbox.checked)
                .sort((a, b) => a.dataset.date.localeCompare(b.dataset.date) || a.dataset.id - b.dataset.id)
                .map(checkbox => {
                    const amount = toPaise(checkbox.dataset.amount);
                    const allocated = Math.min(amount, incomeLeft);
                    incomeLeft -= allocated;

                    return { description: checkbox.dataset.description, allocated, remaining: amount - allocated };
                });
        }

        function updateBulkTallySummary() {
            const plan = planAllocations();
            const total = plan.reduce((sum, item) => sum + item.allocated + item.remaining, 0);
            const shortfall = total - toPaise(tallyLimit);
            const hasUnfunded = plan.some(item => item.allocated === 0);
            let note = '';

            if (hasUnfunded) {
                note = ' (income runs out, select fewer expenses)';
            } else if (shortfall > 0) {
                note = ` (only ${formatRupees(toPaise(tallyLimit))} will be tallied, ${formatRupees(shortfall)} will remain)`;
            }

            tallySummary.textContent = `${plan.length} expense(s) selected · ${formatRupees(total)} total${note}`;
            tallySubmit.disabled = plan.length === 0 || hasUnfunded;
        }

        // Selections travel between months in the ?selected= query value.
        const selectedExpenseIds = () => tallyCheckboxes.filter(checkbox => checkbox.checked).map(checkbox => checkbox.value).join(',');

        function carrySelectionIntoLinks() {
            const ids = selectedExpenseIds();

            document.querySelectorAll('[data-carry-selection] a').forEach(link => {
                const url = new URL(link.href, window.location.href);
                ids ? url.searchParams.set('selected', ids) : url.searchParams.delete('selected');
                link.href = url.toString();
            });
        }

        document.querySelectorAll('[data-carry-selection] form').forEach(form => {
            form.addEventListener('submit', () => {
                const ids = selectedExpenseIds();
                let input = form.querySelector('input[name="selected"]');

                if (!ids) {
                    input?.remove();
                    return;
                }
                if (!input) {
                    input = Object.assign(document.createElement('input'), { type: 'hidden', name: 'selected' });
                    form.appendChild(input);
                }
                input.value = ids;
            });
        });

        if (tallyCheckboxes.length > 0 && tallySubmit && tallySummary) {
            tallyCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    updateBulkTallySummary();
                    carrySelectionIntoLinks();
                });
            });
            carrySelectionIntoLinks();

            tallySubmit.form.addEventListener('submit', async event => {
                const partial = planAllocations().filter(item => item.remaining > 0);
                confirmPartialInput.value = '0';

                if (partial.length === 0) {
                    return;
                }

                event.preventDefault();
                const confirmed = await window.confirmDialog({
                    title: 'Tally only part of the selection?',
                    message: `Only ${formatRupees(toPaise(tallyLimit))} is left in this income, so these expenses will be partly tallied:`,
                    lines: partial.map(item => ({
                        label: item.description,
                        detail: `${formatRupees(item.allocated)} tallied · ${formatRupees(item.remaining)} remains`,
                    })),
                    footnote: 'You can tally the remaining amount from another income.',
                    confirmLabel: 'Tally partly',
                    tone: 'warning',
                });

                if (confirmed) {
                    confirmPartialInput.value = '1';
                    tallySubmit.form.submit();
                }
            });

            updateBulkTallySummary();
        }
    </script>
</x-app-layout>

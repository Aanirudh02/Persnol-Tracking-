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
                                    {{ $tally->expense?->date?->format('d M Y') }} · {{ $tally->expense?->category?->name ?? 'Uncategorized' }}
                                    @if($tally->notes)
                                        <span class="text-slate-400">({{ $tally->notes }})</span>
                                    @endif
                                </div>
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

            <!-- Attach New Expense Tally Form -->
            @if($untalliedTotal > 0 && $availableExpenses->isNotEmpty())
                <form action="{{ route('income.tally', $income) }}" method="POST" class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4 text-xs space-y-3">
                    @csrf
                    <div class="font-bold text-indigo-950">Tally multiple expenses against this income</div>
                    <p class="text-slate-600">Select expenses to apply each one’s full remaining untallied amount. Income available: ₹{{ number_format($untalliedTotal, 2) }}</p>
                    <div class="max-h-64 space-y-1 overflow-y-auto rounded-xl border border-indigo-100 bg-white p-2">
                        @foreach($availableExpenses as $exp)
                            <label class="flex cursor-pointer items-start gap-2 rounded-lg p-2 hover:bg-indigo-50">
                                <input type="checkbox" name="expense_ids[]" value="{{ $exp->id }}" data-amount="{{ $exp->available_to_tally }}" @checked(in_array($exp->id, old('expense_ids', []))) class="tally-expense-checkbox mt-0.5 rounded border-slate-300 text-indigo-600">
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-semibold text-slate-800">{{ $exp->description ?: 'Expense' }}</span>
                                    <span class="text-[11px] text-slate-500">{{ $exp->date->format('d M Y') }} · ₹{{ number_format($exp->available_to_tally, 2) }} available</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div id="bulk-tally-summary" class="rounded-xl bg-white px-3 py-2 font-semibold text-indigo-800">
                            0 expenses selected · ₹0.00 total
                        </div>
                        <div>
                            <label class="font-semibold text-slate-700 block mb-1">Notes (optional)</label>
                            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="e.g. Paid from salary" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                        </div>
                    </div>
                    <div class="flex justify-end pt-1">
                        <button type="submit" id="bulk-tally-submit" disabled class="rounded-xl bg-indigo-600 px-5 py-2 font-bold text-white transition disabled:cursor-not-allowed disabled:opacity-50">Attach Selected Expenses</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <script>
        const tallyCheckboxes = Array.from(document.querySelectorAll('.tally-expense-checkbox'));
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

        if (tallyCheckboxes.length > 0) {
            updateBulkTallySummary();
        }
    </script>
</x-app-layout>

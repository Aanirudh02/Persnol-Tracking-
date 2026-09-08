<x-app-layout title="Edit Credit / Debt">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('credits.index', ['type' => $creditDebt->type]) }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600">&larr; Back to {{ $creditDebt->type === 'credit' ? 'Credits' : 'Debts' }}</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Edit {{ $creditDebt->type === 'credit' ? 'Credit (I Owe)' : 'Debt (Owes Me)' }}</h1>
            <p class="text-xs text-slate-500">Update the total amount, how much has been paid, or transaction details.</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @if ($errors->any())
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 space-y-1">
                    <p class="font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-600 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Please fix the following errors:
                    </p>
                    <ul class="list-disc pl-5 space-y-0.5 text-xs text-rose-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('credits.update', $creditDebt) }}" method="POST" class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                @csrf
                @method('PUT')

                <div class="sm:col-span-2">
                    <label class="font-semibold text-slate-700">Friend</label>
                    <select name="friend_id" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                        @foreach($friends as $friend)
                            <option value="{{ $friend->id }}" @selected(old('friend_id', $creditDebt->friend_id) == $friend->id)>{{ $friend->name }} ({{ $friend->role }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="font-semibold text-slate-700">Type</label>
                    <select name="type" id="credit_type" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                        <option value="credit" @selected(old('type', $creditDebt->type) === 'credit')>Credit (I owe friend)</option>
                        <option value="debt" @selected(old('type', $creditDebt->type) === 'debt')>Debt (Friend owes me)</option>
                    </select>
                </div>

                <div>
                    <label class="font-semibold text-slate-700" id="label_amount">Total Amount (₹)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="credit_amount" value="{{ old('amount', $creditDebt->amount) }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-base font-bold">
                </div>

                <div>
                    <label class="font-semibold text-slate-700" id="label_paid">Amount Paid So Far (₹)</label>
                    <input type="number" step="0.01" min="0" name="amount_paid" id="credit_amount_paid" value="{{ old('amount_paid', $creditDebt->amount_paid) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-base font-bold">
                </div>

                <!-- Dynamic live balance card -->
                <div class="flex flex-col justify-center rounded-2xl border border-sky-100 bg-sky-50/60 px-4 py-2.5">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Remaining Balance</span>
                    <div class="text-xl font-black text-sky-800" id="remaining_display">₹{{ number_format($creditDebt->remaining(), 2) }}</div>
                </div>

                <div>
                    <label class="font-semibold text-slate-700">Date</label>
                    <input type="date" name="date" value="{{ old('date', $creditDebt->date->toDateString()) }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                </div>

                <div>
                    <label class="font-semibold text-slate-700">Payment Method (if updating payment)</label>
                    <select name="payment_method" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                        <option value="">Keep existing method</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method }}">{{ $method }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="font-semibold text-slate-700">Due date</label>
                    <input type="date" name="due_date" value="{{ old('due_date', optional($creditDebt->due_date)->toDateString()) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                </div>

                <div>
                    <label class="font-semibold text-slate-700">Status</label>
                    <select name="status" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                        @foreach($statuses as $status)
                            <option value="{{ $status['name'] }}" @selected(old('status', $creditDebt->status) === $status['name'])>{{ $status['icon'] ?: ucfirst(str_replace('_', ' ', $status['name'])) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="font-semibold text-slate-700">Location</label>
                    <input type="text" name="location" value="{{ old('location', $creditDebt->location) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                </div>

                <div class="sm:col-span-2">
                    <label class="font-semibold text-slate-700">Description</label>
                    <input type="text" name="description" value="{{ old('description', $creditDebt->description) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                </div>

                <div class="sm:col-span-2">
                    <label class="font-semibold text-slate-700">Notes</label>
                    <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">{{ old('notes', $creditDebt->notes) }}</textarea>
                </div>

                <div class="sm:col-span-2 flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('credits.index', ['type' => $creditDebt->type]) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-2.5 font-semibold text-white shadow-md hover:bg-indigo-700 transition active:scale-95">Save Changes</button>
                </div>
            </form>
        </div>

        @if($creditDebt->payments->isNotEmpty())
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-bold text-slate-900 flex items-center gap-2">
                    <span>💳</span> Existing Payment Transactions
                </h2>
                <div class="space-y-3">
                    @foreach($creditDebt->payments as $payment)
                        <div class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm">
                            <div>
                                <div class="font-bold text-slate-900">₹{{ number_format($payment->amount, 2) }}</div>
                                <div class="text-xs text-slate-500">{{ $payment->paid_on->format('d M Y') }} · {{ $payment->payment_method ?: 'No method' }}</div>
                                @if($payment->notes)
                                    <div class="text-xs text-slate-400 mt-0.5">{{ $payment->notes }}</div>
                                @endif
                            </div>
                            <form action="{{ route('credits.payments.delete', [$creditDebt, $payment]) }}" method="POST" onsubmit="return confirm('Delete this payment of ₹{{ number_format($payment->amount, 2) }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-semibold px-2 py-1 rounded-lg hover:bg-rose-50 transition">
                                    Delete
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <script>
        const typeSelect = document.getElementById('credit_type');
        const amountInput = document.getElementById('credit_amount');
        const paidInput = document.getElementById('credit_amount_paid');
        const remainingDisplay = document.getElementById('remaining_display');
        const labelAmount = document.getElementById('label_amount');
        const labelPaid = document.getElementById('label_paid');

        function updateLabels() {
            const isCredit = typeSelect.value === 'credit';
            labelAmount.textContent = isCredit ? 'Total Amount I Owe (₹)' : 'Total Amount Friend Owes Me (₹)';
            labelPaid.textContent = isCredit ? 'Amount I Have Paid (₹)' : 'Amount Friend Has Paid (₹)';
            updateRemaining();
        }

        function updateRemaining() {
            const amount = Number(amountInput.value) || 0;
            const paid = Number(paidInput.value) || 0;
            const rem = Math.max(0, amount - paid);
            remainingDisplay.textContent = '₹' + rem.toFixed(2);
            if (paid > amount) {
                remainingDisplay.className = 'text-xl font-black text-rose-600';
                remainingDisplay.textContent = 'Overpaid by ₹' + (paid - amount).toFixed(2);
            } else if (rem === 0) {
                remainingDisplay.className = 'text-xl font-black text-emerald-600';
                remainingDisplay.textContent = '₹0.00 (Fully Paid)';
            } else {
                remainingDisplay.className = 'text-xl font-black text-sky-800';
            }
        }

        typeSelect.addEventListener('change', updateLabels);
        amountInput.addEventListener('input', updateRemaining);
        paidInput.addEventListener('input', updateRemaining);
        updateLabels();
    </script>
</x-app-layout>

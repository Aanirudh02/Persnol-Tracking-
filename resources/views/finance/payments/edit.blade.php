<x-app-layout title="Edit Payment">
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <a href="{{ route('payments.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Payments
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 mt-2">Edit Payment</h1>
            <p class="text-xs text-slate-500">Update payment details and reconciliation status.</p>
        </div>

        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-sky-100 shadow-sm">
            @if ($errors->any())
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('payments.update', $payment) }}" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Amount (₹) *</label>
                    <input
                        type="number"
                        step="0.01"
                        name="amount"
                        required
                        value="{{ old('amount', $payment->amount) }}"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-lg font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                    >
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Paid To *</label>
                        <input
                            type="text"
                            name="paid_to"
                            required
                            value="{{ old('paid_to', $payment->paid_to) }}"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        >
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Paid By</label>
                        <input
                            type="text"
                            name="paid_by"
                            value="{{ old('paid_by', $payment->paid_by ?? 'Me') }}"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        >
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Purpose *</label>
                    <input
                        type="text"
                        name="purpose"
                        required
                        value="{{ old('purpose', $payment->purpose) }}"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                    >
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Method</label>
                        <select
                            name="payment_method"
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        >
                            <option value="UPI" {{ old('payment_method', $payment->payment_method) === 'UPI' ? 'selected' : '' }}>UPI</option>
                            <option value="Bank" {{ old('payment_method', $payment->payment_method) === 'Bank' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="Cash" {{ old('payment_method', $payment->payment_method) === 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="Card" {{ old('payment_method', $payment->payment_method) === 'Card' ? 'selected' : '' }}>Card</option>
                            <option value="Other" {{ old('payment_method', $payment->payment_method) === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Status</label>
                        <select
                            name="status"
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        >
                            <option value="Pending" {{ old('status', $payment->status) === 'Pending' ? 'selected' : '' }}>Pending</option>
                            <option value="Reconciled" {{ old('status', $payment->status) === 'Reconciled' ? 'selected' : '' }}>Reconciled</option>
                            <option value="Cancelled" {{ old('status', $payment->status) === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="Disputed" {{ old('status', $payment->status) === 'Disputed' ? 'selected' : '' }}>Disputed</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Date *</label>
                        <input
                            type="date"
                            name="date"
                            required
                            value="{{ old('date', $payment->date ? \Carbon\Carbon::parse($payment->date)->format('Y-m-d') : '') }}"
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        >
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Reference / UTR</label>
                        <input
                            type="text"
                            name="reference"
                            value="{{ old('reference', $payment->reference) }}"
                            placeholder="e.g. UPI/REF/12345"
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        >
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Notes</label>
                    <textarea
                        name="notes"
                        rows="2"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                    >{{ old('notes', $payment->notes) }}</textarea>
                </div>

                <div class="pt-2 flex items-center justify-end gap-3">
                    <a href="{{ route('payments.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl font-semibold shadow-sm transition active:scale-95">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

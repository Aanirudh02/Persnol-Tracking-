<x-app-layout title="Record Payment">
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <a href="{{ route('payments.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to Payments</a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-2">Record Payment Transaction</h1>
            <p class="text-xs text-slate-500">Record a payment made or pending reconciliation.</p>
        </div>

        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <form action="{{ route('payments.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Amount (₹) *</label>
                    <input type="number" step="0.01" name="amount" required value="{{ old('amount') }}" placeholder="0.00" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-lg font-bold text-slate-900 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Paid To *</label>
                        <input type="text" name="paid_to" required value="{{ old('paid_to') }}" placeholder="e.g. Rahul, Landlord" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Paid By</label>
                        <input type="text" name="paid_by" value="{{ old('paid_by', 'Me') }}" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Purpose *</label>
                    <input type="text" name="purpose" required value="{{ old('purpose') }}" placeholder="e.g. Shared dinner, College fee, Room rent" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                            <option value="UPI">UPI</option>
                            <option value="Bank">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                            <option value="Pending">Pending</option>
                            <option value="Reconciled">Reconciled</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Disputed">Disputed</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Date *</label>
                        <input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Reference / UTR</label>
                        <input type="text" name="reference" placeholder="e.g. UPI/REF/12345" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any additional notes..." class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white"></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-md shadow-indigo-600/25 transition">
                        Save Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

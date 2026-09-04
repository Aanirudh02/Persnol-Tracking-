<x-app-layout title="Record Money Received">
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <a href="{{ route('income.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to Income</a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-2">Record Money Received</h1>
            <p class="text-xs text-slate-500">Record pocket money, salary, friend returned money, or other income.</p>
        </div>

        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <form action="{{ route('income.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Amount (₹) *</label>
                    <input type="number" step="0.01" name="amount" required value="{{ old('amount') }}" placeholder="0.00" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-lg font-bold text-emerald-600 dark:text-emerald-400">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Source *</label>
                        <select name="source" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                            <option value="Pocket Money">Pocket Money</option>
                            <option value="Salary">Salary</option>
                            <option value="Friend Returned Money">Friend Returned Money</option>
                            <option value="Transfer">Transfer</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
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
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Date *</label>
                        <input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Time</label>
                        <input type="time" name="time" value="{{ old('time', date('H:i')) }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <input type="text" name="description" placeholder="Optional details" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any special notes..." class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white"></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm shadow-md shadow-emerald-600/25 transition">
                        Save Income Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

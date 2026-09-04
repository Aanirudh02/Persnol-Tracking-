<x-app-layout title="Edit Expense">
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <a href="{{ route('expenses.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to Expenses</a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-2">Edit Expense #{{ $expense->id }}</h1>
            <p class="text-xs text-slate-500">Changes will be recorded in the audit trail.</p>
        </div>

        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <form action="{{ route('expenses.update', $expense) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Amount (₹) *</label>
                    <input type="number" step="0.01" name="amount" required value="{{ old('amount', $expense->amount) }}" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-lg font-bold text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Description *</label>
                    <input type="text" name="description" required value="{{ old('description', $expense->description) }}" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                        <select name="category_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $expense->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                            @foreach(['UPI', 'Cash', 'Card', 'Bank', 'Other'] as $m)
                                <option value="{{ $m }}" {{ $expense->payment_method == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Date *</label>
                        <input type="date" name="date" required value="{{ old('date', $expense->date->toDateString()) }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Time</label>
                        <input type="time" name="time" value="{{ old('time', $expense->time) }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Reason for change (Audit Trail) *</label>
                    <input type="text" name="reason" required placeholder="e.g. Corrected typo in amount, updated receipt" class="w-full px-4 py-2 bg-amber-50 dark:bg-amber-950/30 border border-amber-300 dark:border-amber-800 rounded-xl text-slate-900 dark:text-white">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-md transition">
                        Update Expense & Log Audit
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

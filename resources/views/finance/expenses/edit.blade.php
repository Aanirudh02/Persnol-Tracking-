<x-app-layout title="Edit Expense">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('expenses.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">&larr; Back to Expenses</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Edit Expense #{{ $expense->id }}</h1>
            <p class="text-xs text-slate-500">Changes will be recorded in the audit trail.</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @php
                $split = $expense->friendSplit;
            @endphp
            <form action="{{ route('expenses.update', $expense) }}" method="POST" enctype="multipart/form-data" class="space-y-5 text-sm">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Amount</label>
                        <input id="expense-total" type="number" step="0.01" name="amount" required value="{{ old('amount', $expense->amount) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-lg font-bold">
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">GST</label>
                        <input type="number" step="0.01" min="0" name="gst_amount" value="{{ old('gst_amount', $expense->gst_amount) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5">
                    </div>
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Description</label>
                    <input type="text" name="description" required value="{{ old('description', $expense->description) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5">
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Category</label>
                        <select name="category_id" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $expense->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Payment Method</label>
                        <select name="payment_method" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method }}" @selected(old('payment_method', $expense->payment_method) === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Date</label>
                        <input type="date" name="date" required value="{{ old('date', $expense->date->toDateString()) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Time</label>
                        <input type="time" name="time" value="{{ old('time', $expense->time) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                    </div>
                </div>

                <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4 space-y-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Friend Split (Optional)</h2>
                        <p class="text-xs text-slate-500">Clear the friend field to remove the linked split projection.</p>
                    </div>

                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Split with friend</label>
                        <select name="split_with_friend_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">
                            <option value="">None</option>
                            @foreach($friends as $friend)
                                <option value="{{ $friend->id }}" @selected(old('split_with_friend_id', $split?->friend_id ?? $expense->split_with_friend_id) == $friend->id)>{{ $friend->name }} ({{ $friend->role }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Your share</label>
                            <input type="number" step="0.01" name="split_my_share" id="split_my_share" value="{{ old('split_my_share', $split?->my_share ?? $expense->split_my_share) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Friend share</label>
                            <input type="number" step="0.01" name="split_friend_share" id="split_friend_share" value="{{ old('split_friend_share', $split?->friend_share ?? $expense->split_friend_share) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">
                        </div>
                    </div>

                    @php
                        $mode = old('split_paid_by_type', $split?->paymentMode() ?? ($expense->paid_by_type === 'friend' ? 'friend' : ($expense->paid_by_type === 'split' ? 'split' : 'me')));
                    @endphp
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Who paid?</label>
                            <select name="split_paid_by_type" id="split_paid_by_type" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">
                                <option value="me" @selected($mode === 'me')>I paid</option>
                                <option value="friend" @selected($mode === 'friend')>Friend paid</option>
                                <option value="split" @selected($mode === 'split')>Split payment</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Paid by me</label>
                            <input type="number" step="0.01" name="split_paid_by_me_amount" id="split_paid_by_me_amount" value="{{ old('split_paid_by_me_amount', $split?->paid_by_me_amount) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Paid by friend</label>
                            <input type="number" step="0.01" name="split_paid_by_friend_amount" id="split_paid_by_friend_amount" value="{{ old('split_paid_by_friend_amount', $split?->paid_by_friend_amount) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Reason for change</label>
                    <input type="text" name="reason" required placeholder="Why is this being updated?" class="w-full rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5">
                </div>

                <label class="flex items-center gap-2 font-semibold text-slate-700">
                    <input type="checkbox" name="is_voluntary" value="1" @checked(old('is_voluntary', $expense->is_voluntary)) class="rounded border-slate-300">
                    Voluntary spend
                </label>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Receipt</label>
                    <input type="file" name="receipt_image" accept="image/*" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Notes</label>
                    <textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">{{ old('notes', $expense->notes) }}</textarea>
                </div>

                <button type="submit" class="w-full rounded-xl bg-indigo-600 py-2.5 font-semibold text-white">Update Expense</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const totalEl = document.getElementById('expense-total');
            const gstEl = document.querySelector('input[name="gst_amount"]');
            const modeEl = document.getElementById('split_paid_by_type');
            const paidByMeEl = document.getElementById('split_paid_by_me_amount');
            const paidByFriendEl = document.getElementById('split_paid_by_friend_amount');

            const totalAmount = () => Number(totalEl.value || 0) + Number(gstEl.value || 0);
            const applyMode = () => {
                const total = totalAmount();
                if (modeEl.value === 'me') {
                    paidByMeEl.value = total.toFixed(2);
                    paidByFriendEl.value = '0.00';
                    paidByMeEl.readOnly = true;
                    paidByFriendEl.readOnly = true;
                } else if (modeEl.value === 'friend') {
                    paidByMeEl.value = '0.00';
                    paidByFriendEl.value = total.toFixed(2);
                    paidByMeEl.readOnly = true;
                    paidByFriendEl.readOnly = true;
                } else {
                    paidByMeEl.readOnly = false;
                    paidByFriendEl.readOnly = false;
                }
            };

            totalEl?.addEventListener('input', applyMode);
            gstEl?.addEventListener('input', applyMode);
            modeEl?.addEventListener('change', applyMode);
            applyMode();
        });
    </script>
</x-app-layout>

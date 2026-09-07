<x-app-layout title="Edit Split Record">
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <a href="{{ route('friends.index') }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600">&larr; Back to Friends & Splits</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Edit Split Record</h1>
            <p class="text-xs text-slate-500">Update the shares and actual payer amounts for this standalone split.</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <form action="{{ route('friend-splits.update', $friendSplit) }}" method="POST" class="space-y-4 text-sm" id="edit-split-form">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Friend</label>
                        <select name="friend_id" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                            @foreach($friends as $friend)
                                <option value="{{ $friend->id }}" @selected(old('friend_id', $friendSplit->friend_id) == $friend->id)>{{ $friend->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Date</label>
                        <input type="date" name="date" value="{{ old('date', $friendSplit->date->toDateString()) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                    </div>
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Description</label>
                    <input type="text" name="description" value="{{ old('description', $friendSplit->description) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Total amount</label>
                        <input type="number" step="0.01" name="total_amount" id="total_amount" value="{{ old('total_amount', $friendSplit->total_amount) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Your share</label>
                        <input type="number" step="0.01" name="my_share" value="{{ old('my_share', $friendSplit->my_share) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Friend share</label>
                        <input type="number" step="0.01" name="friend_share" value="{{ old('friend_share', $friendSplit->friend_share) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                    </div>
                </div>

                @php
                    $mode = old('paid_by_mode', $friendSplit->paymentMode());
                @endphp
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Who paid?</label>
                            <select name="paid_by_mode" id="paid_by_mode" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5">
                                <option value="me" @selected($mode === 'me')>I paid</option>
                                <option value="friend" @selected($mode === 'friend')>Friend paid</option>
                                <option value="split" @selected($mode === 'split')>Split payment</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Paid by me</label>
                            <input type="number" step="0.01" name="paid_by_me_amount" id="paid_by_me_amount" value="{{ old('paid_by_me_amount', $friendSplit->paid_by_me_amount) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5">
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Paid by friend</label>
                            <input type="number" step="0.01" name="paid_by_friend_amount" id="paid_by_friend_amount" value="{{ old('paid_by_friend_amount', $friendSplit->paid_by_friend_amount) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Payment method</label>
                    <select name="payment_method" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method }}" @selected(old('payment_method', $friendSplit->payment_method) === $method)>{{ $method }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Notes</label>
                    <textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">{{ old('notes', $friendSplit->notes) }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('friends.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-slate-600 transition hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-2.5 font-semibold text-white transition hover:bg-indigo-500">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const totalEl = document.getElementById('total_amount');
            const modeEl = document.getElementById('paid_by_mode');
            const meEl = document.getElementById('paid_by_me_amount');
            const friendEl = document.getElementById('paid_by_friend_amount');

            const applyMode = () => {
                const total = Number(totalEl.value || 0);
                if (modeEl.value === 'me') {
                    meEl.value = total.toFixed(2);
                    friendEl.value = '0.00';
                    meEl.readOnly = true;
                    friendEl.readOnly = true;
                } else if (modeEl.value === 'friend') {
                    meEl.value = '0.00';
                    friendEl.value = total.toFixed(2);
                    meEl.readOnly = true;
                    friendEl.readOnly = true;
                } else {
                    meEl.readOnly = false;
                    friendEl.readOnly = false;
                }
            };

            totalEl?.addEventListener('input', applyMode);
            modeEl?.addEventListener('change', applyMode);
            applyMode();
        });
    </script>
</x-app-layout>

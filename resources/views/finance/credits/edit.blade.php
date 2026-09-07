<x-app-layout title="Edit Credit / Debt">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('credits.index', ['type' => $creditDebt->type]) }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600">&larr; Back to {{ $creditDebt->type === 'credit' ? 'Credits' : 'Debts' }}</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Edit {{ $creditDebt->type === 'credit' ? 'Credit' : 'Debt' }}</h1>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <form action="{{ route('credits.update', $creditDebt) }}" method="POST" class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                @csrf
                @method('PUT')
                <div class="sm:col-span-2">
                    <label class="font-semibold text-slate-700">Friend</label>
                    <select name="friend_id" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                        @foreach($friends as $friend)
                            <option value="{{ $friend->id }}" @selected(old('friend_id', $creditDebt->friend_id) == $friend->id)>{{ $friend->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="font-semibold text-slate-700">Type</label>
                    <select name="type" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                        <option value="credit" @selected(old('type', $creditDebt->type) === 'credit')>Credit (I owe)</option>
                        <option value="debt" @selected(old('type', $creditDebt->type) === 'debt')>Debt (owes me)</option>
                    </select>
                </div>
                <div>
                    <label class="font-semibold text-slate-700">Total Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $creditDebt->amount) }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                </div>
                <div>
                    <label class="font-semibold text-slate-700">Amount Paid</label>
                    <input type="number" step="0.01" min="0" name="amount_paid" value="{{ old('amount_paid', $creditDebt->amount_paid) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                </div>
                <div>
                    <label class="font-semibold text-slate-700">Date</label>
                    <input type="date" name="date" value="{{ old('date', $creditDebt->date->toDateString()) }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                </div>
                <div>
                    <label class="font-semibold text-slate-700">Payment Method (if paying now)</label>
                    <select name="payment_method" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                        <option value="">None / Existing</option>
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
                <div>
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
                <div class="sm:col-span-2 flex items-center justify-end gap-3">
                    <a href="{{ route('credits.index', ['type' => $creditDebt->type]) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-slate-600">Cancel</a>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-2.5 font-semibold text-white">Save Changes</button>
                </div>
            </form>
        </div>

        @if($creditDebt->payments->isNotEmpty())
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-bold text-slate-900">Existing Payments</h2>
                <div class="space-y-3">
                    @foreach($creditDebt->payments as $payment)
                        <div class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm">
                            <div>
                                <div class="font-semibold text-slate-800">₹{{ number_format($payment->amount, 2) }}</div>
                                <div class="text-xs text-slate-500">{{ $payment->paid_on->format('d M Y') }} · {{ $payment->payment_method ?: 'No method' }}</div>
                            </div>
                            <div class="text-xs text-slate-400">{{ $payment->notes ?: '—' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>

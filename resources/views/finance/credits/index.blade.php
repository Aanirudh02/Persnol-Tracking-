<x-app-layout title="{{ $type === 'debt' ? 'Debts' : 'Credits' }}">
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $type === 'debt' ? 'Debts' : 'Credits' }}</h1>
                <p class="text-sm text-slate-500">
                    {{ $type === 'debt' ? 'Money others owe you.' : 'Money you owe others.' }}
                    Open total: <strong>₹{{ number_format($openTotal, 2) }}</strong>
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('credits.index', ['type' => 'credit']) }}" class="rounded-xl px-3 py-2 text-sm font-semibold {{ $type === 'credit' ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white' }}">Credits</a>
                <a href="{{ route('credits.index', ['type' => 'debt']) }}" class="rounded-xl px-3 py-2 text-sm font-semibold {{ $type === 'debt' ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white' }}">Debts</a>
            </div>
        </div>

        <form action="{{ route('credits.store') }}" method="POST" class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm sm:grid-cols-2">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                @if($type === 'credit')
                    Credit means <strong>you owe the friend</strong>.
                @else
                    Debt means <strong>the friend owes you</strong>.
                @endif
            </div>
            <div class="sm:col-span-2">
                <label class="font-semibold text-slate-700">Friend</label>
                <select name="friend_id" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                    @foreach($friends as $friend)
                        <option value="{{ $friend->id }}">{{ $friend->name }} ({{ $friend->role }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="font-semibold text-slate-700">Amount</label>
                <input type="number" step="0.01" name="amount" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
            </div>
            <div>
                <label class="font-semibold text-slate-700">Initial paid</label>
                <input type="number" step="0.01" name="amount_paid" value="0" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
            </div>
            <div>
                <label class="font-semibold text-slate-700">Date</label>
                <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
            </div>
            <div>
                <label class="font-semibold text-slate-700">Payment method</label>
                <select name="payment_method" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method }}">{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="font-semibold text-slate-700">Due date</label>
                <input type="date" name="due_date" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
            </div>
            <div>
                <label class="font-semibold text-slate-700">Status</label>
                <select name="status" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                    @foreach($statuses as $status)
                        <option value="{{ $status['name'] }}">{{ $status['icon'] ?: ucfirst(str_replace('_', ' ', $status['name'])) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="font-semibold text-slate-700">Description</label>
                <input type="text" name="description" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5" placeholder="Lunch, advance, ticket share...">
            </div>
            <div class="sm:col-span-2">
                <label class="font-semibold text-slate-700">Notes</label>
                <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5"></textarea>
            </div>
            <button class="sm:col-span-2 rounded-xl bg-slate-900 py-2.5 font-semibold text-white">Save {{ $type }}</button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <div class="space-y-3 p-4 text-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900">{{ $item->friend?->name ?? 'Friend' }}</p>
                                <p class="text-slate-500">{{ $item->description ?: 'No description' }} · {{ $item->date->format('d M Y') }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $item->statusLabel() }}</p>
                            </div>
                            <div class="grid min-w-[280px] grid-cols-3 gap-2 text-center">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-2 py-2">
                                    <p class="text-[10px] font-semibold uppercase text-slate-400">Original</p>
                                    <p class="text-sm font-bold text-slate-800">₹{{ number_format($item->amount, 2) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-2 py-2">
                                    <p class="text-[10px] font-semibold uppercase text-slate-400">Paid</p>
                                    <p class="text-sm font-bold text-slate-800">₹{{ number_format($item->amount_paid, 2) }}</p>
                                </div>
                                <div class="rounded-xl border-2 border-amber-400 bg-amber-50 px-2 py-2">
                                    <p class="text-[10px] font-semibold uppercase text-amber-700">Left</p>
                                    <p class="text-base font-bold text-amber-800">₹{{ number_format($item->remaining(), 2) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            <a href="{{ route('credits.edit', $item) }}" class="font-semibold text-indigo-600 hover:underline">Edit</a>
                            <form action="{{ route('credits.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete this {{ $item->type }} record?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-semibold text-rose-600 hover:underline">Delete</button>
                            </form>
                        </div>

                        @if($item->payments->isNotEmpty())
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-3">
                                <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Payments</h3>
                                <div class="space-y-2">
                                    @foreach($item->payments as $payment)
                                        <form action="{{ route('credits.payments.update', [$item, $payment]) }}" method="POST" class="grid grid-cols-1 gap-2 rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-[1fr_1fr_1fr_auto_auto]">
                                            @csrf
                                            @method('PUT')
                                            <input type="number" step="0.01" name="amount" value="{{ $payment->amount }}" class="rounded-lg border border-slate-300 px-2 py-2">
                                            <input type="date" name="paid_on" value="{{ $payment->paid_on->toDateString() }}" class="rounded-lg border border-slate-300 px-2 py-2">
                                            <select name="payment_method" class="rounded-lg border border-slate-300 px-2 py-2">
                                                @foreach($paymentMethods as $method)
                                                    <option value="{{ $method }}" @selected($payment->payment_method === $method)>{{ $method }}</option>
                                                @endforeach
                                            </select>
                                            <button class="rounded-lg bg-slate-900 px-3 py-2 font-semibold text-white">Update</button>
                                            <button form="delete-payment-{{ $payment->id }}" type="submit" class="hidden"></button>
                                        </form>
                                        <form id="delete-payment-{{ $payment->id }}" action="{{ route('credits.payments.destroy', [$item, $payment]) }}" method="POST" class="-mt-1 text-right">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">Delete payment</button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($item->remaining() > 0)
                            <form action="{{ route('credits.payments', $item) }}" method="POST" class="grid grid-cols-1 gap-2 sm:grid-cols-4">
                                @csrf
                                <input type="number" step="0.01" name="amount" required placeholder="Pay amount" class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2">
                                <input type="date" name="paid_on" value="{{ date('Y-m-d') }}" required class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2">
                                <select name="payment_method" class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2">
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method }}">{{ $method }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-xl bg-slate-900 px-2 py-2 font-semibold text-white">Record payment</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="p-6 text-sm text-slate-400">No {{ $type }} records yet.</p>
                @endforelse
            </div>
            <div class="p-4">{{ $items->links() }}</div>
        </div>
    </div>
</x-app-layout>

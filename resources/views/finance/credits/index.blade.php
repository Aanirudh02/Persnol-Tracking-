<x-app-layout title="{{ $type === 'debt' ? 'Debts' : 'Credits' }}">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-end justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $type === 'debt' ? 'Debts' : 'Credits' }}</h1>
                <p class="text-sm text-slate-500">
                    {{ $type === 'debt' ? 'Money others owe you (they owe me).' : 'Money you owe others (I owe them).' }}
                    Open total: <strong>₹{{ number_format($openTotal, 2) }}</strong>
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('credits.index', ['type' => 'credit']) }}" class="px-3 py-2 rounded-xl text-sm font-semibold {{ $type === 'credit' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200' }}">Credits (I owe)</a>
                <a href="{{ route('credits.index', ['type' => 'debt']) }}" class="px-3 py-2 rounded-xl text-sm font-semibold {{ $type === 'debt' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200' }}">Debts (owe me)</a>
            </div>
        </div>

        <form action="{{ route('credits.store') }}" method="POST" class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="sm:col-span-2 rounded-xl bg-slate-50 border border-slate-200 px-3 py-2 text-xs text-slate-600">
                @if($type === 'credit')
                    Recording a <strong>Credit</strong> means <strong>you owe this friend</strong>.
                @else
                    Recording a <strong>Debt</strong> means <strong>this friend owes you</strong>.
                @endif
            </div>
            <div class="sm:col-span-2">
                <label class="font-semibold text-slate-700">Friend *</label>
                <select name="friend_id" required class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    @foreach($friends as $f)
                        <option value="{{ $f->id }}">{{ $f->name }} ({{ $f->role }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="font-semibold text-slate-700">Amount *</label>
                <input type="number" step="0.01" name="amount" required class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
            </div>
            <div>
                <label class="font-semibold text-slate-700">Already paid</label>
                <input type="number" step="0.01" name="amount_paid" value="0" class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
            </div>
            <div>
                <label class="font-semibold text-slate-700">Date *</label>
                <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
            </div>
            <div>
                <label class="font-semibold text-slate-700">Due date</label>
                <input type="date" name="due_date" class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
            </div>
            <div class="sm:col-span-2">
                <label class="font-semibold text-slate-700">Description</label>
                <input type="text" name="description" class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl" placeholder="e.g. Lunch">
            </div>
            <div class="sm:col-span-2">
                <label class="font-semibold text-slate-700">Notes</label>
                <textarea name="notes" rows="2" class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl"></textarea>
            </div>
            <button class="sm:col-span-2 py-2.5 rounded-xl bg-slate-900 text-white font-semibold">Save {{ $type === 'debt' ? 'debt' : 'credit' }}</button>
        </form>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <div class="p-4 text-sm space-y-3">
                        <div class="flex flex-col sm:flex-row sm:justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900">{{ $item->friend?->name ?? 'Friend' }}</p>
                                <p class="text-slate-500 break-words">{{ $item->description ?? '—' }} · {{ $item->date->format('d M Y') }}</p>
                                <p class="text-xs text-slate-400 mt-1">{{ $item->statusLabel() }}</p>
                            </div>
                            <div class="shrink-0 grid grid-cols-3 gap-2 text-center sm:min-w-[280px]">
                                <div class="rounded-xl bg-slate-50 border border-slate-200 px-2 py-2">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Original</p>
                                    <p class="text-sm font-bold text-slate-800">₹{{ number_format($item->amount, 2) }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 border border-slate-200 px-2 py-2">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Paid</p>
                                    <p class="text-sm font-bold text-slate-800">₹{{ number_format($item->amount_paid, 2) }}</p>
                                </div>
                                <div class="rounded-xl border-2 border-amber-400 bg-amber-50 px-2 py-2">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-amber-700">Left</p>
                                    <p class="text-base font-bold text-amber-800">₹{{ number_format($item->remaining(), 2) }}</p>
                                </div>
                            </div>
                        </div>
                        @if($item->remaining() > 0)
                            <form action="{{ route('credits.payments', $item) }}" method="POST" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                @csrf
                                <input type="number" step="0.01" name="amount" required placeholder="Pay amount" class="px-2 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                                <input type="date" name="paid_on" value="{{ date('Y-m-d') }}" required class="px-2 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                                <select name="payment_method" class="px-2 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                                    @foreach($paymentMethods as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                                </select>
                                <button class="px-2 py-2 rounded-xl bg-slate-900 text-white font-semibold">Record payment</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="p-6 text-slate-400 text-sm">No {{ $type }} records yet.</p>
                @endforelse
            </div>
            <div class="p-4">{{ $items->links() }}</div>
        </div>
    </div>
</x-app-layout>

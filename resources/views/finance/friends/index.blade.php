<x-app-layout title="Friends & Splits">
    <div class="space-y-6">
        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800 shadow-sm dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <span>⚠️ Please correct the following errors:</span>
                </div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Friends & Splits</h1>
                <p class="text-xs text-slate-500">
                    Owed to you: <span class="font-bold text-emerald-600">₹{{ number_format($totalOwedToMe, 2) }}</span>
                    <span class="mx-1">&bull;</span>
                    You owe: <span class="font-bold text-rose-600">₹{{ number_format($totalIOwe, 2) }}</span>
                </p>
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('add-friend-modal').classList.remove('hidden')" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                    + Add Friend
                </button>
                <button type="button" onclick="document.getElementById('add-split-modal').classList.remove('hidden')" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-md shadow-indigo-600/20 transition hover:bg-indigo-500">
                    + Add Split Record
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            @forelse($friendData as $fd)
                @php
                    $friend = $fd['friend'];
                    $balance = $fd['balance'];
                @endphp
                <div class="space-y-4 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">{{ $friend->name }}</h2>
                            <p class="text-xs text-slate-400">{{ $friend->phone ?: ($friend->email ?: 'No contact added') }}</p>
                            <span class="mt-2 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{{ $friend->role }}</span>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-3 py-2 text-right text-xs">
                            <div class="text-slate-400">Net</div>
                            <div class="font-bold {{ $balance['net'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $balance['net'] >= 0 ? '₹'.number_format($balance['net'], 2).' owed to you' : '₹'.number_format(abs($balance['net']), 2).' you owe' }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="rounded-2xl border border-rose-100 bg-rose-50 px-3 py-3">
                            <div class="text-slate-500">You owe</div>
                            <div class="mt-1 text-lg font-bold text-rose-600">₹{{ number_format($balance['i_owe_friend'], 2) }}</div>
                        </div>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-3 py-3">
                            <div class="text-slate-500">{{ $friend->name }} owes you</div>
                            <div class="mt-1 text-lg font-bold text-emerald-600">₹{{ number_format($balance['friend_owes_me'], 2) }}</div>
                        </div>
                    </div>

                    @if($balance['net'] !== 0.0)
                        <button
                            type="button"
                            onclick="window.openSettleModal({{ $friend->id }}, '{{ $friend->name }}', {{ abs($balance['net']) }}, '{{ $balance['net'] < 0 ? 'i_paid_friend' : 'friend_paid_me' }}')"
                            class="w-full rounded-xl bg-emerald-600 py-2 text-xs font-semibold text-white transition hover:bg-emerald-500"
                        >
                            Record Settlement
                        </button>
                    @endif

                    <div class="space-y-2 rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-900">Split Records</h3>
                            <span class="text-[10px] text-slate-400">Authoritative split rows</span>
                        </div>

                        @forelse($fd['split_records'] as $split)
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 px-3 py-3 text-xs">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800">{{ $split->description }}</p>
                                        <p class="mt-1 text-slate-500">
                                            {{ $split->date->format('d M Y') }}
                                            <span class="mx-1">&bull;</span>
                                            {{ $split->payment_method ?: 'No method' }}
                                            <span class="mx-1">&bull;</span>
                                            {{ $split->paymentMode() === 'split' ? 'Split payment' : ($split->paymentMode() === 'friend' ? 'Friend paid' : 'You paid') }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-slate-900">₹{{ number_format($split->total_amount, 2) }}</div>
                                        <div class="text-[10px] {{ $split->netAmount() >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ $split->netAmount() >= 0 ? 'Net +₹'.number_format($split->netAmount(), 2) : 'Net -₹'.number_format(abs($split->netAmount()), 2) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 grid grid-cols-2 gap-2 text-[11px] text-slate-600">
                                    <div class="rounded-xl bg-white px-2 py-2">Share: you ₹{{ number_format($split->my_share, 2) }}</div>
                                    <div class="rounded-xl bg-white px-2 py-2">Share: friend ₹{{ number_format($split->friend_share, 2) }}</div>
                                    <div class="rounded-xl bg-white px-2 py-2">Paid: you ₹{{ number_format($split->paid_by_me_amount, 2) }}</div>
                                    <div class="rounded-xl bg-white px-2 py-2">Paid: friend ₹{{ number_format($split->paid_by_friend_amount, 2) }}</div>
                                </div>
                                <div class="mt-3 flex items-center justify-end gap-3">
                                    @if(!$split->expense_id)
                                        <a href="{{ route('friend-splits.edit', $split) }}" class="font-semibold text-indigo-600 hover:underline">Edit</a>
                                        <form action="{{ route('friend-splits.destroy', $split) }}" method="POST" onsubmit="return confirm('Delete this split record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-semibold text-rose-600 hover:underline">Delete</button>
                                        </form>
                                    @else
                                        <a href="{{ route('expenses.edit', $split->expense_id) }}" class="font-semibold text-indigo-600 hover:underline">Edit from expense</a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">No split records yet.</p>
                        @endforelse
                    </div>

                    <div class="space-y-2 rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-900">Credits / Debts</h3>
                            <a href="{{ route('credits.index', ['type' => 'credit']) }}" class="text-[11px] font-semibold text-slate-600 hover:underline">Open module</a>
                        </div>

                        @forelse($fd['open_items'] as $item)
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 px-3 py-3 text-xs">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <span class="rounded bg-slate-900 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                                            {{ $item->type === 'credit' ? 'Credit (I owe)' : 'Debt (owes me)' }}
                                        </span>
                                        <p class="mt-2 font-semibold text-slate-800">{{ $item->description ?: 'No description' }}</p>
                                        <p class="text-slate-500">{{ $item->date->format('d M Y') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-slate-900">₹{{ number_format($item->amount, 2) }}</div>
                                        <div class="text-[10px] text-amber-700">Left ₹{{ number_format($item->remaining(), 2) }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="text-[11px] text-slate-500">{{ $item->payments->count() }} payment record(s)</span>
                                    <a href="{{ route('credits.edit', $item) }}" class="font-semibold text-indigo-600 hover:underline">Manage</a>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">No open credit/debt records.</p>
                        @endforelse
                    </div>

                    @if($fd['recent_settlements']->isNotEmpty())
                        <div class="space-y-2 rounded-2xl border border-slate-200 p-4">
                            <h3 class="text-sm font-bold text-slate-900">Recent Settlements</h3>
                            @foreach($fd['recent_settlements'] as $settlement)
                                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-xs">
                                    <div>
                                        <div class="font-semibold text-slate-800">{{ $settlement->direction === 'i_paid_friend' ? 'You paid friend' : 'Friend paid you' }}</div>
                                        <div class="text-slate-500">{{ $settlement->date->format('d M Y') }} · {{ $settlement->payment_method }}</div>
                                    </div>
                                    <div class="font-bold text-slate-900">₹{{ number_format($settlement->amount, 2) }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-400">
                    No friends added yet.
                </div>
            @endforelse

            <div id="add-friend-modal" class="fixed inset-0 z-50 hidden overflow-y-auto overscroll-contain bg-black/60 p-2 sm:p-6 backdrop-blur-sm" onclick="if(event.target === this) this.classList.add('hidden')">
        <div class="flex min-h-full items-center justify-center py-2 sm:py-4">
            <div class="w-full max-w-md max-h-[calc(100dvh-1rem)] sm:max-h-[92vh] flex flex-col rounded-2xl sm:rounded-3xl border border-slate-200 bg-white p-4 sm:p-6 shadow-2xl overflow-hidden my-auto" onclick="event.stopPropagation()">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3 shrink-0">
                    <h3 class="text-lg font-bold text-slate-900">Add Friend</h3>
                    <button type="button" onclick="document.getElementById('add-friend-modal').classList.add('hidden')" class="text-2xl font-bold text-slate-400 hover:text-slate-600 transition p-1">&times;</button>
                </div>

                <div class="min-h-0 overflow-y-auto overscroll-contain pr-1 flex-1">
                    <form action="{{ route('friends.store') }}" method="POST" class="space-y-4 text-sm">
                        @csrf
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block font-semibold text-slate-700">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold text-slate-700">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Role</label>
                            <select name="user_type" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                                <option value="Friend">Friend</option>
                                <option value="Not a Friend">Not a Friend</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Date of birth</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Notes</label>
                            <textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">{{ old('notes') }}</textarea>
                        </div>
                        <div class="pt-2 sticky bottom-0 bg-white pb-1">
                            <button type="submit" class="w-full rounded-xl bg-indigo-600 py-3 font-semibold text-white shadow-md shadow-indigo-600/20 transition hover:bg-indigo-500 text-sm">Save Friend</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="add-split-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/60 p-3 sm:p-6 backdrop-blur-sm" onclick="if(event.target === this) this.classList.add('hidden')">
        <div class="flex min-h-full items-center justify-center py-2 sm:py-6">
            <div class="w-full max-w-2xl max-h-[92vh] flex flex-col rounded-3xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto" onclick="event.stopPropagation()">
                <div class="p-4 sm:p-6 pb-3 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Add Split Record</h3>
                        <p class="text-xs text-slate-500">Store shares and actual amounts separately. This does not create a normal expense row.</p>
                    </div>
                    <button type="button" onclick="document.getElementById('add-split-modal').classList.add('hidden')" class="text-2xl font-bold text-slate-400 hover:text-slate-600 transition p-1">&times;</button>
                </div>

                <div class="overflow-y-auto p-4 sm:p-6 pt-3 flex-1 space-y-4">
                    <form action="{{ route('friend-splits.store') }}" method="POST" class="space-y-4 text-sm" id="split-form">
                        @csrf
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block font-semibold text-slate-700">Friend</label>
                                <select name="friend_id" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                                    @foreach($friendData as $fd)
                                        <option value="{{ $fd['friend']->id }}">{{ $fd['friend']->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold text-slate-700">Date</label>
                                <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Description</label>
                            <input type="text" name="description" required placeholder="Dinner, cab fare, tickets..." class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block font-semibold text-slate-700">Total amount</label>
                                <input type="number" step="0.01" name="total_amount" id="split-total-amount" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold text-slate-700">Your share</label>
                                <input type="number" step="0.01" name="my_share" id="split-my-share" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold text-slate-700">Friend share</label>
                                <input type="number" step="0.01" name="friend_share" id="split-friend-share" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="mb-1 block font-semibold text-slate-700">Who paid?</label>
                                    <select name="paid_by_mode" id="split-paid-mode" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5">
                                        <option value="me">I paid</option>
                                        <option value="friend">Friend paid</option>
                                        <option value="split">Split payment</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block font-semibold text-slate-700">Paid by me</label>
                                    <input type="number" step="0.01" name="paid_by_me_amount" id="split-paid-by-me" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5">
                                </div>
                                <div>
                                    <label class="mb-1 block font-semibold text-slate-700">Paid by friend</label>
                                    <input type="number" step="0.01" name="paid_by_friend_amount" id="split-paid-by-friend" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5">
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <button type="button" onclick="window.fillEqualSplit()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-100 transition">Equal split</button>
                                <button type="button" onclick="window.fillFullMine()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-100 transition">All mine</button>
                                <button type="button" onclick="window.fillFullFriend()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-100 transition">All friend</button>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Payment method</label>
                            <select name="payment_method" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method }}">{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Notes</label>
                            <textarea name="notes" rows="2" placeholder="Any optional notes or context..." class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5"></textarea>
                        </div>

                        <div class="pt-2 sticky bottom-0 bg-white pb-1">
                            <button type="submit" class="w-full rounded-xl bg-indigo-600 py-3 font-semibold text-white shadow-md shadow-indigo-600/20 transition hover:bg-indigo-500 text-sm">Save Split Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="settle-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/60 p-3 sm:p-6 backdrop-blur-sm" onclick="if(event.target === this) this.classList.add('hidden')">
        <div class="flex min-h-full items-center justify-center py-4">
            <div class="w-full max-w-sm max-h-[92vh] flex flex-col rounded-3xl border border-slate-200 bg-white p-5 sm:p-6 shadow-2xl overflow-hidden my-auto" onclick="event.stopPropagation()">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3 shrink-0">
                    <h3 class="text-base font-bold text-slate-900">Record Settlement</h3>
                    <button type="button" onclick="document.getElementById('settle-modal').classList.add('hidden')" class="text-2xl font-bold text-slate-400 hover:text-slate-600 transition p-1">&times;</button>
                </div>
                <div class="overflow-y-auto pr-1 flex-1">
                    <form id="settle-form" action="" method="POST" class="space-y-3 text-sm">
                        @csrf
                        <div>
                            <label class="mb-1 block text-slate-500">Friend</label>
                            <input type="text" id="settle-name" readonly class="w-full rounded-xl bg-slate-100 px-3 py-2.5 font-semibold">
                        </div>
                        <div>
                            <label class="mb-1 block text-slate-500">Amount</label>
                            <input type="number" step="0.01" name="amount" id="settle-amount" required class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 font-semibold">
                        </div>
                        <input type="hidden" name="direction" id="settle-direction">
                        <div>
                            <label class="mb-1 block text-slate-500">Payment method</label>
                            <select name="payment_method" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method }}">{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-slate-500">Notes</label>
                            <textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5"></textarea>
                        </div>
                        <div class="pt-2 sticky bottom-0 bg-white pb-1">
                            <button type="submit" class="w-full rounded-xl bg-emerald-600 py-3 font-semibold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-500 text-sm">Save Settlement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.fillEqualSplit = function () {
            const total = Number(document.getElementById('split-total-amount').value || 0);
            const half = (total / 2).toFixed(2);
            document.getElementById('split-my-share').value = half;
            document.getElementById('split-friend-share').value = (total - Number(half)).toFixed(2);
        };

        window.fillFullMine = function () {
            const total = Number(document.getElementById('split-total-amount').value || 0).toFixed(2);
            document.getElementById('split-my-share').value = total;
            document.getElementById('split-friend-share').value = '0.00';
        };

        window.fillFullFriend = function () {
            const total = Number(document.getElementById('split-total-amount').value || 0).toFixed(2);
            document.getElementById('split-my-share').value = '0.00';
            document.getElementById('split-friend-share').value = total;
        };

        window.updatePaidAmounts = function () {
            const total = Number(document.getElementById('split-total-amount').value || 0);
            const mode = document.getElementById('split-paid-mode').value;
            const paidByMe = document.getElementById('split-paid-by-me');
            const paidByFriend = document.getElementById('split-paid-by-friend');

            if (mode === 'me') {
                paidByMe.value = total.toFixed(2);
                paidByFriend.value = '0.00';
                paidByMe.readOnly = true;
                paidByFriend.readOnly = true;
            } else if (mode === 'friend') {
                paidByMe.value = '0.00';
                paidByFriend.value = total.toFixed(2);
                paidByMe.readOnly = true;
                paidByFriend.readOnly = true;
            } else {
                if (!paidByMe.value) paidByMe.value = (total / 2).toFixed(2);
                if (!paidByFriend.value) paidByFriend.value = (total - Number(paidByMe.value || 0)).toFixed(2);
                paidByMe.readOnly = false;
                paidByFriend.readOnly = false;
            }
        };

        window.openSettleModal = function (friendId, friendName, amount, direction) {
            document.getElementById('settle-form').action = `/finance/friends/${friendId}/settle`;
            document.getElementById('settle-name').value = friendName;
            document.getElementById('settle-amount').value = amount.toFixed(2);
            document.getElementById('settle-direction').value = direction;
            document.getElementById('settle-modal').classList.remove('hidden');
        };

        document.getElementById('split-total-amount')?.addEventListener('input', () => {
            window.updatePaidAmounts();
        });
        document.getElementById('split-paid-mode')?.addEventListener('change', () => {
            window.updatePaidAmounts();
        });
        window.updatePaidAmounts();
    </script>
</x-app-layout>

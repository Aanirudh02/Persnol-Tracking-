<x-app-layout title="Friends & Shared Debts">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Friends & Shared Expenses</h1>
                <p class="text-xs text-slate-500">
                    Owed to you: <span class="font-bold text-emerald-600">₹{{ number_format($totalOwedToMe, 2) }}</span> &bull;
                    You owe: <span class="font-bold text-rose-600">₹{{ number_format($totalIOwe, 2) }}</span>
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" onclick="document.getElementById('add-friend-modal').classList.remove('hidden')" class="px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 transition">
                    + Add User
                </button>
                <button onclick="document.getElementById('add-split-modal').classList.remove('hidden')" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-md shadow-indigo-600/20 transition">
                    + Record Split / Debt
                </button>
            </div>
        </div>

        <!-- Friend Balance Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach($friendData as $fd)
                @php
                    $f = $fd['friend'];
                    $b = $fd['balance'];
                @endphp
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="font-bold text-base text-slate-900 dark:text-white">{{ $f->name }}</h2>
                            <span class="text-xs text-slate-400">{{ $f->phone ?? $f->email ?? '—' }}</span>
                            <span class="mt-1 inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $f->isFriend() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $f->role ?? 'Friend' }}</span>
                        </div>
                        <span class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                            {{ substr($f->name, 0, 1) }}
                        </span>
                    </div>

                    <!-- Balance breakdown -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-xs space-y-1.5">
                        <div class="flex justify-between text-slate-600 dark:text-slate-400">
                            <span>You owe {{ $f->name }}:</span>
                            <span class="font-mono font-semibold text-rose-500">₹{{ number_format($b['i_owe_friend'], 2) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600 dark:text-slate-400">
                            <span>{{ $f->name }} owes you:</span>
                            <span class="font-mono font-semibold text-emerald-500">₹{{ number_format($b['friend_owes_me'], 2) }}</span>
                        </div>
                        <div class="pt-1.5 border-t border-slate-200 dark:border-slate-700 flex justify-between font-bold text-sm">
                            <span>Net left:</span>
                            <span class="{{ $b['net'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($b['net'] < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400') }}">
                                @if($b['net'] > 0)
                                    Owes you ₹{{ number_format($b['net'], 2) }}
                                @elseif($b['net'] < 0)
                                    You owe ₹{{ number_format(abs($b['net']), 2) }}
                                @else
                                    Settled
                                @endif
                            </span>
                        </div>
                    </div>

                    @if($fd['open_items']->count())
                        <div class="space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Open credits / debts</p>
                            @foreach($fd['open_items'] as $item)
                                <div class="rounded-2xl border border-slate-200 p-3 space-y-2">
                                    <div class="flex justify-between gap-2 text-xs">
                                        <div class="min-w-0">
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold {{ $item->type === 'credit' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
                                                {{ $item->type === 'credit' ? 'Credit · I owe' : 'Debt · owes me' }}
                                            </span>
                                            <p class="mt-1 text-slate-600 truncate">{{ $item->description ?? '—' }} · {{ $item->date->format('d M') }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-1.5 text-center">
                                        <div class="rounded-lg bg-slate-50 border border-slate-200 px-1.5 py-1.5">
                                            <p class="text-[9px] font-semibold uppercase text-slate-400">Original</p>
                                            <p class="text-xs font-bold text-slate-800">₹{{ number_format($item->amount, 2) }}</p>
                                        </div>
                                        <div class="rounded-lg bg-slate-50 border border-slate-200 px-1.5 py-1.5">
                                            <p class="text-[9px] font-semibold uppercase text-slate-400">Paid</p>
                                            <p class="text-xs font-bold text-slate-800">₹{{ number_format($item->amount_paid, 2) }}</p>
                                        </div>
                                        <div class="rounded-lg border-2 border-amber-400 bg-amber-50 px-1.5 py-1.5">
                                            <p class="text-[9px] font-semibold uppercase text-amber-700">Left</p>
                                            <p class="text-sm font-bold text-amber-800">₹{{ number_format($item->remaining(), 2) }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('credits.index', ['type' => $item->type]) }}" class="block text-center text-[11px] font-semibold text-slate-700 hover:underline">Manage on {{ $item->type === 'credit' ? 'Credits' : 'Debts' }} →</a>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-2">
                        @if($b['net'] != 0)
                            <button
                                type="button"
                                onclick="window.openSettleModal({{ $f->id }}, '{{ $f->name }}', {{ abs($b['net']) }}, '{{ $b['net'] < 0 ? 'i_paid_friend' : 'friend_paid_me' }}')"
                                class="flex-1 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs shadow-sm transition"
                            >
                                Mark as Settled
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ADD USER MODAL -->
    <div id="add-friend-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" onclick="if(event.target === this) this.classList.add('hidden')">
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-lg text-slate-900 dark:text-white">Add User</h3>
                <button type="button" onclick="document.getElementById('add-friend-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none font-bold" aria-label="Close">&times;</button>
            </div>

            <form action="{{ route('friends.store') }}" method="POST" class="space-y-4 text-sm">
                @csrf

                <div>
                    <label for="add-user-name" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Full Name *</label>
                    <input
                        type="text"
                        id="add-user-name"
                        name="name"
                        required
                        value="{{ old('name') }}"
                        placeholder="Enter full name"
                        class="w-full h-11 px-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400"
                    >
                    @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="add-user-phone" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Phone Number *</label>
                    <input
                        type="text"
                        id="add-user-phone"
                        name="phone"
                        required
                        value="{{ old('phone') }}"
                        placeholder="Enter phone number"
                        class="w-full h-11 px-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400"
                    >
                    @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="add-user-email" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Email ID *</label>
                    <input
                        type="email"
                        id="add-user-email"
                        name="email"
                        required
                        value="{{ old('email') }}"
                        placeholder="Enter email address"
                        class="w-full h-11 px-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400"
                    >
                    @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="add-user-type" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1.5">User Type *</label>
                    <select
                        id="add-user-type"
                        name="user_type"
                        required
                        class="w-full h-11 px-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400"
                    >
                        <option value="Friend" @selected(old('user_type', 'Friend') === 'Friend')>Friend</option>
                        <option value="Not a Friend" @selected(old('user_type') === 'Not a Friend')>Not a Friend</option>
                    </select>
                    @error('user_type')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="add-user-dob" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Date of Birth</label>
                    <input
                        type="date"
                        id="add-user-dob"
                        name="date_of_birth"
                        value="{{ old('date_of_birth') }}"
                        class="w-full h-11 px-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400"
                    >
                    @error('date_of_birth')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="add-user-notes" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Details</label>
                    <textarea
                        id="add-user-notes"
                        name="notes"
                        rows="3"
                        placeholder="Optional notes"
                        class="w-full min-h-[5.5rem] px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 resize-y"
                    >{{ old('notes') }}</textarea>
                    @error('notes')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="w-full h-11 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold shadow-md shadow-indigo-600/20 transition">
                    Add User
                </button>
            </form>
        </div>
    </div>

    <!-- RECORD SPLIT MODAL -->
    <div id="add-split-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-base text-slate-900 dark:text-white">Record Shared Expense / Debt</h3>
                <button onclick="document.getElementById('add-split-modal').classList.add('hidden')" class="text-slate-400 text-2xl font-bold">&times;</button>
            </div>
            <form action="{{ route('friends.transactions.store') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <input type="hidden" name="date" value="{{ date('Y-m-d') }}">

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Friend *</label>
                    <select name="friend_id" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                        @foreach($friendData as $fd)
                            <option value="{{ $fd['friend']->id }}">{{ $fd['friend']->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Split Type *</label>
                    <select name="type" id="split-type" onchange="window.adjustShares()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                        <option value="shared_expense">Shared Expense (Split Equally)</option>
                        <option value="paid_for_friend">I Paid for Friend (Friend owes me all)</option>
                        <option value="friend_paid_for_me">Friend Paid for Me (I owe friend all)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Total Amount (₹) *</label>
                    <input type="number" step="0.01" name="total_amount" id="split-total" oninput="window.adjustShares()" required placeholder="0.00" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-bold text-base text-slate-900 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-500 mb-1">My Share (₹)</label>
                        <input type="number" step="0.01" name="my_share" id="split-my-share" required class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                    </div>
                    <div>
                        <label class="block text-slate-500 mb-1">Friend Share (₹)</label>
                        <input type="number" step="0.01" name="friend_share" id="split-friend-share" required class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Description *</label>
                    <input type="text" name="description" required placeholder="e.g. Dinner at cafe, Auto fare" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                        <option value="UPI">UPI</option>
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                    </select>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 text-white font-semibold shadow-md transition">Save Split</button>
            </form>
        </div>
    </div>

    <!-- SETTLE MODAL -->
    <div id="settle-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-3xl p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-base text-slate-900 dark:text-white">Settle Balance</h3>
                <button onclick="document.getElementById('settle-modal').classList.add('hidden')" class="text-slate-400 text-2xl font-bold">&times;</button>
            </div>
            <form id="settle-form" action="" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block text-slate-500 mb-1">Friend</label>
                    <input type="text" id="settle-name" readonly class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-800 rounded-xl font-bold">
                </div>
                <div>
                    <label class="block text-slate-500 mb-1">Settlement Amount (₹) *</label>
                    <input type="number" step="0.01" name="amount" id="settle-amount" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-bold text-emerald-600 text-base">
                </div>
                <input type="hidden" name="direction" id="settle-direction">
                <div>
                    <label class="block text-slate-500 mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
                        <option value="UPI">UPI</option>
                        <option value="Cash">Cash</option>
                        <option value="Bank">Bank Transfer</option>
                    </select>
                </div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 text-white font-semibold shadow-md transition">Confirm Settlement</button>
            </form>
        </div>
    </div>

    <script>
        window.adjustShares = function() {
            const type = document.getElementById('split-type').value;
            const total = parseFloat(document.getElementById('split-total').value) || 0;
            const myShare = document.getElementById('split-my-share');
            const friendShare = document.getElementById('split-friend-share');

            if (type === 'shared_expense') {
                myShare.value = (total / 2).toFixed(2);
                friendShare.value = (total / 2).toFixed(2);
            } else if (type === 'paid_for_friend') {
                myShare.value = '0.00';
                friendShare.value = total.toFixed(2);
            } else if (type === 'friend_paid_for_me') {
                myShare.value = total.toFixed(2);
                friendShare.value = '0.00';
            }
        };

        window.openSettleModal = function(friendId, friendName, amount, direction) {
            document.getElementById('settle-form').action = `/finance/friends/${friendId}/settle`;
            document.getElementById('settle-name').value = friendName;
            document.getElementById('settle-amount').value = amount;
            document.getElementById('settle-direction').value = direction;
            document.getElementById('settle-modal').classList.remove('hidden');
        };

        @if($errors->any() && (old('user_type') !== null || old('name') || $errors->has('name') || $errors->has('phone') || $errors->has('email') || $errors->has('user_type')))
            document.getElementById('add-friend-modal')?.classList.remove('hidden');
        @endif
    </script>
</x-app-layout>

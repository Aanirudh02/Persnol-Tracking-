<x-app-layout title="Add Expense">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('expenses.index') }}" class="text-sm font-semibold text-slate-600 hover:underline">&larr; Back to Expenses</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Record New Expense</h1>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5 text-sm" id="expense-form">
                @csrf

                @if ($errors->any())
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 space-y-1">
                        <p class="font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-rose-600 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Please resolve the following errors:
                        </p>
                        <ul class="list-disc pl-5 space-y-0.5 text-xs text-rose-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Amount</label>
                        <input id="expense-total" type="number" step="0.01" name="amount" required value="{{ old('amount') }}" placeholder="0.00" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-lg font-bold">
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">GST</label>
                        <input id="expense-gst" type="number" step="0.01" min="0" name="gst_amount" value="{{ old('gst_amount', 0) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5">
                    </div>
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Description</label>
                    <input type="text" name="description" required value="{{ old('description') }}" placeholder="e.g. Surya Bakery, Lunch, Petrol..." class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5">
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Category</label>
                        <select name="category_id" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}{{ $category->is_voluntary ? ' (Voluntary)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Payment Method</label>
                        <select name="payment_method" id="main-payment-method" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Date</label>
                        <input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Time</label>
                        <input type="time" name="time" value="{{ old('time', date('H:i')) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                    </div>
                </div>

                <!-- FRIEND SPLIT SECTION -->
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-4 sm:p-5 space-y-4 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                <span class="text-base">👥</span> Friend Split (Single or Multiple Friends)
                            </h2>
                            <p class="text-xs text-slate-500">Track each friend's share and who paid, counted only once in expenses.</p>
                        </div>
                        <button type="button" id="add-friend-btn" class="self-start sm:self-auto inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition active:scale-95">
                            <span>+</span> Add Friend
                        </button>
                    </div>

                    <!-- Friends list container -->
                    <div id="friends-container" class="space-y-3">
                        <!-- Populated by JS -->
                    </div>

                    <!-- My Share & Paid section -->
                    <div id="my-share-card" class="rounded-xl border border-indigo-200/80 bg-white p-3.5 space-y-3">
                        <div class="text-xs font-bold uppercase tracking-wider text-indigo-900">Your Share & Payment (Me)</div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Your share (What you should pay)</label>
                                <input type="number" step="0.01" min="0" name="split_my_share" id="split_my_share" value="{{ old('split_my_share') }}" placeholder="0.00" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Paid by you (What you actually paid)</label>
                                <input type="number" step="0.01" min="0" name="split_paid_by_me_amount" id="split_paid_by_me_amount" value="{{ old('split_paid_by_me_amount') }}" placeholder="0.00" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800">
                            </div>
                        </div>
                    </div>

                    <!-- Quick buttons -->
                    <div class="flex flex-wrap gap-2 text-xs">
                        <button type="button" onclick="window.quickSplitEqual()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-50 shadow-xs">Equal split</button>
                        <button type="button" onclick="window.quickSplitMineOnly()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-50 shadow-xs">All mine</button>
                        <button type="button" onclick="window.quickSplitFriendsOnly()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-50 shadow-xs">All friends</button>
                        <button type="button" onclick="window.quickPaidIPaidAll()" class="rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-1.5 font-semibold text-indigo-800 hover:bg-indigo-100 shadow-xs">I paid all</button>
                        <button type="button" onclick="window.quickPaidFriendsPaidAll()" class="rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-1.5 font-semibold text-indigo-800 hover:bg-indigo-100 shadow-xs">Friends paid all</button>
                        <button type="button" onclick="window.quickPaidEachOwnShare()" class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1.5 font-semibold text-emerald-800 hover:bg-emerald-100 shadow-xs">Each paid own share</button>
                    </div>

                    <!-- Split Live Balance Summary Box -->
                    <div id="split-summary-box" class="rounded-xl border border-indigo-100 bg-white/90 p-3 text-xs space-y-1.5">
                        <div class="flex items-center justify-between font-bold text-slate-800">
                            <span>Total Bill: ₹<span id="sum-total-bill">0.00</span></span>
                            <span id="split-status-badge" class="px-2 py-0.5 rounded-md font-semibold text-[11px] bg-slate-100 text-slate-600">No split</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-slate-600 pt-1 border-t border-slate-100">
                            <div>Shares total: <span id="sum-shares" class="font-bold text-slate-900">₹0.00</span></div>
                            <div>Paid total: <span id="sum-paid" class="font-bold text-slate-900">₹0.00</span></div>
                        </div>
                        <div id="split-payer-hint" class="text-[11px] text-indigo-700 pt-1"></div>
                    </div>
                </div>

                <!-- PAYMENT BREAKDOWN / MULTI-METHOD GROUPED EXPENSE -->
                <div class="rounded-2xl border border-sky-200 bg-sky-50/50 p-4 space-y-3">
                    <label class="flex items-center gap-2 font-semibold text-slate-800 cursor-pointer">
                        <input id="add-group-expense" type="checkbox" name="add_group_expense" value="1" @checked(old('add_group_expense')) class="rounded border-slate-300 text-sky-600">
                        <span>Add payment breakdown (Separate payment methods for what you paid)</span>
                    </label>

                    <div id="group-expense-lines" class="hidden space-y-3 pt-1">
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-slate-600">Specify how you paid (e.g. ₹5 UPI + ₹15 Cash):</p>
                            <button type="button" id="add-group-line" class="text-xs font-semibold text-sky-700 hover:underline">+ Add payment line</button>
                        </div>
                        <div id="group-line-list" class="space-y-2"></div>
                        <p id="group-total-hint" class="text-xs font-semibold text-slate-600"></p>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Under parent expense (Optional)</label>
                    <select name="parent_id" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                        <option value="">None</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->description }} · ₹{{ $parent->totalAmount() }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="flex items-center gap-2 font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_voluntary" value="1" class="rounded border-slate-300 text-slate-900">
                    Voluntary spend
                </label>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Receipt Image</label>
                    <input type="file" name="receipt_image" accept="image/*" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Additional details..." class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" id="save-expense-btn" class="w-full rounded-xl bg-slate-900 py-3 font-semibold text-white shadow-md hover:bg-slate-800 transition active:scale-[0.99] cursor-pointer">
                    Save Expense
                </button>
            </form>
        </div>
    </div>

    <script>
        const friendsList = @json($friends);
        const paymentMethods = @json(collect($paymentMethods)->values());
        const totalInput = document.getElementById('expense-total');
        const gstInput = document.getElementById('expense-gst');
        const friendsContainer = document.getElementById('friends-container');
        const myShareInput = document.getElementById('split_my_share');
        const myPaidInput = document.getElementById('split_paid_by_me_amount');
        const sumTotalBill = document.getElementById('sum-total-bill');
        const sumShares = document.getElementById('sum-shares');
        const sumPaid = document.getElementById('sum-paid');
        const splitStatusBadge = document.getElementById('split-status-badge');
        const splitPayerHint = document.getElementById('split-payer-hint');

        let friendRowCount = 0;

        function getFullBillTotal() {
            return Number(totalInput.value || 0) + Number(gstInput.value || 0);
        }

        function addFriendRow(friendId = '', share = '', paid = '') {
            const index = friendRowCount++;
            const friendOptions = ['<option value="">Select a friend</option>']
                .concat(friendsList.map(f => `<option value="${f.id}">${f.name} (${f.role})</option>`))
                .join('');

            const card = document.createElement('div');
            card.className = 'friend-row rounded-xl border border-indigo-200 bg-white p-3 space-y-2.5 shadow-2xs';
            card.dataset.index = index;
            card.innerHTML = `
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-indigo-900">Friend #${index + 1}</span>
                    <button type="button" class="remove-friend-btn text-xs text-rose-600 hover:text-rose-800 font-semibold">&times; Remove</button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-0.5">Friend</label>
                        <select name="splits[${index}][friend_id]" class="friend-id-select w-full rounded-xl border border-slate-300 bg-slate-50 px-2.5 py-1.5 text-xs font-medium">
                            ${friendOptions}
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-0.5">Friend Share (₹)</label>
                        <input type="number" step="0.01" min="0" name="splits[${index}][friend_share]" value="${share}" placeholder="0.00" class="friend-share-input w-full rounded-xl border border-slate-300 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-0.5">Paid by friend (₹)</label>
                        <input type="number" step="0.01" min="0" name="splits[${index}][paid_by_friend_amount]" value="${paid}" placeholder="0.00" class="friend-paid-input w-full rounded-xl border border-slate-300 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold">
                    </div>
                </div>
            `;

            if (friendId) {
                card.querySelector('.friend-id-select').value = friendId;
            }

            card.querySelector('.remove-friend-btn').addEventListener('click', () => {
                card.remove();
                recalculateSplits();
                updateGroupHint();
            });

            card.querySelectorAll('input, select').forEach(el => {
                el.addEventListener('input', () => {
                    recalculateSplits();
                    updateGroupHint();
                });
            });

            friendsContainer.appendChild(card);
            recalculateSplits();
            updateGroupHint();
        }

        document.getElementById('add-friend-btn').addEventListener('click', () => {
            addFriendRow();
        });

        function getActiveFriendRows() {
            return Array.from(document.querySelectorAll('.friend-row')).filter(row => {
                const fid = row.querySelector('.friend-id-select').value;
                return Boolean(fid);
            });
        }

        function recalculateSplits() {
            const bill = getFullBillTotal();
            sumTotalBill.textContent = bill.toFixed(2);

            const rows = getActiveFriendRows();
            if (rows.length === 0) {
                splitStatusBadge.textContent = 'No split';
                splitStatusBadge.className = 'px-2 py-0.5 rounded-md font-semibold text-[11px] bg-slate-100 text-slate-600';
                sumShares.textContent = `₹${(Number(myShareInput.value) || 0).toFixed(2)}`;
                sumPaid.textContent = `₹${(Number(myPaidInput.value) || 0).toFixed(2)}`;
                splitPayerHint.textContent = '';
                return;
            }

            let friendsShareSum = 0;
            let friendsPaidSum = 0;
            const friendDetails = [];

            rows.forEach(r => {
                const sel = r.querySelector('.friend-id-select');
                const name = sel.options[sel.selectedIndex]?.text?.split('(')[0]?.trim() || 'Friend';
                const share = Number(r.querySelector('.friend-share-input').value) || 0;
                const paid = Number(r.querySelector('.friend-paid-input').value) || 0;
                friendsShareSum += share;
                friendsPaidSum += paid;
                friendDetails.push({ name, share, paid, net: paid - share });
            });

            const myShare = Number(myShareInput.value) || 0;
            const myPaid = Number(myPaidInput.value) || 0;
            const totalShares = myShare + friendsShareSum;
            const totalPaid = myPaid + friendsPaidSum;

            sumShares.textContent = `₹${totalShares.toFixed(2)} of ₹${bill.toFixed(2)}`;
            sumPaid.textContent = `₹${totalPaid.toFixed(2)} of ₹${bill.toFixed(2)}`;

            const sharesOk = Math.abs(totalShares - bill) < 0.05;
            const paidOk = Math.abs(totalPaid - bill) < 0.05;

            if (sharesOk && paidOk) {
                splitStatusBadge.textContent = '✓ Split Balanced';
                splitStatusBadge.className = 'px-2 py-0.5 rounded-md font-semibold text-[11px] bg-emerald-100 text-emerald-800';
            } else {
                splitStatusBadge.textContent = '⚠ Split Mismatch';
                splitStatusBadge.className = 'px-2 py-0.5 rounded-md font-semibold text-[11px] bg-amber-100 text-amber-800';
            }

            const hints = [];
            hints.push(`You paid ₹${myPaid.toFixed(2)} (share ₹${myShare.toFixed(2)})`);
            friendDetails.forEach(f => {
                hints.push(`${f.name} paid ₹${f.paid.toFixed(2)} (share ₹${f.share.toFixed(2)})`);
            });
            splitPayerHint.textContent = hints.join(' · ');
        }

        // Quick Split Calculation Buttons
        window.quickSplitEqual = function() {
            const bill = getFullBillTotal();
            const rows = getActiveFriendRows();
            const count = rows.length + 1; // Me + friends
            if (count <= 1) {
                myShareInput.value = bill.toFixed(2);
                recalculateSplits();
                return;
            }
            const each = Math.floor((bill / count) * 100) / 100;
            const remainder = Number((bill - (each * (count - 1))).toFixed(2));
            myShareInput.value = remainder.toFixed(2);
            rows.forEach(r => {
                r.querySelector('.friend-share-input').value = each.toFixed(2);
            });
            recalculateSplits();
            updateGroupHint();
        };

        window.quickSplitMineOnly = function() {
            const bill = getFullBillTotal();
            const rows = getActiveFriendRows();
            myShareInput.value = bill.toFixed(2);
            rows.forEach(r => {
                r.querySelector('.friend-share-input').value = '0.00';
            });
            recalculateSplits();
            updateGroupHint();
        };

        window.quickSplitFriendsOnly = function() {
            const bill = getFullBillTotal();
            const rows = getActiveFriendRows();
            myShareInput.value = '0.00';
            if (rows.length === 0) return;
            const each = (bill / rows.length).toFixed(2);
            rows.forEach(r => {
                r.querySelector('.friend-share-input').value = each;
            });
            recalculateSplits();
            updateGroupHint();
        };

        window.quickPaidIPaidAll = function() {
            const bill = getFullBillTotal();
            const rows = getActiveFriendRows();
            myPaidInput.value = bill.toFixed(2);
            rows.forEach(r => {
                r.querySelector('.friend-paid-input').value = '0.00';
            });
            recalculateSplits();
            updateGroupHint();
        };

        window.quickPaidFriendsPaidAll = function() {
            const bill = getFullBillTotal();
            const rows = getActiveFriendRows();
            myPaidInput.value = '0.00';
            if (rows.length === 0) return;
            const each = (bill / rows.length).toFixed(2);
            rows.forEach(r => {
                r.querySelector('.friend-paid-input').value = each;
            });
            recalculateSplits();
            updateGroupHint();
        };

        window.quickPaidEachOwnShare = function() {
            const rows = getActiveFriendRows();
            myPaidInput.value = (Number(myShareInput.value) || 0).toFixed(2);
            rows.forEach(r => {
                const s = Number(r.querySelector('.friend-share-input').value) || 0;
                r.querySelector('.friend-paid-input').value = s.toFixed(2);
            });
            recalculateSplits();
            updateGroupHint();
        };

        totalInput.addEventListener('input', () => {
            recalculateSplits();
            updateGroupHint();
        });
        gstInput.addEventListener('input', () => {
            recalculateSplits();
            updateGroupHint();
        });
        myShareInput.addEventListener('input', () => {
            recalculateSplits();
            updateGroupHint();
        });
        myPaidInput.addEventListener('input', () => {
            recalculateSplits();
            updateGroupHint();
        });

        // PAYMENT BREAKDOWN (Group lines)
        const groupToggle = document.getElementById('add-group-expense');
        const groupLines = document.getElementById('group-expense-lines');
        const groupList = document.getElementById('group-line-list');
        const groupHint = document.getElementById('group-total-hint');
        let groupLineCount = 0;

        function addGroupLine(amount = '', method = null) {
            const index = groupLineCount++;
            method = method || paymentMethods[index] || paymentMethods[0] || 'Cash';
            const options = paymentMethods.map(item => `<option value="${item}">${item}</option>`).join('');
            const row = document.createElement('div');
            row.className = 'grid grid-cols-[1fr_1fr_auto] gap-2';
            row.innerHTML = `
                <input type="number" step="0.01" min="0.01" name="group_expenses[${index}][amount]" value="${amount}" placeholder="Amount" class="group-amount w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                <select name="group_expenses[${index}][payment_method]" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">${options}</select>
                <button type="button" class="px-2 text-rose-600 font-bold" aria-label="Remove payment">&times;</button>
            `;
            row.querySelector('select').value = method;
            row.querySelector('button').addEventListener('click', () => {
                if (groupList.children.length <= 2) return;
                row.remove();
                updateGroupHint();
            });
            groupList.appendChild(row);
            row.querySelector('input').addEventListener('input', updateGroupHint);
            updateGroupHint();
        }

        function updateGroupHint() {
            const total = [...document.querySelectorAll('.group-amount')].reduce((sum, input) => sum + (Number(input.value) || 0), 0);
            const activeFriends = getActiveFriendRows();
            const isFriendSplit = activeFriends.length > 0;
            const myPaid = Number(myPaidInput.value) || 0;
            // Target for breakdown: if friend split is active, breakdown what I paid! Otherwise the full bill.
            const expected = isFriendSplit ? (myPaid > 0 ? myPaid : getFullBillTotal()) : (Number(totalInput.value) || 0);
            const label = isFriendSplit ? 'your paid amount' : 'total amount';

            groupHint.textContent = `Breakdown: ₹${total.toFixed(2)} of ₹${expected.toFixed(2)} (${label})`;
            const ok = Math.abs(total - expected) < 0.05;
            groupHint.className = `text-xs font-semibold ${ok ? 'text-emerald-700' : 'text-amber-700'}`;
        }

        groupToggle.addEventListener('change', () => {
            groupLines.classList.toggle('hidden', !groupToggle.checked);
            if (groupToggle.checked && groupList.children.length === 0) {
                addGroupLine();
                addGroupLine();
            }
        });
        document.getElementById('add-group-line').addEventListener('click', () => addGroupLine());

        // Initialize with 1 friend row if old inputs or by default
        @if(old('split_with_friend_id'))
            addFriendRow('{{ old('split_with_friend_id') }}', '{{ old('split_friend_share') }}', '{{ old('split_paid_by_friend_amount') }}');
        @elseif(old('splits'))
            @foreach(old('splits') as $s)
                addFriendRow('{{ $s['friend_id'] ?? '' }}', '{{ $s['friend_share'] ?? '' }}', '{{ $s['paid_by_friend_amount'] ?? '' }}');
            @endforeach
        @endif

        recalculateSplits();
    </script>
</x-app-layout>

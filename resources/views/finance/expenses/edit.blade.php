<x-app-layout title="Edit Expense">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('expenses.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">&larr; Back to Expenses</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Edit Expense #{{ $expense->id }}</h1>
            <p class="text-xs text-slate-500">Changes will be recorded in the audit trail.</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @php
                $splits = $expense->friendSplits->isNotEmpty() ? $expense->friendSplits : ($expense->friendSplit ? collect([$expense->friendSplit]) : collect());
            @endphp
            <form action="{{ route('expenses.update', $expense) }}" method="POST" enctype="multipart/form-data" class="space-y-5 text-sm" id="edit-expense-form">
                @csrf
                @method('PUT')

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
                        <input id="expense-total" type="number" step="0.01" name="amount" required value="{{ old('amount', $expense->amount) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-lg font-bold">
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">GST</label>
                        <input id="expense-gst" type="number" step="0.01" min="0" name="gst_amount" value="{{ old('gst_amount', $expense->gst_amount) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5">
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
                        <select name="payment_method" id="main-payment-method" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                            @php
                                $hasSplitOption = in_array('Split', (array) $paymentMethods);
                            @endphp
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method }}" @selected(old('payment_method', $expense->payment_method) === $method)>{{ $method }}</option>
                            @endforeach
                            @if(! $hasSplitOption)
                                <option value="Split" @selected(old('payment_method', $expense->payment_method) === 'Split')>Split</option>
                            @endif
                        </select>
                    </div>
                </div>

                <!-- OPTION AT TOP: Record as Combination Payment -->
                <div class="rounded-2xl border border-purple-200 bg-gradient-to-r from-purple-50/80 via-indigo-50/50 to-white p-3.5 sm:p-4 transition shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <label class="flex items-start gap-3 cursor-pointer select-none">
                            <input type="checkbox" id="record-as-combination" name="record_as_combination" value="1" @checked(old('record_as_combination')) class="mt-0.5 h-4.5 w-4.5 rounded border-purple-300 text-purple-600 focus:ring-purple-500">
                            <div>
                                <span class="text-sm font-bold text-purple-950 flex items-center gap-1.5">
                                    <span>🤝</span> Record as Combination Payment
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-200 text-purple-800">Who Paid Alone</span>
                                </span>
                                <p class="text-xs text-purple-700 mt-0.5 leading-relaxed">
                                    Paid together with friend(s)? Just enter what you paid and what friend paid. No share calculations needed — recording who paid alone is enough!
                                </p>
                            </div>
                        </label>
                        <button type="button" id="jump-to-splits-btn" class="self-start sm:self-auto inline-flex items-center gap-1 text-xs font-bold text-purple-700 hover:text-purple-900 bg-white hover:bg-purple-100/60 border border-purple-200 px-3 py-1.5 rounded-xl shadow-2xs transition">
                            <span>Set Payers</span> &darr;
                        </button>
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

                <!-- FRIEND SPLIT SECTION -->
                <div id="friend-split-section" class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-4 sm:p-5 space-y-4 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                <span class="text-base">👥</span> Friend Split / Payers (Single or Multiple Friends)
                            </h2>
                            <p class="text-xs text-slate-500">Track each friend's share and who paid, or record combination payments where paid alone is enough.</p>
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
                                <label class="mb-1 block text-xs font-semibold text-slate-700">
                                    Your share (What you should pay)
                                    <span class="share-opt-badge text-[10px] text-purple-600 font-normal hidden">(Optional in Combination)</span>
                                </label>
                                <input type="number" step="0.01" min="0" name="split_my_share" id="split_my_share" value="{{ old('split_my_share', $expense->split_my_share ?? ($splits->first()?->my_share)) }}" placeholder="0.00" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Paid by you (What you actually paid)</label>
                                <input type="number" step="0.01" min="0" name="split_paid_by_me_amount" id="split_paid_by_me_amount" value="{{ old('split_paid_by_me_amount', $splits->first()?->paid_by_me_amount ?? ($expense->paid_by_type === 'me' ? $expense->totalAmount() : 0)) }}" placeholder="0.00" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800">
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
                        <button type="button" onclick="window.quickCombinationPayerOnly()" class="rounded-lg border border-purple-300 bg-purple-50 px-3 py-1.5 font-semibold text-purple-800 hover:bg-purple-100 shadow-xs">🤝 Combination (Set shares = paid)</button>
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
                        <div class="pt-1 border-t border-slate-100 flex items-center justify-between font-semibold text-slate-700">
                            <span>Your registered expense:</span>
                            <span id="sum-my-registered" class="font-bold text-emerald-600">₹0.00</span>
                        </div>
                        <div id="split-payer-hint" class="text-[11px] text-indigo-700 pt-1"></div>
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

                <button type="submit" class="w-full rounded-xl bg-indigo-600 py-3 font-semibold text-white shadow-md hover:bg-indigo-700 transition active:scale-[0.99] cursor-pointer">
                    Update Expense
                </button>
            </form>
        </div>
    </div>

    <script>
        const friendsList = @json($friends);
        const totalInput = document.getElementById('expense-total');
        const gstInput = document.getElementById('expense-gst');
        const friendsContainer = document.getElementById('friends-container');
        const myShareInput = document.getElementById('split_my_share');
        const myPaidInput = document.getElementById('split_paid_by_me_amount');
        const sumTotalBill = document.getElementById('sum-total-bill');
        const sumShares = document.getElementById('sum-shares');
        const sumPaid = document.getElementById('sum-paid');
        const sumMyRegistered = document.getElementById('sum-my-registered');
        const splitStatusBadge = document.getElementById('split-status-badge');
        const splitPayerHint = document.getElementById('split-payer-hint');
        const recordAsComboInput = document.getElementById('record-as-combination');
        const jumpToSplitsBtn = document.getElementById('jump-to-splits-btn');
        const friendSplitSection = document.getElementById('friend-split-section');
        const mainMethodSelect = document.getElementById('main-payment-method');

        let userManuallyChangedPaymentMethod = false;
        if (mainMethodSelect) {
            mainMethodSelect.addEventListener('change', () => {
                userManuallyChangedPaymentMethod = true;
            });
        }

        let friendRowCount = 0;

        function getFullBillTotal() {
            return Number(totalInput.value || 0) + Number(gstInput.value || 0);
        }

        function isCombinationMode() {
            return Boolean(recordAsComboInput && recordAsComboInput.checked);
        }

        function ensureSplitPaymentMethodOption() {
            if (!mainMethodSelect) return;
            let splitOpt = Array.from(mainMethodSelect.options).find(o => o.value.toLowerCase() === 'split');
            if (!splitOpt) {
                splitOpt = document.createElement('option');
                splitOpt.value = 'Split';
                splitOpt.textContent = 'Split';
                mainMethodSelect.appendChild(splitOpt);
            }
            const activeFriends = getActiveFriendRows();
            const myPaid = Number(myPaidInput.value) || 0;
            const hasFriendPay = activeFriends.some(r => Number(r.querySelector('.friend-paid-input')?.value || 0) > 0);
            if ((activeFriends.length > 0 && (hasFriendPay || activeFriends.length > 1)) || isCombinationMode()) {
                if (!userManuallyChangedPaymentMethod) {
                    mainMethodSelect.value = 'Split';
                }
            }
        }

        function onCombinationToggle() {
            const active = isCombinationMode();
            document.querySelectorAll('.share-opt-badge').forEach(el => {
                el.classList.toggle('hidden', !active);
            });
            if (active) {
                if (getActiveFriendRows().length === 0) {
                    addFriendRow();
                }
            }
            ensureSplitPaymentMethodOption();
            recalculateSplits();
        }

        if (recordAsComboInput) {
            recordAsComboInput.addEventListener('change', onCombinationToggle);
        }

        if (jumpToSplitsBtn) {
            jumpToSplitsBtn.addEventListener('click', () => {
                if (recordAsComboInput && !recordAsComboInput.checked) {
                    recordAsComboInput.checked = true;
                    onCombinationToggle();
                }
                if (getActiveFriendRows().length === 0) {
                    addFriendRow();
                }
                friendSplitSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }

        function addFriendRow(friendId = '', share = '', paid = '') {
            const index = friendRowCount++;
            const friendOptions = ['<option value="">Select a friend</option>']
                .concat(friendsList.map(f => `<option value="${f.id}">${f.name} (${f.role})</option>`))
                .join('');

            const comboActive = isCombinationMode();
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
                        <label class="block text-[11px] font-semibold text-slate-600 mb-0.5">
                            Friend Share (₹)
                            <span class="share-opt-badge text-[10px] text-purple-600 font-normal ${comboActive ? '' : 'hidden'}">(Optional)</span>
                        </label>
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
                ensureSplitPaymentMethodOption();
                recalculateSplits();
            });

            card.querySelectorAll('input, select').forEach(el => {
                el.addEventListener('input', () => {
                    // Do NOT auto-prefill friend share when friend paid is entered (Error 1 & Error 4)
                    ensureSplitPaymentMethodOption();
                    recalculateSplits();
                });
            });

            friendsContainer.appendChild(card);
            ensureSplitPaymentMethodOption();
            recalculateSplits();
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
                if (sumMyRegistered) {
                    sumMyRegistered.textContent = `₹${bill.toFixed(2)}`;
                }
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

            // Registered expense for user alone
            const hasExplicitShares = (myShare > 0 || friendsShareSum > 0);
            let myRegistered = bill;
            if (rows.length > 0 && friendsPaidSum > 0) {
                myRegistered = (hasExplicitShares && myShare > 0) ? myShare : myPaid;
            } else if (rows.length > 0 && myPaid > 0) {
                myRegistered = myPaid;
            }
            if (sumMyRegistered) {
                sumMyRegistered.textContent = `₹${myRegistered.toFixed(2)}`;
            }

            sumShares.textContent = `₹${totalShares.toFixed(2)} of ₹${bill.toFixed(2)}`;
            sumPaid.textContent = `₹${totalPaid.toFixed(2)} of ₹${bill.toFixed(2)}`;

            const comboMode = isCombinationMode();
            const sharesOk = Math.abs(totalShares - bill) < 0.05;
            const paidOk = Math.abs(totalPaid - bill) < 0.05;
            const sharesLeftZero = (totalShares <= 0.05 && paidOk);

            if (paidOk && (sharesLeftZero || comboMode)) {
                splitStatusBadge.textContent = '✓ Combination Paid (₹' + totalPaid.toFixed(2) + ')';
                splitStatusBadge.className = 'px-2 py-0.5 rounded-md font-semibold text-[11px] bg-purple-100 text-purple-800';
            } else if (sharesOk && paidOk) {
                splitStatusBadge.textContent = '✓ Split Balanced';
                splitStatusBadge.className = 'px-2 py-0.5 rounded-md font-semibold text-[11px] bg-emerald-100 text-emerald-800';
            } else {
                splitStatusBadge.textContent = '⚠ Split Mismatch';
                splitStatusBadge.className = 'px-2 py-0.5 rounded-md font-semibold text-[11px] bg-amber-100 text-amber-800';
            }

            if (paidOk && (sharesLeftZero || comboMode)) {
                splitPayerHint.innerHTML = `<span class="font-bold text-purple-900">🤝 Combination Payment:</span> You paid ₹${myPaid.toFixed(2)}, friend(s) paid ₹${friendsPaidSum.toFixed(2)}. Recorded who paid alone (no debt created).`;
            } else {
                const hints = [];
                hints.push(`You paid ₹${myPaid.toFixed(2)} (share ₹${myShare.toFixed(2)})`);
                friendDetails.forEach(f => {
                    hints.push(`${f.name} paid ₹${f.paid.toFixed(2)} (share ₹${f.share.toFixed(2)})`);
                });
                splitPayerHint.textContent = hints.join(' · ');
            }
        }

        // Quick Split Calculation Buttons
        window.quickCombinationPayerOnly = function() {
            if (recordAsComboInput) {
                recordAsComboInput.checked = true;
            }
            const rows = getActiveFriendRows();
            myShareInput.value = myPaidInput.value;
            rows.forEach(r => {
                r.querySelector('.friend-share-input').value = r.querySelector('.friend-paid-input').value;
            });
            onCombinationToggle();
            recalculateSplits();
        };

        window.quickSplitEqual = function() {
            const bill = getFullBillTotal();
            const rows = getActiveFriendRows();
            const count = rows.length + 1;
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
        };

        window.quickSplitMineOnly = function() {
            const bill = getFullBillTotal();
            const rows = getActiveFriendRows();
            myShareInput.value = bill.toFixed(2);
            rows.forEach(r => {
                r.querySelector('.friend-share-input').value = '0.00';
            });
            recalculateSplits();
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
        };

        window.quickPaidIPaidAll = function() {
            const bill = getFullBillTotal();
            const rows = getActiveFriendRows();
            myPaidInput.value = bill.toFixed(2);
            rows.forEach(r => {
                r.querySelector('.friend-paid-input').value = '0.00';
            });
            recalculateSplits();
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
        };

        window.quickPaidEachOwnShare = function() {
            const rows = getActiveFriendRows();
            myPaidInput.value = (Number(myShareInput.value) || 0).toFixed(2);
            rows.forEach(r => {
                const s = Number(r.querySelector('.friend-share-input').value) || 0;
                r.querySelector('.friend-paid-input').value = s.toFixed(2);
            });
            recalculateSplits();
        };

        totalInput.addEventListener('input', recalculateSplits);
        gstInput.addEventListener('input', recalculateSplits);
        myShareInput.addEventListener('input', recalculateSplits);
        myPaidInput.addEventListener('input', () => {
            ensureSplitPaymentMethodOption();
            recalculateSplits();
        });

        // Preload existing splits or old() values
        @if(old('splits'))
            @foreach(old('splits') as $s)
                addFriendRow('{{ $s['friend_id'] ?? '' }}', '{{ $s['friend_share'] ?? '' }}', '{{ $s['paid_by_friend_amount'] ?? '' }}');
            @endforeach
        @elseif($splits->isNotEmpty())
            @foreach($splits as $s)
                addFriendRow('{{ $s->friend_id }}', '{{ $s->friend_share }}', '{{ $s->paid_by_friend_amount }}');
            @endforeach
        @elseif($expense->split_with_friend_id)
            addFriendRow('{{ $expense->split_with_friend_id }}', '{{ $expense->split_friend_share }}', '{{ $expense->paid_by_type === "friend" ? $expense->totalAmount() : 0 }}');
        @endif

        recalculateSplits();
    </script>
</x-app-layout>

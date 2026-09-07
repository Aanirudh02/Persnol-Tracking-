<x-app-layout title="Add Expense">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('expenses.index') }}" class="text-sm font-semibold text-slate-600 hover:underline">&larr; Back to Expenses</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Record New Expense</h1>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5 text-sm">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Amount</label>
                        <input id="expense-total" type="number" step="0.01" name="amount" required value="{{ old('amount') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-lg font-bold">
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">GST</label>
                        <input type="number" step="0.01" min="0" name="gst_amount" value="{{ old('gst_amount', 0) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5">
                    </div>
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Description</label>
                    <input type="text" name="description" required value="{{ old('description') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5">
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Category</label>
                        <select name="category_id" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}{{ $category->is_voluntary ? ' (Voluntary)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Payment Method</label>
                        <select name="payment_method" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method }}">{{ $method }}</option>
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

                <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4 space-y-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Friend Split (Optional)</h2>
                        <p class="text-xs text-slate-500">Tracks shares and actual payer amounts while keeping the expense itself counted only once.</p>
                    </div>

                    <div>
                        <label class="mb-1 block font-semibold text-slate-700">Split with friend</label>
                        <select name="split_with_friend_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">
                            <option value="">None</option>
                            @foreach($friends as $friend)
                                <option value="{{ $friend->id }}">{{ $friend->name }} ({{ $friend->role }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Your share</label>
                            <input type="number" step="0.01" name="split_my_share" id="split_my_share" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Friend share</label>
                            <input type="number" step="0.01" name="split_friend_share" id="split_friend_share" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Who paid?</label>
                            <select name="split_paid_by_type" id="split_paid_by_type" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">
                                <option value="me">I paid</option>
                                <option value="friend">Friend paid</option>
                                <option value="split">Split payment</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Paid by me</label>
                            <input type="number" step="0.01" name="split_paid_by_me_amount" id="split_paid_by_me_amount" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold text-slate-700">Paid by friend</label>
                            <input type="number" step="0.01" name="split_paid_by_friend_amount" id="split_paid_by_friend_amount" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 text-xs">
                        <button type="button" onclick="window.expenseEqualSplit()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 font-semibold text-slate-700">Equal split</button>
                        <button type="button" onclick="window.expenseMineOnly()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 font-semibold text-slate-700">All mine</button>
                        <button type="button" onclick="window.expenseFriendOnly()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 font-semibold text-slate-700">All friend</button>
                    </div>
                </div>

                <label class="flex items-center gap-2 font-semibold text-slate-700">
                    <input id="add-group-expense" type="checkbox" name="add_group_expense" value="1" @checked(old('add_group_expense')) class="rounded border-slate-300">
                    Add as grouped expense with separate payment methods
                </label>

                <div id="group-expense-lines" class="hidden space-y-2 rounded-xl border border-sky-100 bg-sky-50/50 p-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-slate-700">Payment breakdown</p>
                        <button type="button" id="add-group-line" class="text-xs font-semibold text-sky-700 hover:underline">+ Add payment</button>
                    </div>
                    <div id="group-line-list" class="space-y-2"></div>
                    <p id="group-total-hint" class="text-xs text-slate-500"></p>
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Under parent expense</label>
                    <select name="parent_id" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                        <option value="">None</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->description }} · ₹{{ $parent->totalAmount() }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="flex items-center gap-2 font-semibold text-slate-700">
                    <input type="checkbox" name="is_voluntary" value="1" class="rounded border-slate-300">
                    Voluntary spend
                </label>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Receipt</label>
                    <input type="file" name="receipt_image" accept="image/*" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Notes</label>
                    <textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="w-full rounded-xl bg-slate-900 py-2.5 font-semibold text-white">Save Expense</button>
            </form>
        </div>
    </div>

    <script>
        const groupToggle = document.getElementById('add-group-expense');
        const groupLines = document.getElementById('group-expense-lines');
        const groupList = document.getElementById('group-line-list');
        const groupTotal = document.getElementById('expense-total');
        const groupHint = document.getElementById('group-total-hint');
        const paymentMethods = @json(collect($paymentMethods)->values());
        let groupLineCount = 0;

        function addGroupLine(amount = '', method = null) {
            const index = groupLineCount++;
            method = method || paymentMethods[index] || paymentMethods[0] || 'Cash';
            const options = paymentMethods.map((item) => `<option value="${item}">${item}</option>`).join('');
            const row = document.createElement('div');
            row.className = 'grid grid-cols-[1fr_1fr_auto] gap-2';
            row.innerHTML = `<input type="number" step="0.01" min="0.01" name="group_expenses[${index}][amount]" value="${amount}" placeholder="Amount" class="group-amount w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                <select name="group_expenses[${index}][payment_method]" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2">${options}</select>
                <button type="button" class="px-2 text-rose-600" aria-label="Remove payment">&times;</button>`;
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
            const expected = Number(groupTotal.value) || 0;
            groupHint.textContent = `Breakdown: ₹${total.toFixed(2)} of ₹${expected.toFixed(2)}`;
            groupHint.className = `text-xs ${Math.abs(total - expected) < 0.01 ? 'text-emerald-700' : 'text-amber-700'}`;
        }

        groupToggle.addEventListener('change', () => {
            groupLines.classList.toggle('hidden', !groupToggle.checked);
            if (groupToggle.checked && groupList.children.length === 0) {
                addGroupLine();
                addGroupLine();
            }
        });
        document.getElementById('add-group-line').addEventListener('click', () => addGroupLine());
        groupTotal.addEventListener('input', updateGroupHint);
        if (groupToggle.checked) groupToggle.dispatchEvent(new Event('change'));

        const expenseTotal = () => Number(document.getElementById('expense-total').value || 0) + Number(document.querySelector('input[name="gst_amount"]').value || 0);
        const updateSplitPaid = () => {
            const total = expenseTotal();
            const mode = document.getElementById('split_paid_by_type').value;
            const paidByMe = document.getElementById('split_paid_by_me_amount');
            const paidByFriend = document.getElementById('split_paid_by_friend_amount');
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
                if (!paidByMe.value) {
                    paidByMe.value = (total / 2).toFixed(2);
                }
                if (!paidByFriend.value) {
                    paidByFriend.value = (total - Number(paidByMe.value || 0)).toFixed(2);
                }
                paidByMe.readOnly = false;
                paidByFriend.readOnly = false;
            }
        };

        window.expenseEqualSplit = function () {
            const total = expenseTotal();
            const half = (total / 2).toFixed(2);
            document.getElementById('split_my_share').value = half;
            document.getElementById('split_friend_share').value = (total - Number(half)).toFixed(2);
        };
        window.expenseMineOnly = function () {
            const total = expenseTotal().toFixed(2);
            document.getElementById('split_my_share').value = total;
            document.getElementById('split_friend_share').value = '0.00';
        };
        window.expenseFriendOnly = function () {
            const total = expenseTotal().toFixed(2);
            document.getElementById('split_my_share').value = '0.00';
            document.getElementById('split_friend_share').value = total;
        };

        document.getElementById('split_paid_by_type').addEventListener('change', updateSplitPaid);
        document.getElementById('expense-total').addEventListener('input', updateSplitPaid);
        document.querySelector('input[name="gst_amount"]').addEventListener('input', updateSplitPaid);
        updateSplitPaid();
    </script>
</x-app-layout>

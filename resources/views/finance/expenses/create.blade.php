<x-app-layout title="Add Expense">
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <a href="{{ route('expenses.index') }}" class="text-sm font-semibold text-slate-600 hover:underline">&larr; Back to Expenses</a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 mt-2">Record New Expense</h1>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-sm">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Amount (₹) *</label>
                    <input id="expense-total" type="number" step="0.01" name="amount" required value="{{ old('amount') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-lg font-bold">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">GST (₹, optional)</label>
                    <input type="number" step="0.01" min="0" name="gst_amount" value="{{ old('gst_amount', 0) }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Description *</label>
                    <input type="text" name="description" required value="{{ old('description') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Category</label>
                        <select name="category_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                            <optgroup label="Active">
                                @foreach($categories->where('is_archived', false) as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}{{ $cat->is_voluntary ? ' (Voluntary)' : '' }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Archived">
                                @foreach($categories->where('is_archived', true) as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                            @foreach($paymentMethods as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
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
                    @error('group_expenses')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Date *</label>
                        <input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Time</label>
                        <input type="time" name="time" value="{{ old('time', date('H:i')) }}" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Paid by</label>
                        <select name="paid_by_type" id="paid_by_type" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl" onchange="document.getElementById('friend_paid_wrap').classList.toggle('hidden', this.value !== 'friend')">
                            <option value="me">Me</option>
                            <option value="friend">Friend / Parent</option>
                        </select>
                    </div>
                    <div id="friend_paid_wrap" class="hidden">
                        <label class="block font-semibold text-slate-700 mb-1">Who paid</label>
                        <select name="paid_by_friend_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                            @foreach($friends as $f)
                                <option value="{{ $f->id }}">{{ $f->name }} ({{ $f->role }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Split with friend (optional)</label>
                    <select name="split_with_friend_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                        <option value="">None</option>
                        @foreach($friends as $f)
                            <option value="{{ $f->id }}">{{ $f->name }} ({{ $f->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">My share</label>
                        <input type="number" step="0.01" name="split_my_share" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Friend share</label>
                        <input type="number" step="0.01" name="split_friend_share" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Under parent expense (sub-item)</label>
                    <select name="parent_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                        <option value="">None — top-level</option>
                        @foreach($parents as $p)
                            <option value="{{ $p->id }}">{{ $p->description }} · ₹{{ $p->amount }} ({{ $p->date->format('d M') }})</option>
                        @endforeach
                    </select>
                </div>
                <label class="flex items-center gap-2 font-semibold text-slate-700">
                    <input type="checkbox" name="is_voluntary" value="1" class="rounded border-slate-300">
                    Voluntary spend (separate from main totals)
                </label>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Receipt</label>
                    <input type="file" name="receipt_image" accept="image/*" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold">Save Expense</button>
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
            const preferredMethods = ['Cash', 'UPI'];
            method = method || preferredMethods[index] || paymentMethods[index] || paymentMethods[0] || 'Cash';
            if (!paymentMethods.includes(method)) method = paymentMethods[0] || 'Cash';
            const options = paymentMethods.map((item) => `<option value="${item}">${item}</option>`).join('');
            const row = document.createElement('div');
            row.className = 'grid grid-cols-[1fr_1fr_auto] gap-2';
            row.innerHTML = `<input type="number" step="0.01" min="0.01" name="group_expenses[${index}][amount]" value="${amount}" placeholder="Amount" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl group-amount">
                <select name="group_expenses[${index}][payment_method]" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl">${options}</select>
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
    </script>
</x-app-layout>

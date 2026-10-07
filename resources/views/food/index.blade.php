<x-app-layout title="Food & Snacks">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Food & Snacks</h1>
                <p class="text-xs text-slate-500">Log what you ate/drank. Link under an existing expense (e.g. Mario) — do not create a second parent spend.</p>
            </div>
            <button type="button" onclick="document.getElementById('food-log-form').classList.toggle('hidden')" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm self-start transition">
                + Add Food / Snack
            </button>
        </div>

        <form id="food-log-form" action="{{ route('food.store') }}" method="POST" class="{{ request()->boolean('is_snack') ? '' : 'hidden' }} bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4 text-sm">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Number of Items</label>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="changeItemCount(-1)" class="w-10 h-10 rounded-xl border border-slate-300 bg-slate-100 hover:bg-slate-200 font-bold text-slate-700 flex items-center justify-center cursor-pointer transition select-none text-base">&minus;</button>
                        <input id="item_count" name="item_count" type="number" min="1" max="50" step="1" value="1" placeholder="1" class="w-20 text-center px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-bold text-indigo-700 text-base">
                        <button type="button" onclick="changeItemCount(1)" class="w-10 h-10 rounded-xl border border-slate-300 bg-slate-100 hover:bg-slate-200 font-bold text-slate-700 flex items-center justify-center cursor-pointer transition select-none text-base">&plus;</button>
                        <button type="button" onclick="changeItemCount(1)" class="ml-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-100 cursor-pointer whitespace-nowrap">+ Add Row</button>
                    </div>
                </div>
                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2 font-medium text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" name="is_snack" value="1" @checked(request()->boolean('is_snack')) class="rounded border-slate-300 text-indigo-600">
                        <span>Mark as snack / tea</span>
                    </label>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Date (Auto-synced with Expense)</label>
                    <input type="date" id="food_date_input" name="date" value="{{ date('Y-m-d') }}" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-semibold">
                </div>
            </div>

            <!-- Dynamic Items Rows Container -->
            <div id="food-item-rows" class="space-y-2"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Location / Restaurant (Optional)</label>
                    <input type="text" name="location" placeholder="e.g. Mario, Canteen, Chai Point..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Total GST ₹ (Optional)</label>
                    <input type="number" id="food_gst_input" name="gst_amount" step="0.01" min="0" placeholder="e.g. 18.00 (leave blank if none)" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-medium text-slate-800">
                </div>
            </div>

            <!-- Live Calculation Summary Bar -->
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-4 text-slate-600">
                    <span>Items Subtotal: <strong id="summary-subtotal" class="text-slate-900 font-bold">₹0.00</strong></span>
                    <span>+</span>
                    <span>Total GST: <strong id="summary-gst" class="text-slate-900 font-bold">₹0.00</strong></span>
                </div>
                <div class="text-sm font-bold text-slate-900">
                    Grand Total Spend: <span id="summary-grand-total" class="text-indigo-600 font-black text-base">₹0.00</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Expense Tracking Mode</label>
                    <select name="expense_mode" id="expense_mode" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                        <option value="none">No new expense (habit only)</option>
                        <option value="separate">Create separate expense (total)</option>
                        <option value="sub_item">Sub-item under existing expense / group</option>
                        <option value="voluntary">Voluntary spend</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Link to Expense / Group</label>
                    <select name="parent_expense_id" id="parent_expense_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                        <option value="">Link to existing expense / group…</option>
                        @foreach($parentExpenses as $pe)
                            <option value="{{ $pe->id }}"
                                data-date="{{ $pe->date->toDateString() }}"
                                data-amount="{{ $pe->totalAmount() }}"
                                data-remaining="{{ $pe->remaining_amount }}"
                                data-desc="{{ $pe->expenseGroup ? '['.$pe->expenseGroup->name.'] ' : '' }}{{ $pe->description }}"
                                @disabled($pe->remaining_amount <= 0)>
                                {{ $pe->expenseGroup ? '👥 [Group: '.$pe->expenseGroup->name.'] ' : '' }}{{ $pe->description }} · Total Spent: ₹{{ number_format($pe->totalAmount(), 2) }} (₹{{ number_format($pe->remaining_amount, 2) }} remaining) · {{ $pe->date->format('d M Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                        @foreach($paymentMethods as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Notes (Optional)</label>
                    <input type="text" name="notes" placeholder="Details & notes (optional)" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
            </div>

            <!-- Live Selected Expense Information Banner -->
            <div id="selected-expense-info" class="hidden rounded-xl border border-indigo-200 bg-indigo-50/70 p-3 text-xs text-indigo-900 leading-relaxed shadow-xs"></div>

            <p class="text-xs text-slate-400">Picking an existing expense or expense group auto-links items as sub-items, syncs the date, and keeps parent tracking intact.</p>

            <button class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold shadow-md transition cursor-pointer">Save food / snack items</button>
        </form>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 rounded-3xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold text-slate-400">Today's Snacks</span>
                <div class="text-2xl font-bold text-orange-500 mt-1">{{ $todaySnacks }}</div>
            </div>
            <div class="p-4 rounded-3xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold text-slate-400">Weekly Snack Spend</span>
                <div class="text-2xl font-bold mt-1">₹{{ number_format($weeklySnackSpend, 0) }}</div>
            </div>
            <div class="p-4 rounded-3xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold text-slate-400">Monthly Snack Spend</span>
                <div class="text-2xl font-bold mt-1">₹{{ number_format($monthlySnackSpend, 0) }}</div>
            </div>
            <div class="p-4 rounded-3xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold text-slate-400">Top Snack</span>
                <div class="text-base font-bold text-indigo-600 mt-1 truncate">{{ $frequentSnacks->first()?->item_name ?? '—' }}</div>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-400 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4">Date</th>
                            <th class="py-3.5 px-4">Item</th>
                            <th class="py-3.5 px-4">Category</th>
                            <th class="py-3.5 px-4">Qty</th>
                            <th class="py-3.5 px-4">Amount</th>
                            <th class="py-3.5 px-4">Linked expense</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($entries as $item)
                            <tr>
                                <td class="py-3 px-4 font-mono text-slate-500 whitespace-nowrap">{{ $item->date->format('d M') }}</td>
                                <td class="py-3 px-4 font-bold">{{ $item->item_name }}</td>
                                <td class="py-3 px-4">{{ $item->category?->name ?? '—' }}</td>
                                <td class="py-3 px-4">{{ $item->quantity }}</td>
                                <td class="py-3 px-4 font-bold">₹{{ number_format($item->totalAmount(), 2) }}@if((float) $item->gst_amount > 0) <span class="block text-[10px] text-slate-400">GST ₹{{ number_format($item->gst_amount, 2) }}</span>@endif</td>
                                <td class="py-3 px-4">
                                    @if($item->expense)
                                        <a href="{{ route('expenses.show', $item->expense) }}" class="text-sky-700 hover:underline">{{ $item->expense->description }}</a>
                                        @if($item->expense->expenseGroup)
                                            <span class="block text-[10px] text-slate-500">Group: {{ $item->expense->expenseGroup->name }}</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right space-x-2">
                                    <button type="button" class="text-slate-700 hover:underline" onclick="document.getElementById('edit-food-{{ $item->id }}').classList.toggle('hidden')">Edit</button>
                                    <form action="{{ route('food.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Delete item?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <tr id="edit-food-{{ $item->id }}" class="hidden bg-slate-50">
                                <td colspan="7" class="p-4">
                                    <form action="{{ route('food.update', $item) }}" method="POST" class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="item_name" value="{{ $item->item_name }}" required class="px-2 py-2 border border-slate-300 rounded-xl">
                                        <select name="category_id" class="px-2 py-2 border border-slate-300 rounded-xl">
                                            <option value="">Category</option>
                                            @foreach($categories as $c)
                                                <option value="{{ $c->id }}" @selected($item->category_id === $c->id)>{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" class="px-2 py-2 border border-slate-300 rounded-xl">
                                        <input type="number" step="0.01" name="amount" value="{{ $item->amount }}" class="px-2 py-2 border border-slate-300 rounded-xl">
                                        <input type="number" step="0.01" min="0" name="gst_amount" value="{{ $item->gst_amount }}" placeholder="GST ₹" class="px-2 py-2 border border-slate-300 rounded-xl">
                                        <input type="date" name="date" value="{{ $item->date->toDateString() }}" class="px-2 py-2 border border-slate-300 rounded-xl">
                                        <input type="text" name="location" value="{{ $item->location }}" placeholder="Location" class="px-2 py-2 border border-slate-300 rounded-xl">
                                        <select name="parent_expense_id" class="px-2 py-2 border border-slate-300 rounded-xl sm:col-span-2">
                                            <option value="">No linked expense</option>
                                            @if($item->auto_create_expense && $item->expense_id && ! $parentExpenses->contains('id', $item->expense_id))
                                                <option value="{{ $item->expense_id }}" selected>Its own expense #{{ $item->expense_id }} (auto-created)</option>
                                            @endif
                                            @foreach($parentExpenses as $pe)
                                                <option value="{{ $pe->id }}" @selected($item->expense_id === $pe->id) @disabled($pe->remaining_amount <= 0 && $item->expense_id !== $pe->id)>{{ $pe->description }} · ₹{{ number_format($pe->remaining_amount, 2) }} remaining</option>
                                            @endforeach
                                        </select>
                                        <label class="flex items-center gap-2"><input type="checkbox" name="is_snack" value="1" @checked($item->is_snack)> Snack</label>
                                        <button class="px-3 py-2 rounded-xl bg-slate-900 text-white font-semibold">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-8 text-center text-slate-400">No food entries yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $entries->links() }}</div>
        </div>
    </div>

    <script>
        const foodCategories = @json($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values());
        const rowsBox = document.getElementById('food-item-rows');
        const countSel = document.getElementById('item_count');
        const parentSel = document.getElementById('parent_expense_id');
        const modeSel = document.getElementById('expense_mode');
        const dateInput = document.getElementById('food_date_input');
        const gstInput = document.getElementById('food_gst_input');
        const infoBox = document.getElementById('selected-expense-info');
        const subtotalSpan = document.getElementById('summary-subtotal');
        const gstSpan = document.getElementById('summary-gst');
        const grandTotalSpan = document.getElementById('summary-grand-total');

        function getCurrentRowData() {
            const data = [];
            const rows = rowsBox.querySelectorAll('.food-item-row');
            rows.forEach((row, i) => {
                const name = row.querySelector(`input[name="items[${i}][item_name]"]`)?.value || '';
                const cat = row.querySelector(`select[name="items[${i}][category_id]"]`)?.value || '';
                const qty = row.querySelector(`input[name="items[${i}][quantity]"]`)?.value || '1';
                const amt = row.querySelector(`input[name="items[${i}][amount]"]`)?.value || '0';
                data.push({ name, cat, qty, amt });
            });
            return data;
        }

        function updateExpenseBanner() {
            if (!parentSel || !infoBox) return;
            const opt = parentSel.options[parentSel.selectedIndex];
            if (parentSel.value && opt) {
                modeSel.value = 'sub_item';
                const expDate = opt.dataset.date;
                const expAmount = parseFloat(opt.dataset.amount || '0');
                const expDesc = opt.dataset.desc;

                if (expDate && dateInput) {
                    dateInput.value = expDate;
                }

                let subtotal = 0;
                const rows = rowsBox.querySelectorAll('.food-item-row');
                rows.forEach((row, i) => {
                    const amtVal = parseFloat(row.querySelector(`input[name="items[${i}][amount]"]`)?.value || '0');
                    if (!isNaN(amtVal) && amtVal > 0) {
                        subtotal += amtVal;
                    }
                });
                const gstVal = parseFloat(gstInput?.value || '0') || 0;
                const currentFoodTotal = subtotal + gstVal;
                const remainingDiff = expAmount - currentFoodTotal;

                let statusBadge = '';
                if (remainingDiff > 0) {
                    statusBadge = `<span class="text-emerald-700 font-bold">₹${remainingDiff.toFixed(2)} remaining unallocated</span>`;
                } else if (remainingDiff === 0) {
                    statusBadge = `<span class="text-indigo-700 font-bold">100% matched exactly</span>`;
                } else {
                    statusBadge = `<span class="text-rose-600 font-bold">⚠️ Exceeds parent by ₹${Math.abs(remainingDiff).toFixed(2)}</span>`;
                }

                infoBox.innerHTML = `📌 Linked to: <strong>${expDesc}</strong> · Linking <strong class="text-indigo-700 font-black">₹${currentFoodTotal.toFixed(2)}</strong> out of <strong class="text-slate-900 font-black">₹${expAmount.toFixed(2)}</strong> (${statusBadge}) · Date auto-set to <strong>${expDate}</strong>`;
                infoBox.classList.remove('hidden');
            } else {
                infoBox.classList.add('hidden');
            }
        }

        function calculateTotals() {
            let subtotal = 0;
            const rows = rowsBox.querySelectorAll('.food-item-row');
            rows.forEach((row, i) => {
                const amtVal = parseFloat(row.querySelector(`input[name="items[${i}][amount]"]`)?.value || '0');
                if (!isNaN(amtVal) && amtVal > 0) {
                    subtotal += amtVal;
                }
            });
            const gstVal = parseFloat(gstInput?.value || '0') || 0;
            const grandTotal = subtotal + gstVal;

            if (subtotalSpan) subtotalSpan.textContent = `₹${subtotal.toFixed(2)}`;
            if (gstSpan) gstSpan.textContent = `₹${gstVal.toFixed(2)}`;
            if (grandTotalSpan) grandTotalSpan.textContent = `₹${grandTotal.toFixed(2)}`;

            updateExpenseBanner();
        }

        parentSel?.addEventListener('change', updateExpenseBanner);

        function renderRows(targetCount, updateCountInput = true) {
            const existing = getCurrentRowData();
            let n = parseInt(targetCount || countSel.value || '1', 10);
            if (isNaN(n) || n < 1) n = 1;
            if (n > 50) n = 50;

            if (updateCountInput && countSel) {
                countSel.value = n;
            }

            let html = '';
            for (let i = 0; i < n; i++) {
                const prev = existing[i] || { name: '', cat: '', qty: '1', amt: '0' };
                const catOpts = foodCategories.map(c => `<option value="${c.id}" ${String(c.id) === String(prev.cat) ? 'selected' : ''}>${c.name}</option>`).join('');
                html += `<div class="food-item-row grid grid-cols-12 gap-2 items-end">
                    <div class="col-span-12 sm:col-span-4">
                        ${i === 0 ? '<label class="block text-xs font-semibold text-slate-700 mb-1">Item name *</label>' : ''}
                        <input type="text" name="items[${i}][item_name]" value="${prev.name}" ${i === 0 ? 'required' : ''} placeholder="e.g. Dosa, Coffee, Sandwich..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        ${i === 0 ? '<label class="block text-xs font-semibold text-slate-700 mb-1">Food category</label>' : ''}
                        <select name="items[${i}][category_id]" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl"><option value="">—</option>${catOpts}</select>
                    </div>
                    <div class="col-span-3 sm:col-span-2">
                        ${i === 0 ? '<label class="block text-xs font-semibold text-slate-700 mb-1">Qty</label>' : ''}
                        <input type="number" name="items[${i}][quantity]" value="${prev.qty}" min="1" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    </div>
                    <div class="col-span-3 sm:col-span-3">
                        ${i === 0 ? '<label class="block text-xs font-semibold text-slate-700 mb-1">Amount ₹</label>' : ''}
                        <div class="flex items-center gap-1">
                            <input type="number" step="0.01" name="items[${i}][amount]" value="${prev.amt}" min="0" placeholder="0.00" class="item-amount-input w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-900">
                            ${i > 0 ? `<button type="button" onclick="removeItemRow(${i})" class="text-rose-500 font-bold px-2 py-1 text-lg hover:text-rose-700 cursor-pointer" title="Remove row">&times;</button>` : ''}
                        </div>
                    </div>
                </div>`;
            }
            rowsBox.innerHTML = html;
            calculateTotals();
        }

        function changeItemCount(delta) {
            const current = parseInt(countSel.value || '1', 10);
            const next = Math.max(1, Math.min(50, (isNaN(current) ? 1 : current) + delta));
            countSel.value = next;
            renderRows(next, true);
        }

        function removeItemRow(index) {
            const existing = getCurrentRowData();
            if (existing.length > 1) {
                existing.splice(index, 1);
                countSel.value = existing.length;
                let html = '';
                for (let i = 0; i < existing.length; i++) {
                    const prev = existing[i];
                    const catOpts = foodCategories.map(c => `<option value="${c.id}" ${String(c.id) === String(prev.cat) ? 'selected' : ''}>${c.name}</option>`).join('');
                    html += `<div class="food-item-row grid grid-cols-12 gap-2 items-end">
                        <div class="col-span-12 sm:col-span-4">
                            ${i === 0 ? '<label class="block text-xs font-semibold text-slate-700 mb-1">Item name *</label>' : ''}
                            <input type="text" name="items[${i}][item_name]" value="${prev.name}" ${i === 0 ? 'required' : ''} placeholder="Item ${i + 1}" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            ${i === 0 ? '<label class="block text-xs font-semibold text-slate-700 mb-1">Food category</label>' : ''}
                            <select name="items[${i}][category_id]" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl"><option value="">—</option>${catOpts}</select>
                        </div>
                        <div class="col-span-3 sm:col-span-2">
                            ${i === 0 ? '<label class="block text-xs font-semibold text-slate-700 mb-1">Qty</label>' : ''}
                            <input type="number" name="items[${i}][quantity]" value="${prev.qty}" min="1" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                        </div>
                        <div class="col-span-3 sm:col-span-3">
                            ${i === 0 ? '<label class="block text-xs font-semibold text-slate-700 mb-1">Amount ₹</label>' : ''}
                            <div class="flex items-center gap-1">
                                <input type="number" step="0.01" name="items[${i}][amount]" value="${prev.amt}" min="0" placeholder="0.00" class="item-amount-input w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-900">
                                ${i > 0 ? `<button type="button" onclick="removeItemRow(${i})" class="text-rose-500 font-bold px-2 py-1 text-lg hover:text-rose-700 cursor-pointer" title="Remove row">&times;</button>` : ''}
                            </div>
                        </div>
                    </div>`;
                }
                rowsBox.innerHTML = html;
                calculateTotals();
            }
        }

        countSel?.addEventListener('input', (e) => {
            const val = e.target.value.trim();
            if (val !== '') {
                const parsed = parseInt(val, 10);
                if (!isNaN(parsed) && parsed >= 1) {
                    renderRows(parsed, false);
                }
            }
        });

        countSel?.addEventListener('blur', (e) => {
            const val = e.target.value.trim();
            if (val === '' || isNaN(parseInt(val, 10)) || parseInt(val, 10) < 1) {
                countSel.value = 1;
                renderRows(1, true);
            }
        });

        gstInput?.addEventListener('input', calculateTotals);
        rowsBox?.addEventListener('input', (e) => {
            if (e.target.matches('.item-amount-input') || e.target.name?.includes('[amount]')) {
                calculateTotals();
            }
        });

        renderRows(1, true);
    </script>
</x-app-layout>

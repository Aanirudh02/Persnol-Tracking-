<x-app-layout title="Bulk Entry">
    @php
        $typeLabels = ['expense' => 'Expense', 'personal' => 'Personal', 'income' => 'Income'];
        $oldRows = old('rows');
        $rows = is_array($oldRows) && $oldRows !== []
            ? $oldRows
            : collect(range(0, 4))->mapWithKeys(fn (int $i) => [$i => ['type' => 'expense', 'date' => now()->toDateString()]])->all();
        $nextIndex = (int) collect(array_keys($rows))->map(fn ($key) => (int) $key)->max() + 1;
    @endphp

    <div class="space-y-5">
        <x-money-tabs active="bulk" />

        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Bulk add expense &amp; income</h1>
                <p class="text-xs text-slate-500">One row per entry. Empty rows are skipped. If any row has a problem, nothing is saved and that row is highlighted.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <button type="button" onclick="bulkEntry.addRows(1)" class="rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">+ Add row</button>
                <button type="button" onclick="bulkEntry.addRows(5)" class="rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">+ 5 rows</button>
                <button type="button" onclick="document.getElementById('bulk-paste-panel').classList.toggle('hidden')" class="rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">📋 Paste a list</button>
            </div>
        </div>

        <div id="bulk-paste-panel" class="hidden space-y-2 rounded-2xl border border-sky-200 bg-sky-50/60 p-4 text-xs">
            <p class="font-semibold text-slate-700">One entry per line: <code>description, amount, method</code> (method optional, e.g. <code>Tea, 20, Cash</code>).</p>
            <textarea id="bulk-paste-text" rows="4" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-mono text-xs" placeholder="Auto fare, 60, Cash&#10;Groceries, 450, UPI"></textarea>
            <div class="flex flex-wrap items-center gap-2">
                <label class="font-semibold text-slate-600">Add as
                    <select id="bulk-paste-type" class="ml-1 rounded-lg border border-slate-300 px-2 py-1">
                        @foreach($typeLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="font-semibold text-slate-600">dated
                    <input type="date" id="bulk-paste-date" value="{{ now()->toDateString() }}" class="ml-1 rounded-lg border border-slate-300 px-2 py-1">
                </label>
                <button type="button" onclick="bulkEntry.pasteList()" class="rounded-lg bg-slate-900 px-3 py-1.5 font-bold text-white cursor-pointer">Add lines</button>
            </div>
        </div>

        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
                <p class="font-bold">Nothing was saved. Fix these rows:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach($errors->getMessages() as $key => $messages)
                        @php preg_match('/^rows\.(\d+)\./', $key, $m); @endphp
                        <li>{{ isset($m[1]) ? 'Row '.((int) $m[1] + 1).': ' : '' }}{{ $messages[0] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('bulk-entry.store') }}" id="bulk-entry-form" class="space-y-4">
            @csrf
            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <table class="w-full min-w-[980px] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-800/60">
                        <tr>
                            <th class="px-2 py-2.5 w-8">#</th>
                            <th class="px-2 py-2.5 w-28">Type</th>
                            <th class="px-2 py-2.5 w-32">Date</th>
                            <th class="px-2 py-2.5 w-24">Time</th>
                            <th class="px-2 py-2.5">Description / source</th>
                            <th class="px-2 py-2.5 w-40">Category</th>
                            <th class="px-2 py-2.5 w-28">Amount ₹</th>
                            <th class="px-2 py-2.5 w-28">Method</th>
                            <th class="px-2 py-2.5 w-48">Options</th>
                            <th class="px-2 py-2.5 w-8"></th>
                        </tr>
                    </thead>
                    <tbody id="bulk-rows" class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($rows as $index => $row)
                            @php
                                $rowType = in_array($row['type'] ?? 'expense', array_keys($typeLabels), true) ? $row['type'] : 'expense';
                                $rowHasError = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, "rows.{$index}."));
                            @endphp
                            <tr data-row class="{{ $rowHasError ? 'bg-rose-50 dark:bg-rose-950/30' : '' }}">
                                @include('finance.bulk_entry._row', ['index' => $index, 'row' => $row, 'rowType' => $rowType])
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 text-xs shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-4">
                    <div><span class="block text-[10px] font-bold uppercase text-slate-400">Expenses</span><strong id="bulk-total-expense" class="text-base text-rose-600">₹0.00</strong></div>
                    <div><span class="block text-[10px] font-bold uppercase text-slate-400">Personal</span><strong id="bulk-total-personal" class="text-base text-pink-600">₹0.00</strong></div>
                    <div><span class="block text-[10px] font-bold uppercase text-slate-400">Income</span><strong id="bulk-total-income" class="text-base text-emerald-600">₹0.00</strong></div>
                    <div id="bulk-balances" class="flex flex-wrap gap-4 border-l border-slate-200 pl-4 dark:border-slate-700"></div>
                </div>
                <button type="submit" class="rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-bold text-white hover:bg-slate-800 cursor-pointer">Save all</button>
            </div>
        </form>
    </div>

    <datalist id="bulk-family-members">
        <option value="Me"></option>
        @foreach($familyMembers as $member)
            <option value="{{ $member }}"></option>
        @endforeach
    </datalist>

    <template id="bulk-row-template">
        @include('finance.bulk_entry._row', ['index' => '__INDEX__', 'row' => ['type' => 'expense', 'date' => now()->toDateString()], 'rowType' => 'expense'])
    </template>

    <script>
        window.bulkEntry = (() => {
            const categoriesByType = @js($categoriesByType);
            const paymentMethods = @js($paymentMethods);
            const currentBalances = @js($currentBalances);
            let nextIndex = {{ $nextIndex }};
            const tbody = document.getElementById('bulk-rows');
            const money = (value) => '₹' + Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            function fillCategories(row, keepValue = true) {
                const type = row.querySelector('[data-field="type"]').value;
                const select = row.querySelector('[data-field="category_id"]');
                const current = keepValue ? select.value : '';
                select.innerHTML = '';
                const blank = document.createElement('option');
                blank.value = '';
                blank.textContent = type === 'personal' ? 'Pick category' : '— General —';
                select.appendChild(blank);
                (categoriesByType[type] || []).forEach((category) => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    if (String(category.id) === String(current)) option.selected = true;
                    select.appendChild(option);
                });
                row.querySelectorAll('[data-only]').forEach((el) => {
                    el.classList.toggle('hidden', !el.dataset.only.split(' ').includes(type));
                });
            }

            function wire(row) {
                row.querySelector('[data-field="type"]').addEventListener('change', () => { fillCategories(row, false); updateTotals(); });
                row.querySelectorAll('input, select').forEach((el) => el.addEventListener('input', updateTotals));
                row.querySelector('[data-remove]').addEventListener('click', () => { row.remove(); updateTotals(); });
            }

            function addRow(values = {}) {
                const html = document.getElementById('bulk-row-template').innerHTML.replaceAll('__INDEX__', nextIndex++);
                const tr = document.createElement('tr');
                tr.setAttribute('data-row', '');
                tr.innerHTML = html;
                tbody.appendChild(tr);
                Object.entries(values).forEach(([field, value]) => {
                    const el = tr.querySelector(`[data-field="${field}"]`);
                    if (el) el.value = value;
                });
                wire(tr);
                fillCategories(tr, false);
                tr.querySelector('[data-field="number"]').textContent = tbody.querySelectorAll('[data-row]').length;
                return tr;
            }

            function updateTotals() {
                const totals = { expense: 0, personal: 0, income: 0 };
                const change = {};
                tbody.querySelectorAll('[data-row]').forEach((row, i) => {
                    row.querySelector('[data-field="number"]').textContent = i + 1;
                    const type = row.querySelector('[data-field="type"]').value;
                    const amount = parseFloat(row.querySelector('[data-field="amount"]').value) || 0;
                    const method = row.querySelector('[data-field="payment_method"]').value;
                    totals[type] += amount;
                    const unlogged = type === 'expense' && row.querySelector('[data-field="is_unlogged"]')?.checked;
                    const addToMain = type !== 'personal' || row.querySelector('[data-field="add_to_main"]')?.checked;
                    if (unlogged || !addToMain) return;
                    change[method] = (change[method] || 0) + (type === 'income' ? amount : -amount);
                });
                document.getElementById('bulk-total-expense').textContent = money(totals.expense);
                document.getElementById('bulk-total-personal').textContent = money(totals.personal);
                document.getElementById('bulk-total-income').textContent = money(totals.income);
                const box = document.getElementById('bulk-balances');
                box.innerHTML = '';
                Object.keys(currentBalances).forEach((method) => {
                    const after = currentBalances[method] + (change[method] || 0);
                    const div = document.createElement('div');
                    div.innerHTML = `<span class="block text-[10px] font-bold uppercase text-slate-400"></span><strong class="text-base"></strong>`;
                    div.querySelector('span').textContent = method + ' after saving';
                    div.querySelector('strong').textContent = money(after);
                    div.querySelector('strong').classList.add(after < 0 ? 'text-rose-600' : 'text-slate-900');
                    box.appendChild(div);
                });
            }

            function pasteList() {
                const type = document.getElementById('bulk-paste-type').value;
                const date = document.getElementById('bulk-paste-date').value;
                const lines = document.getElementById('bulk-paste-text').value.split(/\r?\n/).map((l) => l.trim()).filter(Boolean);
                // Reuse empty rows first
                const emptyRows = [...tbody.querySelectorAll('[data-row]')].filter((row) => !row.querySelector('[data-field="amount"]').value && !row.querySelector('[data-field="description"]').value);
                lines.forEach((line) => {
                    const parts = line.split(/\t|,/).map((p) => p.trim()).filter(Boolean);
                    const amountIndex = parts.findIndex((p) => /^₹?\d+(\.\d+)?$/.test(p.replace(/,/g, '')));
                    if (amountIndex === -1) return;
                    const amount = parts[amountIndex].replace(/[₹,]/g, '');
                    const methodIndex = parts.findIndex((p, i) => i !== amountIndex && paymentMethods.some((m) => m.toLowerCase() === p.toLowerCase()));
                    const method = methodIndex >= 0 ? paymentMethods.find((m) => m.toLowerCase() === parts[methodIndex].toLowerCase()) : paymentMethods[0];
                    const description = parts.filter((p, i) => i !== amountIndex && i !== methodIndex).join(', ');
                    const row = emptyRows.shift();
                    if (row) {
                        row.querySelector('[data-field="type"]').value = type;
                        fillCategories(row, false);
                        row.querySelector('[data-field="date"]').value = date;
                        row.querySelector('[data-field="description"]').value = description;
                        row.querySelector('[data-field="amount"]').value = amount;
                        row.querySelector('[data-field="payment_method"]').value = method;
                    } else {
                        addRow({ type, date, description, amount, payment_method: method });
                    }
                });
                document.getElementById('bulk-paste-text').value = '';
                document.getElementById('bulk-paste-panel').classList.add('hidden');
                updateTotals();
            }

            tbody.querySelectorAll('[data-row]').forEach((row) => { wire(row); fillCategories(row, true); });
            updateTotals();

            return {
                addRows(count) { for (let i = 0; i < count; i++) addRow(); updateTotals(); },
                pasteList,
            };
        })();
    </script>
</x-app-layout>

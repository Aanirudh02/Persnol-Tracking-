@props([
    'methods' => [],
    'enabled' => false,
    'lines' => [],
    'includeGst' => false,
])

<div class="rounded-2xl border border-sky-200 bg-sky-50/50 p-4 space-y-3">
    <label class="flex items-center gap-2 font-semibold text-slate-800 cursor-pointer">
        <input id="add-group-expense" type="checkbox" name="add_group_expense" value="1" @checked($enabled) class="rounded border-slate-300 text-sky-600">
        <span>Pay with more than one method (e.g. UPI + Cash)</span>
    </label>

    <div id="group-expense-lines" class="hidden space-y-3 pt-1">
        <div class="flex items-center justify-between">
            <p class="text-xs text-slate-600">Enter how much you paid with each method:</p>
            <button type="button" id="add-group-line" class="text-xs font-semibold text-sky-700 hover:underline">+ Add payment line</button>
        </div>
        <div id="group-line-list" class="space-y-2"></div>
        <p id="group-total-hint" class="text-xs font-semibold text-slate-600"></p>
    </div>
</div>

<script>
    // Runs after the whole form is parsed, since it reads fields further down the page
    document.addEventListener('DOMContentLoaded', () => {
        const methods = @json(collect($methods)->reject(fn ($method) => strtolower($method) === 'split')->values());
        const savedLines = @json(array_values($lines));
        const includeGst = @json((bool) $includeGst);
        const toggle = document.getElementById('add-group-expense');
        const panel = document.getElementById('group-expense-lines');
        const list = document.getElementById('group-line-list');
        const hint = document.getElementById('group-total-hint');
        const form = toggle.closest('form');
        let lineCount = 0;

        const amountOf = id => Number(document.getElementById(id)?.value) || 0;
        const hasPayers = () => [...form.querySelectorAll('.friend-id-select')].some(select => select.value);
        const amountInputs = () => [...list.querySelectorAll('.group-amount')];

        // With a payer split the breakdown covers what I paid, otherwise the whole expense
        function getTarget() {
            const billTotal = amountOf('expense-total') + amountOf('expense-gst');

            if (hasPayers()) {
                return amountOf('split_paid_by_me_amount') || billTotal;
            }

            return includeGst ? billTotal : amountOf('expense-total');
        }

        function updateHint() {
            const total = amountInputs().reduce((sum, input) => sum + (Number(input.value) || 0), 0);
            const target = getTarget();
            const label = hasPayers() ? 'your paid amount' : 'total amount';

            hint.textContent = `Breakdown: ₹${total.toFixed(2)} of ₹${target.toFixed(2)} (${label})`;
            hint.className = `text-xs font-semibold ${Math.abs(total - target) < 0.05 ? 'text-emerald-700' : 'text-amber-700'}`;
        }

        // With two lines, typing one amount fills the other with the balance
        function fillOtherLine(typed) {
            typed.dataset.manual = '1';
            const inputs = amountInputs();
            const other = inputs.find(input => input !== typed);
            if (inputs.length !== 2 || other.dataset.manual) return;

            const balance = getTarget() - (Number(typed.value) || 0);
            other.value = typed.value !== '' && balance > 0 ? balance.toFixed(2) : '';
        }

        function addLine(amount = '', method = '') {
            const index = lineCount++;
            const preferred = ['upi', 'cash'].map(name => methods.find(item => item.toLowerCase() === name));
            const row = document.createElement('div');
            row.className = 'grid grid-cols-[1fr_1fr_auto] gap-2';
            row.innerHTML = `
                <input type="number" step="0.01" min="0.01" name="group_expenses[${index}][amount]" placeholder="Amount" class="group-amount w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                <select name="group_expenses[${index}][payment_method]" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"></select>
                <button type="button" class="px-2 text-rose-600 font-bold" aria-label="Remove payment">&times;</button>
            `;

            const input = row.querySelector('input');
            const select = row.querySelector('select');
            methods.forEach(item => select.add(new Option(item, item)));
            select.value = method || preferred[index] || methods[index] || methods[0] || 'Cash';
            input.value = amount;
            if (amount !== '') input.dataset.manual = '1';

            input.addEventListener('input', () => fillOtherLine(input));
            row.querySelector('button').addEventListener('click', () => {
                if (list.children.length <= 2) return;
                row.remove();
                updateHint();
            });
            list.appendChild(row);
        }

        function syncToggle() {
            panel.classList.toggle('hidden', !toggle.checked);
            while (toggle.checked && list.children.length < 2) addLine();
            list.querySelectorAll('input, select').forEach(field => field.disabled = !toggle.checked);
            updateHint();
        }

        savedLines.forEach(line => addLine(line.amount ?? '', line.payment_method ?? ''));
        toggle.addEventListener('change', syncToggle);
        document.getElementById('add-group-line').addEventListener('click', () => addLine());

        // Amounts also change through the quick split buttons, so watch clicks too
        ['input', 'change', 'click'].forEach(type => form.addEventListener(type, updateHint));
        syncToggle();
    });
</script>

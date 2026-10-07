@props([
    'formId',
    'linked' => [],
    'dynamic' => false,
    'targetLabel' => 'linked records',
    'fields' => [
        ['key' => 'payment_method', 'input' => 'payment_method', 'label' => 'Payment method'],
        ['key' => 'date', 'input' => 'date', 'label' => 'Date'],
        ['key' => 'amount', 'input' => 'amount', 'label' => 'Amount'],
    ],
])

{{--
    Asks "also apply these changes to the linked records?" when a synced field
    (payment method / date / amount) changed. Ticked fields are posted as sync_linked[].

    Static mode: pass :linked. Dynamic mode (one modal form reused for many records):
    set form.dataset.linkedRecords = JSON.stringify([...]) and call
    window.resetLinkedSyncPrompt(formId) after filling the form.
--}}
@if(! empty($linked) || $dynamic)
    @php $promptId = 'linked-sync-'.$formId; @endphp

    <div id="{{ $promptId }}" class="hidden fixed inset-0 z-[60] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🔗</span> Also update {{ $targetLabel }}?
                </h3>
                <p class="mt-1 text-xs text-slate-500">This record is linked to:</p>
                <ul data-sync-linked class="mt-1 space-y-0.5 text-xs font-semibold text-slate-800 dark:text-slate-200 list-disc pl-5"></ul>
            </div>

            <div>
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Apply these changes there too:</p>
                <div data-sync-changes class="space-y-1.5"></div>
                <p class="mt-2 text-[11px] text-slate-400">
                    Values that were deliberately different (e.g. a partial repayment) are left as they are.
                </p>
            </div>

            <div class="flex flex-col gap-2">
                <button type="button" data-sync-apply class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold cursor-pointer">Update both places</button>
                <button type="button" data-sync-skip class="w-full py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200 cursor-pointer">Only this record</button>
                <button type="button" data-sync-cancel class="w-full py-2 text-xs text-slate-400 hover:text-slate-600 font-semibold cursor-pointer">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const formId = @js($formId);
            const form = document.getElementById(formId);
            const modal = document.getElementById(@js($promptId));
            const fields = @js($fields);
            const staticLinked = @js(array_values($linked));
            if (!form || !modal) return;

            const linkedRecords = () => {
                if (staticLinked.length) return staticLinked;
                try { return JSON.parse(form.dataset.linkedRecords || '[]'); } catch (e) { return []; }
            };
            const valueOf = (field) => {
                const element = form.elements[field.input];
                return element ? String(element.value ?? '').trim() : null;
            };
            const sameValue = (field, a, b) => field.key === 'amount'
                ? Math.abs((parseFloat(a) || 0) - (parseFloat(b) || 0)) < 0.01
                : a === b;

            let original = {};
            let confirmed = false;
            const reset = () => {
                original = {};
                fields.forEach((field) => { original[field.key] = valueOf(field); });
                confirmed = false;
                form.querySelectorAll('input[name="sync_linked[]"]').forEach((el) => el.remove());
            };
            window.resetLinkedSyncPrompt = window.resetLinkedSyncPrompt || {};
            window.resetLinkedSyncPrompt[formId] = reset;

            const submitWith = (keys) => {
                form.querySelectorAll('input[name="sync_linked[]"]').forEach((el) => el.remove());
                keys.forEach((key) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'sync_linked[]';
                    hidden.value = key;
                    form.appendChild(hidden);
                });
                confirmed = true;
                modal.classList.add('hidden');
                form.requestSubmit ? form.requestSubmit() : form.submit();
            };

            form.addEventListener('submit', (event) => {
                const linked = linkedRecords();
                if (confirmed || !linked.length) return;
                const changes = fields.filter((field) => valueOf(field) !== null && !sameValue(field, valueOf(field), original[field.key]));
                if (!changes.length) return;

                event.preventDefault();

                const linkedList = modal.querySelector('[data-sync-linked]');
                linkedList.innerHTML = '';
                linked.forEach((record) => {
                    const item = document.createElement('li');
                    if (record.url) {
                        const anchor = document.createElement('a');
                        anchor.href = record.url;
                        anchor.target = '_blank';
                        anchor.className = 'hover:underline';
                        anchor.textContent = record.label;
                        item.appendChild(anchor);
                    } else {
                        item.textContent = record.label;
                    }
                    linkedList.appendChild(item);
                });

                const list = modal.querySelector('[data-sync-changes]');
                list.innerHTML = '';
                changes.forEach((field) => {
                    const row = document.createElement('label');
                    row.className = 'flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.checked = true;
                    checkbox.value = field.key;
                    checkbox.className = 'rounded border-slate-300 text-indigo-600';
                    const text = document.createElement('span');
                    text.textContent = `${field.label}: ${original[field.key] || '—'} → ${valueOf(field) || '—'}`;
                    row.append(checkbox, text);
                    list.appendChild(row);
                });
                modal.classList.remove('hidden');
            });

            modal.querySelector('[data-sync-apply]').addEventListener('click', () => {
                submitWith([...modal.querySelectorAll('[data-sync-changes] input:checked')].map((el) => el.value));
            });
            modal.querySelector('[data-sync-skip]').addEventListener('click', () => submitWith([]));
            modal.querySelector('[data-sync-cancel]').addEventListener('click', () => modal.classList.add('hidden'));

            document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', reset) : reset();
        })();
    </script>
@endif

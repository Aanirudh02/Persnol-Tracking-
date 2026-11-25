{{-- In-page replacement for window.confirm(). Usage: `if (await window.confirmDialog({ title, message, lines, confirmLabel, tone })) { ... }` --}}
<dialog id="confirm-dialog" aria-labelledby="confirm-dialog-title" class="m-auto w-[calc(100%-2rem)] max-w-md rounded-3xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900 dark:text-white">
    <form method="dialog" class="space-y-4 p-6">
        <div class="flex items-start gap-3">
            <span id="confirm-dialog-icon" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-lg" aria-hidden="true"></span>
            <div class="min-w-0 space-y-1">
                <h2 id="confirm-dialog-title" class="text-base font-black tracking-tight"></h2>
                <p id="confirm-dialog-message" class="text-xs text-slate-600 dark:text-slate-300"></p>
            </div>
        </div>
        <ul id="confirm-dialog-lines" class="hidden max-h-60 space-y-1.5 overflow-y-auto rounded-2xl border border-slate-200 bg-slate-50 p-3 text-xs dark:border-slate-700 dark:bg-slate-800/60"></ul>
        <p id="confirm-dialog-footnote" class="hidden text-[11px] text-slate-500 dark:text-slate-400"></p>
        <div class="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-end">
            <button type="submit" value="cancel" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">Cancel</button>
            <button type="submit" value="confirm" id="confirm-dialog-confirm" class="rounded-xl px-4 py-2 text-xs font-bold text-white"></button>
        </div>
    </form>
</dialog>

<script>
    window.confirmDialog = window.confirmDialog || (function () {
        const dialog = document.getElementById('confirm-dialog');
        const tones = {
            warning: { icon: '⚠️', iconClass: 'bg-amber-100 text-amber-700', buttonClass: 'bg-amber-600 hover:bg-amber-500' },
            danger: { icon: '🗑️', iconClass: 'bg-rose-100 text-rose-700', buttonClass: 'bg-rose-600 hover:bg-rose-500' },
            primary: { icon: '✔️', iconClass: 'bg-indigo-100 text-indigo-700', buttonClass: 'bg-indigo-600 hover:bg-indigo-500' },
        };

        dialog.addEventListener('click', event => {
            if (event.target === dialog) {
                dialog.close('cancel');
            }
        });

        return function ({ title = 'Are you sure?', message = '', lines = [], footnote = '', confirmLabel = 'Confirm', tone = 'primary' } = {}) {
            const style = tones[tone] || tones.primary;
            const icon = document.getElementById('confirm-dialog-icon');
            const list = document.getElementById('confirm-dialog-lines');
            const note = document.getElementById('confirm-dialog-footnote');
            const confirmButton = document.getElementById('confirm-dialog-confirm');

            document.getElementById('confirm-dialog-title').textContent = title;
            document.getElementById('confirm-dialog-message').textContent = message;
            icon.textContent = style.icon;
            icon.className = `flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-lg ${style.iconClass}`;
            confirmButton.textContent = confirmLabel;
            confirmButton.className = `rounded-xl px-4 py-2 text-xs font-bold text-white ${style.buttonClass}`;

            list.replaceChildren(...lines.map(line => {
                const item = document.createElement('li');
                item.className = 'flex flex-wrap justify-between gap-x-3 gap-y-0.5';
                const label = Object.assign(document.createElement('span'), { className: 'font-semibold text-slate-800 dark:text-slate-100', textContent: line.label });
                const detail = Object.assign(document.createElement('span'), { className: 'text-slate-500 dark:text-slate-400', textContent: line.detail || '' });
                item.append(label, detail);
                return item;
            }));
            list.classList.toggle('hidden', lines.length === 0);
            note.textContent = footnote;
            note.classList.toggle('hidden', !footnote);

            dialog.returnValue = '';
            dialog.showModal();
            confirmButton.focus();

            return new Promise(resolve => {
                dialog.addEventListener('close', () => resolve(dialog.returnValue === 'confirm'), { once: true });
            });
        };
    })();
</script>

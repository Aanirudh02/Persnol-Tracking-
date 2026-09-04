<x-app-layout title="Payments & Reconciliation">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Payments & Reconciliation</h1>
                <p class="text-xs text-slate-500">
                    Pending: <span class="font-bold text-amber-600">₹{{ number_format($totalPending, 2) }}</span> &bull;
                    Reconciled: <span class="font-bold text-emerald-600">₹{{ number_format($totalReconciled, 2) }}</span>
                </p>
            </div>
            <a href="{{ route('payments.create') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-md shadow-indigo-600/20 flex items-center gap-1.5 self-start">
                <span>+</span> Record Payment
            </a>
        </div>

        <!-- Payments Table -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            @if($payments->isEmpty())
                <div class="text-center py-16 text-slate-400 text-xs">
                    <span class="text-3xl block mb-2">🧾</span>
                    No payments found.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="py-3.5 px-4">Date</th>
                                <th class="py-3.5 px-4">Paid To</th>
                                <th class="py-3.5 px-4">Purpose</th>
                                <th class="py-3.5 px-4">Amount</th>
                                <th class="py-3.5 px-4">Method / Ref</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($payments as $pay)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                        {{ $pay->date->format('d M Y') }}
                                        <span class="block text-[10px] text-slate-400">{{ $pay->time }}</span>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">{{ $pay->paid_to }}</td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                                        {{ $pay->purpose }}
                                        @if($pay->notes)
                                            <span class="block text-[10px] text-slate-400 font-normal">{{ $pay->notes }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 font-bold text-slate-900 dark:text-white text-sm whitespace-nowrap">
                                        ₹{{ number_format($pay->amount, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                                        {{ $pay->payment_method }}
                                        @if($pay->reference)
                                            <span class="block text-[10px] font-mono text-slate-400">{{ $pay->reference }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($pay->status === 'Reconciled')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 flex items-center gap-1 w-max">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Reconciled
                                            </span>
                                            @if($pay->reconciliation)
                                                <span class="block text-[9px] text-slate-400 mt-0.5">On {{ $pay->reconciliation->reconciled_date->format('d M Y') }} by {{ $pay->reconciliation->reconciled_by }}</span>
                                            @endif
                                        @elseif($pay->status === 'Pending')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 w-max block">
                                                ⏳ Pending
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 w-max block">
                                                {{ $pay->status }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        @if($pay->status === 'Pending')
                                            <button
                                                type="button"
                                                onclick="window.openReconcileModal({{ $pay->id }}, '{{ $pay->paid_to }}', {{ $pay->amount }})"
                                                class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-[11px] shadow-sm mr-2 transition cursor-pointer"
                                            >
                                                Reconcile
                                            </button>
                                        @endif

                                        @if($pay->isEditableByUser())
                                            <a href="{{ route('payments.edit', $pay) }}" class="text-indigo-600 hover:text-indigo-500 font-semibold mr-2">Edit</a>
                                            <form action="{{ route('payments.destroy', $pay) }}" method="POST" class="inline" onsubmit="return confirm('Delete payment?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-500 font-semibold cursor-pointer">Delete</button>
                                            </form>
                                        @else
                                            <span class="text-slate-400 text-[11px] font-medium" title="Locked from editing">🔒 Locked</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- RECONCILIATION MODAL -->
    <div id="reconcile-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-base text-slate-900 dark:text-white">Reconcile Payment</h3>
                <button onclick="document.getElementById('reconcile-modal').classList.add('hidden')" class="text-slate-400 text-2xl font-bold">&times;</button>
            </div>

            <form id="reconcile-form" action="" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment To</label>
                    <input type="text" id="rec-paid-to" readonly class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-800 rounded-xl font-bold text-slate-700 dark:text-slate-300">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Reconciled Amount (₹) *</label>
                    <input type="number" step="0.01" name="reconciled_amount" id="rec-amount" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-bold text-emerald-600 text-base">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Reconciliation Date *</label>
                    <input type="date" name="reconciled_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Notes / Bank Ref</label>
                    <textarea name="notes" rows="2" placeholder="e.g. Bank statement verified" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white"></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs shadow-md shadow-emerald-600/20 transition">
                        Confirm & Mark as Reconciled
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.openReconcileModal = function(id, paidTo, amount) {
            const modal = document.getElementById('reconcile-modal');
            const form = document.getElementById('reconcile-form');
            form.action = `/finance/payments/${id}/reconcile`;
            document.getElementById('rec-paid-to').value = paidTo;
            document.getElementById('rec-amount').value = amount;
            modal.classList.remove('hidden');
        };
    </script>
</x-app-layout>

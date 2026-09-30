<x-app-layout title="Daily Cash Register & Balances">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">Daily Balances & Cashflow</h1>
                <p class="text-xs text-slate-500">Day-by-day cash register. Today's current balance rolls over as tomorrow's opening balance.</p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()" class="rounded-2xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm">
            </form>
        </div>

        <!-- Today's Highlight Card -->
        @if($todayData)
            <div class="rounded-3xl border border-indigo-100 bg-gradient-to-r from-indigo-500/10 via-sky-500/10 to-indigo-500/10 p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-lg bg-indigo-600 px-2.5 py-1 text-[11px] font-black uppercase text-white">Today's Register</span>
                            <span class="text-xs font-bold text-slate-600">{{ Carbon\Carbon::today()->format('l, d F Y') }}</span>
                        </div>
                        <div class="mt-3 flex flex-wrap items-baseline gap-4">
                            <div>
                                <span class="text-[11px] font-bold uppercase text-slate-500">Opening</span>
                                <div class="text-xl font-bold text-slate-700">₹{{ number_format($todayData['opening'], 2) }}</div>
                            </div>
                            <span class="text-slate-400 font-bold">+</span>
                            <div>
                                <span class="text-[11px] font-bold uppercase text-emerald-600">Inflow</span>
                                <div class="text-xl font-bold text-emerald-700">₹{{ number_format($todayData['income'], 2) }}</div>
                            </div>
                            <span class="text-slate-400 font-bold">-</span>
                            <div>
                                <span class="text-[11px] font-bold uppercase text-rose-600">Outflow</span>
                                <div class="text-xl font-bold text-rose-700">₹{{ number_format($todayData['expense'], 2) }}</div>
                            </div>
                            @if($todayData['adjustment'] != 0)
                                <span class="text-slate-400 font-bold">±</span>
                                <div>
                                    <span class="text-[11px] font-bold uppercase text-amber-600">Adjustment</span>
                                    <div class="text-xl font-bold text-amber-700">₹{{ number_format($todayData['adjustment'], 2) }}</div>
                                </div>
                            @endif
                            <span class="text-slate-400 font-bold">=</span>
                            <div>
                                <span class="text-[11px] font-black uppercase text-indigo-700">Current / Closing</span>
                                <div class="text-2xl font-black text-indigo-900">₹{{ number_format($todayData['closing'], 2) }}</div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="openBalanceModal('{{ $todayStr }}', '{{ $todayData['opening'] }}', '{{ $todayData['adjustment'] }}', '{{ $todayData['closing'] }}', '{{ addslashes($todayData['notes'] ?? '') }}')" class="rounded-2xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-md hover:bg-indigo-700 transition active:scale-95">
                        Adjust Today's Balance
                    </button>
                </div>
            </div>
        @endif

        <!-- Day by Day Cash Register Table -->
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50/70 p-4">
                <h3 class="font-bold text-slate-800 text-sm">Monthly Register: {{ Carbon\Carbon::parse($month.'-01')->format('F Y') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Opening</th>
                            <th class="px-4 py-3 text-emerald-700">Inflow (+)</th>
                            <th class="px-4 py-3 text-rose-700">Outflow (-)</th>
                            <th class="px-4 py-3 text-amber-700">Adjustment</th>
                            <th class="px-4 py-3 font-black text-slate-900">Closing / Current</th>
                            <th class="px-4 py-3">Notes</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold">
                        @foreach($days as $d)
                            <tr class="hover:bg-slate-50/80 transition {{ $d['is_today'] ? 'bg-indigo-50/40 font-bold' : '' }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <span>{{ $d['date']->format('d M (D)') }}</span>
                                        @if($d['is_today'])
                                            <span class="rounded bg-indigo-600 px-1.5 py-0.2 text-[9px] font-black text-white">TODAY</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    ₹{{ number_format($d['opening'], 2) }}
                                    @if($d['is_opening_manual'])
                                        <span class="text-[9px] text-indigo-500 font-normal">(manual)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-emerald-600">
                                    {{ $d['income'] > 0 ? '+₹'.number_format($d['income'], 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-rose-600">
                                    {{ $d['expense'] > 0 ? '-₹'.number_format($d['expense'], 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-amber-600">
                                    {{ $d['adjustment'] != 0 ? ($d['adjustment'] > 0 ? '+' : '').'₹'.number_format($d['adjustment'], 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 font-black {{ $d['closing'] < 0 ? 'text-rose-600' : 'text-slate-900' }}">
                                    ₹{{ number_format($d['closing'], 2) }}
                                    @if($d['is_closing_manual'])
                                        <span class="text-[9px] text-amber-500 font-normal">(adjusted)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-400 font-normal text-[11px] max-w-xs truncate">
                                    {{ $d['notes'] ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" onclick="openBalanceModal('{{ $d['date_str'] }}', '{{ $d['opening'] }}', '{{ $d['adjustment'] }}', '{{ $d['closing'] }}', '{{ addslashes($d['notes'] ?? '') }}')" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-50">
                                        Adjust
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Adjust Daily Balance Modal -->
    <div id="balance-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-900">Adjust Daily Balance</h3>
                    <p class="text-xs text-slate-500" id="modal-date-label">Date: </p>
                </div>
                <button type="button" onclick="closeBalanceModal()" class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100">&times;</button>
            </div>
            <form action="{{ route('daily-balances.update') }}" method="POST" class="space-y-3 text-sm">
                @csrf
                <input type="hidden" name="record_date" id="modal_record_date">
                
                <div>
                    <label class="font-bold text-slate-700">Opening Balance (₹)</label>
                    <input type="number" step="0.01" name="opening_balance" id="modal_opening" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-bold">
                    <span class="text-[11px] text-slate-400">Override the initial balance at start of this day.</span>
                </div>

                <div>
                    <label class="font-bold text-slate-700">Manual Cash Adjustment (₹)</label>
                    <input type="number" step="0.01" name="manual_adjustment" id="modal_adjustment" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-bold">
                    <span class="text-[11px] text-slate-400">Add or deduct cash difference found in physical wallet/pocket.</span>
                </div>

                <div>
                    <label class="font-bold text-slate-700">Closing / Current Balance (₹)</label>
                    <input type="number" step="0.01" name="closing_balance" id="modal_closing" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-bold text-indigo-700">
                    <span class="text-[11px] text-slate-400">Directly set final closing balance for this day.</span>
                </div>

                <div>
                    <label class="font-bold text-slate-700">Notes</label>
                    <input type="text" name="notes" id="modal_notes" placeholder="Reason for adjustment..." class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="closeBalanceModal()" class="rounded-xl border border-slate-200 px-4 py-2 text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 font-bold text-white shadow-md hover:bg-indigo-700">Save Adjustment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openBalanceModal(dateStr, opening, adjustment, closing, notes) {
            document.getElementById('modal_record_date').value = dateStr;
            document.getElementById('modal-date-label').textContent = 'Date: ' + dateStr;
            document.getElementById('modal_opening').value = opening;
            document.getElementById('modal_adjustment').value = adjustment;
            document.getElementById('modal_closing').value = closing;
            document.getElementById('modal_notes').value = notes;
            document.getElementById('balance-modal').classList.remove('hidden');
            document.getElementById('balance-modal').classList.add('flex');
        }

        function closeBalanceModal() {
            document.getElementById('balance-modal').classList.add('hidden');
            document.getElementById('balance-modal').classList.remove('flex');
        }
    </script>
</x-app-layout>

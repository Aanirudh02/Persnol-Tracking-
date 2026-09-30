<x-app-layout title="Savings & Goals">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">Savings & Funds</h1>
                <p class="text-xs text-slate-500">Track and allocate your savings, emergency funds, and goal deposits.</p>
            </div>
            <button onclick="document.getElementById('add-saving-modal').classList.remove('hidden'); document.getElementById('add-saving-modal').classList.add('flex');" class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-md hover:bg-indigo-700 transition active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Record Savings
            </button>
        </div>

        <!-- Metric Summary Cards -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Saved Pool</span>
                <div class="mt-1 text-2xl font-black text-slate-900">₹{{ number_format($totalSaved, 2) }}</div>
                <span class="text-[11px] text-slate-500">Lifetime accumulated</span>
            </div>
            <div class="rounded-3xl border border-emerald-100 bg-emerald-50/50 p-4 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Net Usable Savings</span>
                <div class="mt-1 text-2xl font-black text-emerald-700">₹{{ number_format($netAvailable, 2) }}</div>
                <span class="text-[11px] text-emerald-600 font-semibold">Available balance</span>
            </div>
            <div class="rounded-3xl border border-indigo-100 bg-indigo-50/50 p-4 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-600">Saved This Month</span>
                <div class="mt-1 text-2xl font-black text-indigo-700">₹{{ number_format($currentMonthSaved, 2) }}</div>
                <span class="text-[11px] text-indigo-600 font-semibold">Current month</span>
            </div>
            <div class="rounded-3xl border border-rose-100 bg-rose-50/50 p-4 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600">Total Withdrawn</span>
                <div class="mt-1 text-2xl font-black text-rose-700">₹{{ number_format($withdrawnTotal, 2) }}</div>
                <span class="text-[11px] text-rose-600 font-semibold">Utilized from savings</span>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm text-xs">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-500 uppercase">Goal:</span>
                <a href="{{ route('savings.index') }}" class="rounded-xl px-3 py-1.5 font-bold transition {{ !request('goal') ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">All</a>
                @foreach($goals as $goal)
                    <a href="{{ route('savings.index', ['goal' => $goal]) }}" class="rounded-xl px-3 py-1.5 font-bold transition {{ request('goal') === $goal ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $goal }}</a>
                @endforeach
            </div>
        </div>

        <!-- Savings List -->
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="divide-y divide-slate-100">
                @forelse($savings as $saving)
                    <div class="p-5 text-sm hover:bg-slate-50/50 transition space-y-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-slate-900 text-base">{{ $saving->source }}</span>
                                    @if($saving->goal_or_category)
                                        <span class="rounded-lg bg-indigo-50 border border-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-700">🎯 {{ $saving->goal_or_category }}</span>
                                    @endif
                                    <span class="rounded-lg border px-2 py-0.5 text-[10px] font-bold uppercase {{ $saving->statusBadgeColor() }}">{{ $saving->status }}</span>
                                </div>
                                <p class="text-xs text-slate-500">{{ $saving->saved_date->format('d M Y') }} · Added on {{ $saving->created_at->format('h:i A') }}</p>
                                @if($saving->notes)
                                    <p class="text-xs text-slate-600 bg-slate-50 p-2 rounded-xl border border-slate-100 inline-block">{{ $saving->notes }}</p>
                                @endif
                            </div>

                            <div class="flex items-center gap-4 text-right">
                                <div>
                                    <div class="text-xs uppercase font-bold text-slate-400">Available / Original</div>
                                    <div class="text-lg font-black text-slate-900">
                                        <span class="text-emerald-600">₹{{ number_format($saving->netAvailable(), 2) }}</span>
                                        <span class="text-slate-400 text-sm font-normal"> / ₹{{ number_format($saving->amount, 2) }}</span>
                                    </div>
                                    @if($saving->withdrawn_amount > 0)
                                        <div class="text-[11px] font-semibold text-rose-500">₹{{ number_format($saving->withdrawn_amount, 2) }} withdrawn</div>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($saving->netAvailable() > 0 && $saving->status === 'active')
                                        <button onclick="openWithdrawModal('{{ $saving->id }}', '{{ $saving->source }}', '{{ $saving->netAvailable() }}')" class="rounded-xl border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-100 transition">
                                            Withdraw
                                        </button>
                                    @endif
                                    <a href="{{ route('savings.edit', $saving) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Edit</a>
                                    <form action="{{ route('savings.destroy', $saving) }}" method="POST" onsubmit="return confirm('Delete this savings entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-slate-400">
                        <p class="text-base font-bold text-slate-700">No savings records found</p>
                        <p class="text-xs text-slate-400 mt-1">Start setting aside money into your savings and goals.</p>
                    </div>
                @endforelse
            </div>
            @if($savings->hasPages())
                <div class="p-4 border-t border-slate-100">{{ $savings->links() }}</div>
            @endif
        </div>
    </div>

    <!-- Record Savings Modal -->
    <div id="add-saving-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
        <div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-black text-slate-900">Record New Savings</h3>
                <button type="button" onclick="document.getElementById('add-saving-modal').classList.add('hidden'); document.getElementById('add-saving-modal').classList.remove('flex');" class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100">&times;</button>
            </div>
            <form action="{{ route('savings.store') }}" method="POST" class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2">
                    <label class="font-bold text-slate-700">Amount Saved (₹)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" required placeholder="5000.00" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-base font-black text-indigo-600">
                </div>
                <div>
                    <label class="font-bold text-slate-700">Source / How it came</label>
                    <input type="text" name="source" required placeholder="Salary, Gift, Side gig..." class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-semibold">
                </div>
                <div>
                    <label class="font-bold text-slate-700">Goal / Fund Allocation</label>
                    <input type="text" name="goal_or_category" placeholder="Emergency, Bike, Vacation..." class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-semibold">
                </div>
                <div>
                    <label class="font-bold text-slate-700">Date Saved</label>
                    <input type="date" name="saved_date" value="{{ date('Y-m-d') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="font-bold text-slate-700">Status</label>
                    <select name="status" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-semibold">
                        <option value="active">Active (Available)</option>
                        <option value="locked">Locked (Reserved)</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="font-bold text-slate-700">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Optional notes..." class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs"></textarea>
                </div>
                <div class="sm:col-span-2 flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('add-saving-modal').classList.add('hidden'); document.getElementById('add-saving-modal').classList.remove('flex');" class="rounded-xl border border-slate-200 px-4 py-2 text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 font-bold text-white shadow-md hover:bg-indigo-700">Save Deposit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Withdraw Modal -->
    <div id="withdraw-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-black text-slate-900">Withdraw from Savings</h3>
                <button type="button" onclick="document.getElementById('withdraw-modal').classList.add('hidden'); document.getElementById('withdraw-modal').classList.remove('flex');" class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100">&times;</button>
            </div>
            <form id="withdraw-form" method="POST" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="font-bold text-slate-700">Withdrawal Amount (₹)</label>
                    <input type="number" step="0.01" min="0.01" name="withdraw_amount" id="withdraw-amount-input" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-base font-black text-rose-600">
                    <p class="text-xs text-slate-500 mt-1" id="max-withdraw-label">Max available: ₹0.00</p>
                </div>
                <div>
                    <label class="font-bold text-slate-700">Withdrawal Purpose / Notes</label>
                    <input type="text" name="notes" placeholder="Used for emergency repair, bill, etc." class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-semibold">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('withdraw-modal').classList.add('hidden'); document.getElementById('withdraw-modal').classList.remove('flex');" class="rounded-xl border border-slate-200 px-4 py-2 text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-xl bg-rose-600 px-5 py-2 font-bold text-white shadow-md hover:bg-rose-700">Confirm Withdrawal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openWithdrawModal(id, source, maxAmount) {
            const form = document.getElementById('withdraw-form');
            form.action = `/finance/savings/${id}/withdraw`;
            const input = document.getElementById('withdraw-amount-input');
            input.max = maxAmount;
            input.value = maxAmount;
            document.getElementById('max-withdraw-label').textContent = `Max available for "${source}": ₹${Number(maxAmount).toFixed(2)}`;
            document.getElementById('withdraw-modal').classList.remove('hidden');
            document.getElementById('withdraw-modal').classList.add('flex');
        }
    </script>
</x-app-layout>

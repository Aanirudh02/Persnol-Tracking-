<x-app-layout title="Personal Expenses">
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Personal Expenses</h1>
                <p class="text-xs text-slate-500">Track family, hotel, shopping, tour, and personal spending independently.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('statements.create', ['type' => 'personal']) }}" style="background-color: #db2777; color: #ffffff;" class="px-4 py-2 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <span>📄</span> Take Statement
                </a>
                <button type="button" onclick="openPersonalModal()" style="background-color: #be185d; color: #ffffff;" class="px-4 py-2 rounded-xl bg-pink-700 hover:bg-pink-800 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <span>+</span> Add Personal Expense
                </button>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-3xl border border-pink-100 dark:border-pink-950/40 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Filtered Spend</span>
                    <span class="text-lg">🛍️</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-pink-600">₹{{ number_format($totalAmount, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 text-[11px] text-slate-500">
                    {{ $expenses->total() }} recorded transaction(s)
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">This Month's Spend</span>
                    <span class="text-lg">📅</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">₹{{ number_format($monthAmount, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 text-[11px] text-slate-500">
                    Current calendar month
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Voluntary Spending</span>
                    <span class="text-lg">✨</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-amber-500">₹{{ number_format($voluntaryAmount, 2) }}</div>
                <div class="mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 text-[11px] text-slate-500">
                    Discretionary / Leisure
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <form action="{{ route('personal-expenses.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1">Category</label>
                    <select name="category_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-slate-400 mb-1">Payment Method</label>
                    <select name="payment_method" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                        <option value="">All Methods</option>
                        @foreach($paymentMethods as $pm)
                            <option value="{{ $pm }}" {{ request('payment_method') == $pm ? 'selected' : '' }}>{{ $pm }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-slate-400 mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-slate-400 mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                </div>
            </form>
        </div>

        <!-- Personal Expenses Table -->
        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
            @if($expenses->isEmpty())
                <div class="text-center py-16 text-slate-400 text-xs">
                    <span class="text-3xl block mb-2">🛍️</span>
                    No personal expenses recorded for this filter.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-3.5 px-4">Date</th>
                                <th class="py-3.5 px-4">Description</th>
                                <th class="py-3.5 px-4">Category</th>
                                <th class="py-3.5 px-4">Amount</th>
                                <th class="py-3.5 px-4">Method</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($expenses as $exp)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                        {{ $exp->date->format('d M Y') }}
                                        @if($exp->time)
                                            <span class="block text-[10px] text-slate-400">{{ $exp->time }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-semibold text-slate-900 dark:text-white block">{{ $exp->description ?: ($exp->category?->name ?? 'Personal Expense') }}</span>
                                        @if($exp->notes)
                                            <span class="text-[11px] text-slate-400 italic block mt-0.5">{{ $exp->notes }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-pink-50 dark:bg-pink-950/40 text-pink-700 dark:text-pink-300 border border-pink-200 dark:border-pink-900">
                                            {{ $exp->category?->name ?? 'Personal' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-pink-600 text-sm whitespace-nowrap">
                                        ₹{{ number_format($exp->amount, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400 whitespace-nowrap font-medium">
                                        {{ $exp->payment_method ?: 'Cash' }}
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        @if($exp->expense_id)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                ✓ In Main Expenses
                                            </span>
                                        @else
                                            <div class="flex items-center gap-1.5">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                                    Separate
                                                </span>
                                                <form action="{{ route('personal-expenses.convert-to-normal', $exp) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-900 hover:bg-slate-800 text-white transition shadow-xs cursor-pointer" title="Record this to Main Expenses & opening balance">
                                                        + Add to Main
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" onclick="openEditPersonalModal({{ json_encode($exp) }})" class="text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition cursor-pointer">
                                                Edit
                                            </button>
                                            <form action="{{ route('personal-expenses.destroy', $exp) }}" method="POST" onsubmit="return confirm('Delete this personal expense?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline cursor-pointer">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($expenses->hasPages())
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                        {{ $expenses->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- ADD/EDIT MODAL (VANILLA JS, NO OVERLAPPING) -->
        <div id="personal-expense-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" onclick="if(event.target === this) closePersonalModal()">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🛍️</span>
                        <h3 id="personal-modal-title" class="font-bold text-base text-slate-900 dark:text-white">Add Personal Expense</h3>
                    </div>
                    <button type="button" onclick="closePersonalModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer">&times;</button>
                </div>

                <form id="personal-expense-form" action="{{ route('personal-expenses.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" id="personal-method-input" value="POST">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                        <select name="category_id" id="personal-category-input" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Amount (₹)</label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="personal-amount-input" required placeholder="0.00" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:bg-white focus:ring-2 focus:ring-pink-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Date</label>
                            <input type="date" name="date" id="personal-date-input" value="{{ date('Y-m-d') }}" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Time</label>
                            <input type="time" name="time" id="personal-time-input" value="{{ date('H:i') }}" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Description (e.g. Hotel with Family, Shopping, Spotify)</label>
                        <input type="text" name="description" id="personal-desc-input" placeholder="e.g. Weekend snacks or Shopping" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Method</label>
                        <select name="payment_method" id="personal-pm-input" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                            @foreach($paymentMethods as $pm)
                                <option value="{{ $pm }}">{{ $pm }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Notes (Optional)</label>
                        <textarea name="notes" id="personal-notes-input" rows="2" placeholder="Optional notes..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500"></textarea>
                    </div>

                    <!-- Dual Record Link Toggle Card -->
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-1.5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="record_as_normal_expense" id="personal-dual-input" value="1" class="rounded border-slate-300 text-pink-600 focus:ring-pink-500">
                            <span class="text-xs font-bold text-slate-900 dark:text-white">Also record as normal expense</span>
                        </label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Automatically creates and connects a matching expense entry in your regular expenses and updates opening balance.</p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" onclick="closePersonalModal()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-xs font-semibold shadow-md transition cursor-pointer">
                            Save Expense
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openPersonalModal() {
            document.getElementById('personal-modal-title').textContent = 'Add Personal Expense';
            document.getElementById('personal-expense-form').action = "{{ route('personal-expenses.store') }}";
            document.getElementById('personal-method-input').value = 'POST';
            document.getElementById('personal-amount-input').value = '';
            document.getElementById('personal-desc-input').value = '';
            document.getElementById('personal-notes-input').value = '';
            document.getElementById('personal-date-input').value = "{{ date('Y-m-d') }}";
            document.getElementById('personal-time-input').value = "{{ date('H:i') }}";
            document.getElementById('personal-dual-input').checked = false;
            
            const modal = document.getElementById('personal-expense-modal');
            modal.classList.remove('hidden');
        }

        function openEditPersonalModal(exp) {
            document.getElementById('personal-modal-title').textContent = 'Edit Personal Expense';
            document.getElementById('personal-expense-form').action = '/finance/personal-expenses/' + exp.id;
            document.getElementById('personal-method-input').value = 'PUT';
            
            if (exp.category_id) {
                document.getElementById('personal-category-input').value = exp.category_id;
            }
            document.getElementById('personal-amount-input').value = exp.amount;
            document.getElementById('personal-date-input').value = exp.date ? exp.date.substring(0, 10) : "{{ date('Y-m-d') }}";
            document.getElementById('personal-time-input').value = exp.time ? exp.time.substring(0, 5) : "{{ date('H:i') }}";
            document.getElementById('personal-desc-input').value = exp.description || '';
            if (exp.payment_method) {
                document.getElementById('personal-pm-input').value = exp.payment_method;
            }
            document.getElementById('personal-notes-input').value = exp.notes || '';
            document.getElementById('personal-dual-input').checked = !!exp.expense_id;

            const modal = document.getElementById('personal-expense-modal');
            modal.classList.remove('hidden');
        }

        function closePersonalModal() {
            document.getElementById('personal-expense-modal').classList.add('hidden');
        }
    </script>
</x-app-layout>

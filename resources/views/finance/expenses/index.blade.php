<x-app-layout title="Expenses">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Expenses</h1>
                <p class="text-xs text-slate-500">Total recorded: <span class="font-bold text-rose-600">₹{{ number_format($totalAmount, 2) }}</span></p>
            </div>
            <a href="{{ route('expenses.create') }}" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-semibold text-xs shadow-md shadow-sky-600/20 flex items-center gap-1.5 self-start transition active:scale-95">
                <span>+</span> Add Expense
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-sky-100 dark:border-slate-800 shadow-sm">
            <form action="{{ route('expenses.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1">Category</label>
                    <select name="category_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                        <option value="">All Categories</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 mb-1">Method</label>
                    <select name="payment_method" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                        <option value="">All Methods</option>
                        @foreach($paymentMethods ?? ['UPI', 'Cash', 'Card', 'Bank Transfer', 'Other'] as $m)
                            <option value="{{ $m }}" {{ request('payment_method') == $m ? 'selected' : '' }}>{{ $m }}</option>
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

        <!-- Expenses Table / List -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            @if($expenses->isEmpty())
                <div class="text-center py-16 text-slate-400 text-xs">
                    <span class="text-3xl block mb-2">💸</span>
                    No expense records found.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="py-3.5 px-4">Date</th>
                                <th class="py-3.5 px-4">Description</th>
                                <th class="py-3.5 px-4">Category</th>
                                <th class="py-3.5 px-4">Amount</th>
                                <th class="py-3.5 px-4">Method</th>
                                <th class="py-3.5 px-4">Paid By</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($expenses as $exp)
                                @php $isEditable = $exp->isEditableByUser(); @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                        {{ $exp->date->format('d M Y') }}
                                        <span class="block text-[10px] text-slate-400">{{ $exp->time }}</span>
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                        <a href="{{ route('expenses.show', $exp) }}" class="font-semibold text-slate-900 hover:underline">{{ $exp->description }}</a>
                                        @if($exp->receipt_image)
                                            <a href="{{ asset('storage/' . $exp->receipt_image) }}" target="_blank" class="inline-block ml-1 text-indigo-500 hover:underline text-[10px]">📷 receipt</a>
                                        @endif
                                        @if($exp->notes)
                                            <span class="block text-[10px] text-slate-400 font-normal">{{ $exp->notes }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ $exp->category?->name ?? 'Other' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-rose-600 dark:text-rose-400 text-sm whitespace-nowrap">
                                        ₹{{ number_format($exp->amount, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $exp->payment_method }}</td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $exp->paid_by }}</td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        @if($isEditable)
                                            <a href="{{ route('expenses.edit', $exp) }}" class="text-indigo-600 hover:text-indigo-500 font-semibold mr-2">Edit</a>
                                            <form action="{{ route('expenses.destroy', $exp) }}" method="POST" class="inline" onsubmit="return confirm('Archive this expense?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-500 font-semibold cursor-pointer">Delete</button>
                                            </form>
                                        @else
                                            <span class="text-slate-400 text-[11px] font-medium" title="Edit window expired">🔒 Locked</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

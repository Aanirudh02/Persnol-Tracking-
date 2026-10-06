<x-app-layout title="{{ $type === 'debt' ? 'Debts' : 'Credits' }}">
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $type === 'debt' ? 'Debts' : 'Credits' }}</h1>
                <p class="text-sm text-slate-500">
                    {{ $type === 'debt' ? 'Money others owe you.' : 'Money you owe others.' }}
                    Open total: <strong>₹{{ number_format($openTotal, 2) }}</strong>
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('credits.index', ['type' => 'credit']) }}" class="rounded-xl px-3 py-2 text-sm font-semibold {{ $type === 'credit' ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white' }}">Credits</a>
                <a href="{{ route('credits.index', ['type' => 'debt']) }}" class="rounded-xl px-3 py-2 text-sm font-semibold {{ $type === 'debt' ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white' }}">Debts</a>
            </div>
        </div>

        <form action="{{ route('credits.store') }}" method="POST" class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm sm:grid-cols-2">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">

            @if ($errors->any())
                <div class="sm:col-span-2 rounded-2xl border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-800 space-y-1">
                    <p class="font-bold flex items-center gap-1.5 text-sm">
                        <svg class="w-4 h-4 text-rose-600 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Please check the following:
                    </p>
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                @if($type === 'credit')
                    Credit means <strong>you owe the friend</strong>.
                @else
                    Debt means <strong>the friend owes you</strong>.
                @endif
            </div>
            <div class="sm:col-span-2">
                <label class="font-semibold text-slate-700">Friend</label>
                <select name="friend_id" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                    @foreach($friends as $friend)
                        <option value="{{ $friend->id }}">{{ $friend->name }} ({{ $friend->role }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="font-semibold text-slate-700">Amount</label>
                <input type="number" step="0.01" name="amount" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
            </div>
            <div>
                <label class="font-semibold text-slate-700">Initial paid</label>
                <input type="number" step="0.01" name="amount_paid" value="0" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
            </div>
            <div>
                <label class="font-semibold text-slate-700">Date</label>
                <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
            </div>
            <div>
                <label class="font-semibold text-slate-700">Payment method</label>
                <select name="payment_method" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method }}">{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="font-semibold text-slate-700">Due date</label>
                <input type="date" name="due_date" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
            </div>
            <div>
                <label class="font-semibold text-slate-700">Status</label>
                <select name="status" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                    @foreach($statuses as $status)
                        <option value="{{ $status['name'] }}">{{ $status['icon'] ?: ucfirst(str_replace('_', ' ', $status['name'])) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="font-semibold text-slate-700">Description</label>
                <input type="text" name="description" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5" placeholder="Lunch, advance, ticket share...">
            </div>
            <div class="sm:col-span-2">
                <label class="font-semibold text-slate-700">Notes</label>
                <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5"></textarea>
            </div>
            <div class="sm:col-span-2 flex items-center gap-2 pt-1">
                <input type="checkbox" name="link_as_expense" id="link_as_expense" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                <label for="link_as_expense" class="text-xs font-semibold text-slate-700 cursor-pointer">
                    {{ $type === 'debt' ? 'Also file as Normal Expense (cash lent/given to friend)' : 'Also file as Normal Expense (repayment paid to friend)' }}
                </label>
            </div>
            <button class="sm:col-span-2 rounded-xl bg-slate-900 py-2.5 font-semibold text-white">Save {{ $type }}</button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <div class="space-y-3 p-4 text-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900">{{ $item->friend?->name ?? 'Friend' }}</p>
                                <p class="text-slate-500 flex items-center gap-1.5 flex-wrap">
                                    <span>{{ $item->description ?: 'No description' }} · {{ \Carbon\Carbon::parse($item->date)->format('d M Y') }}</span>
                                    @if($item->payment_method)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-700 border border-slate-200">
                                            <span>{{ strtolower($item->payment_method) === 'cash' ? '💵' : (strtolower($item->payment_method) === 'upi' ? '📱' : '💳') }}</span>
                                            <span>{{ $item->payment_method }}</span>
                                        </span>
                                    @endif
                                </p>
                                
                                <!-- Settled / Paid Status Badges -->
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    @if($item->is_settled_discounted)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 px-3 py-1 text-xs font-black text-white shadow-sm ring-1 ring-amber-400/40">
                                            @if((float) $item->amount_paid <= 0)
                                                <span>🕊️ Settled (No-Pay)</span> · <span>₹{{ number_format($item->settled_discount_amount, 2) }} waived</span>
                                            @else
                                                <span>🤝 Settled</span> · <span>₹{{ number_format($item->settled_discount_amount, 2) }} forgiven</span>
                                            @endif
                                        </span>
                                    @elseif($item->status === 'fully_paid')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-emerald-500 to-teal-600 px-3 py-1 text-xs font-black text-white shadow-sm ring-1 ring-emerald-400/40">
                                            <span>✅ Fully Paid</span>
                                        </span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 border border-slate-200">{{ $item->statusLabel() }}</span>
                                    @endif
                                </div>

                                <!-- Linked Normal Expense Status / Warning -->
                                @if($item->linked_expense_id)
                                    @if($item->linkedExpense)
                                        <div class="mt-1.5 inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800">
                                            <span>✅ Recorded as Normal Expense:</span>
                                            <a href="{{ route('expenses.index', ['search' => $item->linkedExpense->description]) }}" class="underline hover:text-emerald-950">{{ $item->linkedExpense->description }} · ₹{{ number_format($item->linkedExpense->amount, 2) }} ({{ $item->linkedExpense->payment_method ?: 'Cash' }}) ({{ \Carbon\Carbon::parse($item->linkedExpense->date)->format('d M Y') }})</a>
                                        </div>
                                    @else
                                        <div class="mt-1.5 inline-flex items-center gap-1.5 rounded-xl border border-rose-300 bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-800">
                                            <span>⚠️ Warning:</span> Linked Normal Expense #{{ $item->linked_expense_id }} was deleted!
                                        </div>
                                    @endif
                                @endif

                                <!-- Linked Personal Expense Status / Warning -->
                                @if($item->linked_personal_expense_id)
                                    @if($item->linkedPersonalExpense)
                                        <div class="mt-1.5 inline-flex items-center gap-1.5 rounded-xl border border-purple-200 bg-purple-50 px-2.5 py-1 text-xs font-bold text-purple-800">
                                            <span>💜 Recorded as Personal Expense:</span>
                                            <a href="{{ route('personal-expenses.index') }}" class="underline hover:text-purple-950">{{ $item->linkedPersonalExpense->description }} · ₹{{ number_format($item->linkedPersonalExpense->amount, 2) }} ({{ $item->linkedPersonalExpense->payment_method ?: 'Cash' }}) ({{ \Carbon\Carbon::parse($item->linkedPersonalExpense->date)->format('d M Y') }})</a>
                                        </div>
                                    @else
                                        <div class="mt-1.5 inline-flex items-center gap-1.5 rounded-xl border border-rose-300 bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-800">
                                            <span>⚠️ Warning:</span> Linked Personal Expense #{{ $item->linked_personal_expense_id }} was deleted!
                                        </div>
                                    @endif
                                @endif
                            </div>
                            <div class="grid min-w-[280px] grid-cols-3 gap-2 text-center">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-2 py-2">
                                    <p class="text-[10px] font-semibold uppercase text-slate-400">Original</p>
                                    <p class="text-sm font-bold text-slate-800">₹{{ number_format($item->amount, 2) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-2 py-2">
                                    <p class="text-[10px] font-semibold uppercase text-slate-400">Paid</p>
                                    <p class="text-sm font-bold text-slate-800">₹{{ number_format($item->amount_paid, 2) }}</p>
                                </div>
                                <div class="rounded-xl border-2 {{ $item->remaining() > 0 ? 'border-amber-400 bg-amber-50' : 'border-slate-200 bg-slate-50' }} px-2 py-2">
                                    <p class="text-[10px] font-semibold uppercase {{ $item->remaining() > 0 ? 'text-amber-700' : 'text-slate-400' }}">Left</p>
                                    <p class="text-base font-bold {{ $item->remaining() > 0 ? 'text-amber-800' : 'text-slate-500' }}">₹{{ number_format($item->remaining(), 2) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            <a href="{{ route('credits.edit', $item) }}" class="font-semibold text-indigo-600 hover:underline">Edit</a>

                            {{-- Normal Expense Link / Button for BOTH Credit & Debt --}}
                            @if($item->linked_expense_id && $item->linkedExpense)
                                <span class="font-semibold text-emerald-700 inline-flex items-center gap-1">
                                    <span>✅</span> Recorded as Normal Expense
                                </span>
                            @else
                                <button type="button" onclick="document.getElementById('record-expense-{{ $item->id }}').classList.toggle('hidden'); document.getElementById('record-personal-expense-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('close-debt-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('close-credit-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('record-income-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('settle-discount-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('settle-nopay-{{ $item->id }}')?.classList.add('hidden');" class="font-semibold text-emerald-700 hover:underline flex items-center gap-1">
                                    <span>📗</span> File as Normal Expense
                                </button>
                            @endif

                            {{-- Credit Only: Personal Expense Option --}}
                            @if($item->type === 'credit')
                                @if($item->linked_personal_expense_id && $item->linkedPersonalExpense)
                                    <span class="font-semibold text-purple-700 inline-flex items-center gap-1">
                                        <span>💜</span> Recorded as Personal Expense
                                    </span>
                                @else
                                    <button type="button" onclick="document.getElementById('record-personal-expense-{{ $item->id }}').classList.toggle('hidden'); document.getElementById('record-expense-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('close-credit-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('settle-discount-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('settle-nopay-{{ $item->id }}')?.classList.add('hidden');" class="font-semibold text-purple-700 hover:underline flex items-center gap-1">
                                        <span>💜</span> File as Personal Expense
                                    </button>
                                @endif

                                @if($item->remaining() > 0)
                                    <button type="button" onclick="document.getElementById('close-credit-{{ $item->id }}').classList.toggle('hidden'); document.getElementById('record-expense-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('record-personal-expense-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('settle-discount-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('settle-nopay-{{ $item->id }}')?.classList.add('hidden');" class="font-semibold text-indigo-700 hover:underline flex items-center gap-1">
                                        <span>🔒</span> Close Credit (as Expense)
                                    </button>
                                @endif
                            @endif

                            {{-- Debt Only: Close as Income & Partial Income --}}
                            @if($item->type === 'debt' && $item->remaining() > 0)
                                <button type="button" onclick="document.getElementById('close-debt-{{ $item->id }}').classList.toggle('hidden'); document.getElementById('record-expense-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('record-income-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('settle-discount-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('settle-nopay-{{ $item->id }}')?.classList.add('hidden');" class="font-semibold text-emerald-700 hover:underline flex items-center gap-1">
                                    <span>🔒</span> Close Debt (as Income)
                                </button>
                                <button type="button" onclick="document.getElementById('record-income-{{ $item->id }}').classList.toggle('hidden'); document.getElementById('close-debt-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('record-expense-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('settle-discount-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('settle-nopay-{{ $item->id }}')?.classList.add('hidden');" class="font-semibold text-teal-700 hover:underline flex items-center gap-1">
                                    <span>💰</span> Partial Income
                                </button>
                            @endif

                            @if($item->remaining() > 0)
                                <button type="button" onclick="document.getElementById('settle-nopay-{{ $item->id }}').classList.toggle('hidden'); document.getElementById('settle-discount-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('record-expense-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('close-debt-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('close-credit-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('record-personal-expense-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('record-income-{{ $item->id }}')?.classList.add('hidden');" class="font-semibold text-emerald-700 hover:underline flex items-center gap-1">
                                    <span>🕊️</span> No-Pay Settle (₹0)
                                </button>
                                <button type="button" onclick="document.getElementById('settle-discount-{{ $item->id }}').classList.toggle('hidden'); document.getElementById('settle-nopay-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('record-expense-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('close-debt-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('close-credit-{{ $item->id }}')?.classList.add('hidden'); document.getElementById('record-personal-expense-{{ $item->id }}')?.classList.add('hidden');" class="font-semibold text-amber-600 hover:underline flex items-center gap-1">
                                    <span>🤝</span> Settle with Discount / Forgiven
                                </button>
                            @endif

                            {{-- Delete with prompt if linked to normal expense --}}
                            @if($item->linked_expense_id && $item->linkedExpense)
                                <button type="button" onclick="openDeleteCreditModal({{ $item->id }}, '{{ $item->type }}', '{{ addslashes($item->linkedExpense->description) }}', '{{ number_format($item->linkedExpense->amount, 2) }}')" class="font-semibold text-rose-600 hover:underline">
                                    Delete
                                </button>
                            @else
                                <form action="{{ route('credits.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete this {{ $item->type }} record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-semibold text-rose-600 hover:underline">Delete</button>
                                </form>
                            @endif
                        </div>

                        <!-- Close Debt Form (Friend Paid Back -> Adds Income) -->
                        @if($item->type === 'debt' && $item->remaining() > 0)
                            <form id="close-debt-{{ $item->id }}" action="{{ route('credits.close', $item) }}" method="POST" class="hidden rounded-2xl border border-emerald-300 bg-emerald-50/70 p-3 text-xs space-y-2">
                                @csrf
                                <div class="font-bold text-emerald-900 flex items-center justify-between">
                                    <span>🔒 Close Debt & Record as Income</span>
                                    <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-lg font-black">Remaining: ₹{{ number_format($item->remaining(), 2) }}</span>
                                </div>
                                <p class="text-slate-600">Marks this debt as <strong>Fully Paid</strong> and automatically records <strong>₹{{ number_format($item->remaining(), 2) }}</strong> as an Income entry from {{ $item->friend?->name ?? 'Friend' }}.</p>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                    <div>
                                        <label class="font-semibold text-slate-700 block mb-1">Date Received</label>
                                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold">
                                    </div>
                                    <div>
                                        <label class="font-semibold text-slate-700 block mb-1">Payment Method</label>
                                        <select name="payment_method" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold">
                                            @foreach($paymentMethods as $method)
                                                <option value="{{ $method }}" @selected(($item->payment_method ?: 'Cash') === $method)>{{ $method }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="flex items-end">
                                        <button type="submit" class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-500 py-2 font-bold text-white shadow-sm transition">
                                            Confirm Close & Add Income
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        <!-- Close Credit Form (I Paid Back -> Adds Normal Expense) -->
                        @if($item->type === 'credit' && $item->remaining() > 0)
                            <form id="close-credit-{{ $item->id }}" action="{{ route('credits.close', $item) }}" method="POST" class="hidden rounded-2xl border border-indigo-300 bg-indigo-50/70 p-3 text-xs space-y-2">
                                @csrf
                                <div class="font-bold text-indigo-900 flex items-center justify-between">
                                    <span>🔒 Close Credit & Record as Normal Expense</span>
                                    <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-lg font-black">Remaining: ₹{{ number_format($item->remaining(), 2) }}</span>
                                </div>
                                <p class="text-slate-600">Marks this credit as <strong>Fully Paid</strong> and automatically records <strong>₹{{ number_format($item->remaining(), 2) }}</strong> as a Normal Expense entry to {{ $item->friend?->name ?? 'Friend' }}.</p>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                    <div>
                                        <label class="font-semibold text-slate-700 block mb-1">Date Repaid</label>
                                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold">
                                    </div>
                                    <div>
                                        <label class="font-semibold text-slate-700 block mb-1">Payment Method</label>
                                        <select name="payment_method" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold">
                                            @foreach($paymentMethods as $method)
                                                <option value="{{ $method }}" @selected(($item->payment_method ?: 'Cash') === $method)>{{ $method }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="flex items-end">
                                        <button type="submit" class="w-full rounded-xl bg-indigo-600 hover:bg-indigo-500 py-2 font-bold text-white shadow-sm transition">
                                            Confirm Close & Add Expense
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        <!-- Record as Normal Expense Inline Form (Credit Repayment OR Debt Lent) -->
                        @php
                            $isDebt = $item->type === 'debt';
                            $defaultAmt = $isDebt ? (float) $item->amount : ((float) $item->amount_paid > 0 ? (float) $item->amount_paid : (float) $item->remaining());
                            $rawDate = $item->payments->last()?->paid_on ?? $item->date ?? now();
                            $suggestedDate = \Carbon\Carbon::parse($rawDate)->toDateString();
                            $suggestedRepay = (float) $item->amount_paid > 0 ? (float) $item->amount_paid : (float) $item->remaining();
                        @endphp
                        <form id="record-expense-{{ $item->id }}" action="{{ route('credits.record-as-expense', $item) }}" method="POST" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50/60 p-3 text-xs space-y-2">
                            @csrf
                            <div class="font-bold text-emerald-900">
                                {{ $isDebt ? 'Record Money Lent as Normal Expense' : 'Record Credit Repayment as Normal Expense' }}
                            </div>
                            <p class="text-slate-600">
                                {{ $isDebt ? 'Files the cash given/lent under normal expenses. Amount defaults to ₹'.number_format($defaultAmt, 2).'.' : 'Files this repayment under standard / college expenses. Pre-filled with paid amount (₹'.number_format($defaultAmt, 2).').' }}
                            </p>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-4">
                                <div>
                                    <label class="font-semibold text-slate-700 block mb-1">Amount (₹)</label>
                                    <input type="number" step="0.01" name="amount" value="{{ $defaultAmt }}" max="{{ $item->amount }}" min="0.01" required placeholder="Amount" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-emerald-800">
                                </div>
                                <div>
                                    <label class="font-semibold text-slate-700 block mb-1">Date</label>
                                    <input type="date" name="date" value="{{ $suggestedDate }}" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold">
                                </div>
                                <div>
                                    <label class="font-semibold text-slate-700 block mb-1">Payment Method</label>
                                    <select name="payment_method" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                        @foreach($paymentMethods as $method)
                                            <option value="{{ $method }}" @selected(($item->payment_method ?: ($item->payments->last()?->payment_method ?? 'Cash')) === $method)>{{ $method }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <button class="w-full rounded-xl bg-emerald-700 px-3 py-2 font-bold text-white hover:bg-emerald-800">Submit Normal Expense</button>
                                </div>
                            </div>
                        </form>

                        @if($item->type === 'credit')
                            <!-- Record as Personal Expense Inline Form (Credit Repayment Only) -->
                            <form id="record-personal-expense-{{ $item->id }}" action="{{ route('credits.record-as-personal-expense', $item) }}" method="POST" class="hidden rounded-2xl border border-purple-200 bg-purple-50/60 p-3 text-xs space-y-2">
                                @csrf
                                <div class="font-bold text-purple-900">Record Credit Repayment as Personal Expense</div>
                                <p class="text-slate-600">Files this repayment under personal & family domain. Pre-filled with paid amount (₹{{ number_format($suggestedRepay, 2) }}) and payment date ({{ \Carbon\Carbon::parse($suggestedDate)->format('d M Y') }}).</p>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
                                    <div>
                                        <label class="font-semibold text-slate-700 block mb-1">Repay Amount (₹)</label>
                                        <input type="number" step="0.01" name="amount" value="{{ $suggestedRepay }}" max="{{ $item->amount }}" min="0.01" required placeholder="Repay Amount" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-purple-800">
                                    </div>
                                    <div>
                                        <label class="font-semibold text-slate-700 block mb-1">Repayment Date</label>
                                        <input type="date" name="date" value="{{ $suggestedDate }}" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold">
                                    </div>
                                    <div>
                                        <label class="font-semibold text-slate-700 block mb-1">Category</label>
                                        <select name="category_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                            @foreach($personalCategories as $pcat)
                                                <option value="{{ $pcat->id }}">{{ $pcat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="font-semibold text-slate-700 block mb-1">Classification</label>
                                        <select name="classification" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                            <option value="necessary">Necessary</option>
                                            <option value="discretionary">Discretionary</option>
                                            <option value="luxury">Luxury</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="font-semibold text-slate-700 block mb-1">Payment Method</label>
                                        <select name="payment_method" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                            @foreach($paymentMethods as $method)
                                                <option value="{{ $method }}" @selected(($item->payments->last()?->payment_method ?? $item->payment_method) === $method)>{{ $method }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="flex items-end">
                                        <button class="w-full rounded-xl bg-purple-700 px-3 py-2 font-bold text-white hover:bg-purple-800">Submit Personal</button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        <!-- Record as Income Inline Form (Debt) -->
                        @if($item->type === 'debt' && $item->remaining() > 0)
                            <form id="record-income-{{ $item->id }}" action="{{ route('credits.record-as-income', $item) }}" method="POST" class="hidden rounded-2xl border border-teal-200 bg-teal-50/60 p-3 text-xs space-y-2">
                                @csrf
                                <div class="font-bold text-teal-900">Record Recovered Debt as Income Entry</div>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-4">
                                    <input type="number" step="0.01" name="amount" value="{{ $item->remaining() }}" max="{{ $item->remaining() }}" min="0.01" required placeholder="Collected Amount" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold">
                                    <input type="date" name="date" value="{{ $suggestedDate }}" required class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                    <select name="payment_method" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                        @foreach($paymentMethods as $method)
                                            <option value="{{ $method }}">{{ $method }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-xl bg-teal-700 px-3 py-1.5 font-bold text-white hover:bg-teal-800">Submit Income</button>
                                </div>
                            </form>
                        @endif

                        <!-- Settle with Discount Inline Form -->
                        @if($item->remaining() > 0)
                            <form id="settle-discount-{{ $item->id }}" action="{{ route('credits.settle-discounted', $item) }}" method="POST" class="hidden rounded-2xl border border-amber-200 bg-amber-50/60 p-3 text-xs space-y-2">
                                @csrf
                                <div class="font-bold text-amber-900">Settle with Discount (Forgive remaining portion)</div>
                                <p class="text-slate-600">Remaining ₹{{ number_format($item->remaining(), 2) }}. Enter how much was actually paid/received and how much is forgiven/discounted.</p>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-4">
                                    <div>
                                        <label class="font-semibold text-slate-700 block">Paid Amount (₹)</label>
                                        <input type="number" step="0.01" min="0" name="settled_amount" value="0.00" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold">
                                    </div>
                                    <div>
                                        <label class="font-semibold text-slate-700 block">Forgiven / Discount (₹)</label>
                                        <input type="number" step="0.01" min="0.01" name="discount_amount" value="{{ number_format($item->remaining(), 2, '.', '') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-amber-700">
                                    </div>
                                    <div>
                                        <label class="font-semibold text-slate-700 block">Settled On Date</label>
                                        <input type="date" name="paid_on" value="{{ $suggestedDate }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs">
                                    </div>
                                    <div class="flex items-end">
                                        <button class="w-full rounded-xl bg-amber-600 px-3 py-2 font-bold text-white hover:bg-amber-700">Mark Settled</button>
                                    </div>
                                </div>
                            </form>

                            <!-- No-Pay Settle (₹0 Close / Waive Off) Inline Form -->
                            <form id="settle-nopay-{{ $item->id }}" action="{{ route('credits.settle-no-pay', $item) }}" method="POST" class="hidden rounded-2xl border border-emerald-300 bg-emerald-50/70 p-3 text-xs space-y-2">
                                @csrf
                                <div class="font-bold text-emerald-900 flex items-center justify-between">
                                    <span>🕊️ No-Pay Settle (₹0 Payment / Waive Off in Full)</span>
                                    <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-lg font-black">Waiving: ₹{{ number_format($item->remaining(), 2) }}</span>
                                </div>
                                <p class="text-slate-600">
                                    @if($item->type === 'debt')
                                        Forgive remaining <strong>₹{{ number_format($item->remaining(), 2) }}</strong> owed by {{ $item->friend?->name ?? 'Friend' }} without collecting money. Daily cash & GPay registers will NOT be affected.
                                    @else
                                        Waive remaining <strong>₹{{ number_format($item->remaining(), 2) }}</strong> you owe to {{ $item->friend?->name ?? 'Friend' }} with ₹0 payment. Daily cash & GPay registers will NOT be affected.
                                    @endif
                                </p>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                    <div>
                                        <label class="font-semibold text-slate-700 block mb-1">Reason / Note</label>
                                        <input type="text" name="reason" value="Waived off / Settled with ₹0" placeholder="e.g. Waived off by agreement" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold">
                                    </div>
                                    <div>
                                        <label class="font-semibold text-slate-700 block mb-1">Date</label>
                                        <input type="date" name="settled_on" value="{{ $suggestedDate }}" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold">
                                    </div>
                                    <div class="flex items-end">
                                        <button type="submit" class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-500 py-2 font-bold text-white shadow-sm transition">
                                            Confirm No-Pay Settle (₹0)
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        @if($item->payments->isNotEmpty() || $item->remaining() > 0)
                            @php
                                $isSettledOrPaid = $item->is_settled_discounted || $item->status === 'fully_paid';
                            @endphp
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Payments</h3>
                                    @if($isSettledOrPaid && $item->payments->isNotEmpty())
                                        <button type="button" onclick="document.getElementById('payments-editor-{{ $item->id }}').classList.toggle('hidden'); document.getElementById('payments-readonly-{{ $item->id }}').classList.toggle('hidden');" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 cursor-pointer flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-0.5 border border-indigo-200">
                                            <span>🔒 Locked (Settled)</span> · <span class="underline">Click to Edit Payments</span>
                                        </button>
                                    @endif
                                </div>

                                <!-- Read-Only Payments View for Settled/Paid Records -->
                                @if($isSettledOrPaid && $item->payments->isNotEmpty())
                                    <div id="payments-readonly-{{ $item->id }}" class="space-y-1.5">
                                        @foreach($item->payments as $payment)
                                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-900">₹{{ number_format($payment->amount, 2) }}</span>
                                                    <span class="text-slate-400">·</span>
                                                    <span class="text-slate-600">{{ \Carbon\Carbon::parse($payment->paid_on)->format('d M Y') }}</span>
                                                    <span class="text-slate-400">·</span>
                                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 font-semibold text-slate-600">{{ $payment->payment_method ?? 'Payment' }}</span>
                                                </div>
                                                @if($payment->notes)
                                                    <span class="text-slate-400 italic text-[11px] truncate max-w-[200px]">{{ $payment->notes }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Editable Payments View (Default for active, toggleable for settled) -->
                                <div id="payments-editor-{{ $item->id }}" class="{{ ($isSettledOrPaid && $item->payments->isNotEmpty()) ? 'hidden' : '' }} space-y-2">
                                    @foreach($item->payments as $payment)
                                        <form action="{{ route('credits.payments.update', [$item, $payment]) }}" method="POST" class="grid grid-cols-1 gap-2 rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-[1fr_1fr_1fr_auto_auto]">
                                            @csrf
                                            @method('PUT')
                                            <input type="number" step="0.01" name="amount" value="{{ $payment->amount }}" class="rounded-lg border border-slate-300 px-2 py-2 text-xs font-bold">
                                            <input type="date" name="paid_on" value="{{ \Carbon\Carbon::parse($payment->paid_on)->toDateString() }}" class="rounded-lg border border-slate-300 px-2 py-2 text-xs">
                                            <select name="payment_method" class="rounded-lg border border-slate-300 px-2 py-2 text-xs">
                                                @foreach($paymentMethods as $method)
                                                    <option value="{{ $method }}" @selected($payment->payment_method === $method)>{{ $method }}</option>
                                                @endforeach
                                            </select>
                                            <button class="rounded-lg bg-slate-900 px-3 py-2 font-semibold text-white">Update</button>
                                            <button form="delete-payment-{{ $payment->id }}" type="submit" class="hidden"></button>
                                        </form>
                                        <form id="delete-payment-{{ $payment->id }}" action="{{ route('credits.payments.destroy', [$item, $payment]) }}" method="POST" class="-mt-1 text-right">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">Delete payment</button>
                                        </form>
                                    @endforeach

                                    <!-- Record Additional Payment -->
                                    <div class="mt-2 pt-2 border-t border-slate-200">
                                        <div class="text-[11px] font-bold text-slate-500 mb-1.5 uppercase">Record New Payment</div>
                                        <form action="{{ route('credits.payments', $item) }}" method="POST" class="grid grid-cols-1 gap-2 sm:grid-cols-4">
                                            @csrf
                                            <input type="number" step="0.01" name="amount" required placeholder="Pay amount" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold">
                                            <input type="date" name="paid_on" value="{{ date('Y-m-d') }}" required class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs">
                                            <select name="payment_method" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs">
                                                @foreach($paymentMethods as $method)
                                                    <option value="{{ $method }}">{{ $method }}</option>
                                                @endforeach
                                            </select>
                                            <button class="rounded-xl bg-slate-900 px-2 py-2 font-semibold text-white">Record payment</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="p-6 text-sm text-slate-400">No {{ $type }} records yet.</p>
                @endforelse
            </div>
            <div class="p-4">{{ $items->links() }}</div>
        </div>
    </div>

    <!-- Modal for deleting Credit/Debt linked to Normal Expense -->
    <div id="delete-linked-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs hidden">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-slate-200">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span>⚠️</span> Delete <span id="del-modal-type">Credit</span>
            </h3>
            <p class="mt-2 text-xs text-slate-600 leading-relaxed">
                This item is linked to a Normal Expense:
                <span id="del-modal-exp-desc" class="font-bold text-slate-900 block mt-1"></span>
                <span id="del-modal-exp-amt" class="text-emerald-600 font-bold block mt-0.5"></span>
            </p>
            <p class="mt-3 text-xs text-slate-700 font-medium">
                Would you like to delete the linked Normal Expense as well, or delete the <span id="del-modal-type-2">credit</span> alone?
            </p>
            <div class="mt-5 flex flex-col gap-2">
                <form id="del-form-alone" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="delete_linked_expense" value="0">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl border border-slate-300 bg-slate-50 hover:bg-slate-100 text-xs font-bold text-slate-800 transition cursor-pointer">
                        Delete <span id="del-btn-type">Credit</span> Alone (Keep Expense)
                    </button>
                </form>
                <form id="del-form-both" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="delete_linked_expense" value="1">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-xs font-bold text-white shadow-md shadow-rose-600/20 transition cursor-pointer">
                        Delete Both (<span id="del-btn-type-2">Credit</span> & Linked Normal Expense)
                    </button>
                </form>
                <button type="button" onclick="document.getElementById('delete-linked-modal').classList.add('hidden')" class="w-full py-2 text-xs text-slate-400 hover:text-slate-600 font-semibold cursor-pointer">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        function openDeleteCreditModal(id, type, expDesc, expAmt) {
            const capitalized = type.charAt(0).toUpperCase() + type.slice(1);
            document.getElementById('del-modal-type').textContent = capitalized;
            document.getElementById('del-modal-type-2').textContent = type;
            document.getElementById('del-btn-type').textContent = capitalized;
            document.getElementById('del-btn-type-2').textContent = capitalized;
            document.getElementById('del-modal-exp-desc').textContent = '“' + expDesc + '”';
            document.getElementById('del-modal-exp-amt').textContent = 'Amount: ₹' + expAmt;
            
            const destroyUrl = "{{ url('/finance/credits') }}/" + id;
            document.getElementById('del-form-alone').action = destroyUrl;
            document.getElementById('del-form-both').action = destroyUrl;
            
            document.getElementById('delete-linked-modal').classList.remove('hidden');
        }
    </script>
</x-app-layout>

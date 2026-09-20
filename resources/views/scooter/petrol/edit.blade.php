<x-app-layout title="Edit Petrol Record">
    <div class="max-w-2xl mx-auto space-y-6">
        <!-- Back link -->
        <div class="flex items-center justify-between">
            <a href="{{ route('petrol.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Petrol Tracker
            </a>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-3xl border border-sky-100 shadow-sm p-6 sm:p-8">
            <div class="flex items-center gap-3 pb-5 border-b border-slate-100 mb-6">
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold">
                    ⛽
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Edit Petrol Fill</h1>
                    <p class="text-xs text-slate-500">Update fuel purchase details, litres, and odometer reading.</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('petrol.update', $petrol) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="vehicle_id" class="block text-xs font-semibold text-slate-700 mb-1.5">Vehicle *</label>
                    <select
                        name="vehicle_id"
                        id="vehicle_id"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                    >
                        @foreach($vehicles as $veh)
                            <option value="{{ $veh->id }}" {{ old('vehicle_id', $petrol->vehicle_id) == $veh->id ? 'selected' : '' }}>
                                {{ $veh->name }} {{ $veh->is_default ? '(Default)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="date" class="block text-xs font-semibold text-slate-700 mb-1.5">Date *</label>
                        <input
                            type="date"
                            name="date"
                            id="date"
                            value="{{ old('date', $petrol->date ? \Carbon\Carbon::parse($petrol->date)->format('Y-m-d') : '') }}"
                            required
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        />
                    </div>
                    <div>
                        <label for="time" class="block text-xs font-semibold text-slate-700 mb-1.5">Time</label>
                        <input
                            type="time"
                            name="time"
                            id="time"
                            value="{{ old('time', $petrol->time) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="amount" class="block text-xs font-semibold text-slate-700 mb-1.5">Total Amount (₹) *</label>
                        <input
                            type="number"
                            step="0.01"
                            name="amount"
                            id="amount"
                            value="{{ old('amount', $petrol->amount) }}"
                            required
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        />
                    </div>
                    <div>
                        <label for="litres" class="block text-xs font-semibold text-slate-700 mb-1.5">Fuel Volume (Litres) *</label>
                        <input
                            type="number"
                            step="0.01"
                            name="litres"
                            id="litres"
                            value="{{ old('litres', $petrol->litres) }}"
                            required
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="odometer" class="block text-xs font-semibold text-slate-700 mb-1.5">Odometer (km)</label>
                        <input
                            type="number"
                            name="odometer"
                            id="odometer"
                            value="{{ old('odometer', $petrol->odometer) }}"
                            placeholder="e.g. 14500"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        />
                    </div>
                    <div>
                        <label for="payment_method" class="block text-xs font-semibold text-slate-700 mb-1.5">Payment Method *</label>
                        <select
                            name="payment_method"
                            id="payment_method"
                            required
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        >
                            <option value="UPI" {{ old('payment_method', $petrol->payment_method) === 'UPI' ? 'selected' : '' }}>UPI (GPay / PhonePe / Paytm)</option>
                            <option value="Cash" {{ old('payment_method', $petrol->payment_method) === 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="Card" {{ old('payment_method', $petrol->payment_method) === 'Card' ? 'selected' : '' }}>Debit / Credit Card</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="petrol_station" class="block text-xs font-semibold text-slate-700 mb-1.5">Petrol Pump / Station Name</label>
                    <input
                        type="text"
                        name="petrol_station"
                        id="petrol_station"
                        value="{{ old('petrol_station', $petrol->petrol_station) }}"
                        placeholder="e.g., Indian Oil, Bharat Petroleum, Shell..."
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                    />
                </div>

                <div>
                    <label for="notes" class="block text-xs font-semibold text-slate-700 mb-1.5">Notes (Optional)</label>
                    <textarea
                        name="notes"
                        id="notes"
                        rows="2"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                    >{{ old('notes', $petrol->notes) }}</textarea>
                </div>

                @php
                    $linkedExpense = $petrol->expense;
                    $isLinkedActive = $linkedExpense && !$linkedExpense->trashed();
                    $isLinkedDeleted = ($petrol->expense_id && !$linkedExpense) || ($linkedExpense && $linkedExpense->trashed());
                @endphp

                @if(!$petrol->expense_id || !$linkedExpense)
                    <label class="flex items-start gap-2 rounded-xl border border-teal-200 bg-teal-50 dark:bg-teal-950/40 p-3 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" name="add_as_expense" value="1" class="mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span><strong>Add this record as an expense</strong><span class="block text-[11px] text-slate-500">Creates an active normal expense using updated petrol details.</span></span>
                    </label>
                @elseif($isLinkedDeleted)
                    <div class="rounded-xl border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/40 p-3.5 text-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                                <span>⚠️</span> The linked expense was deleted or archived.
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-200/80 dark:bg-amber-900 text-amber-900 dark:text-amber-200">
                                Deleted
                            </span>
                        </div>
                        <p class="text-[11px] text-amber-700 dark:text-amber-400 leading-relaxed">
                            This petrol fill is currently not counted in your active expenses. Check the option below to re-log it as an active expense.
                        </p>
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-amber-200/60 dark:border-amber-900/60">
                            <label class="inline-flex items-center gap-2 font-bold text-teal-700 dark:text-teal-400 cursor-pointer">
                                <input type="checkbox" name="add_as_expense" value="1" checked class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                <span>+ Re-log as Active Expense on save</span>
                            </label>
                            <a href="{{ route('expenses.show', $petrol->expense_id) }}" target="_blank" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                View Deleted Expense &rarr;
                            </a>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-emerald-200 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-950/40 p-3 text-xs text-emerald-700 dark:text-emerald-300 flex items-center justify-between">
                        <div>
                            <strong>✓ Linked to Active Expense:</strong> Updating this petrol record will automatically update that expense too.
                        </div>
                        <a href="{{ route('expenses.show', $petrol->expense_id) }}" target="_blank" class="text-[11px] font-bold text-emerald-800 dark:text-emerald-200 hover:underline shrink-0 ml-2">
                            View Expense &rarr;
                        </a>
                    </div>
                @endif

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('petrol.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold shadow-sm transition active:scale-95"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

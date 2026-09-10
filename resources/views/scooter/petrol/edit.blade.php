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

                @if(!$petrol->expense_id)
                    <label class="flex items-start gap-2 rounded-xl border border-teal-200 bg-teal-50 p-3 text-xs text-slate-700">
                        <input type="checkbox" name="add_as_expense" value="1" class="mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span><strong>Add this record as an expense</strong><span class="block text-[11px] text-slate-500">Creates an expense using the updated petrol details.</span></span>
                    </label>
                @else
                    <p class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-700">This petrol record is linked to an expense. Updating it will update that expense too.</p>
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

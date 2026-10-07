<x-app-layout title="Edit Money Received">
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <a href="{{ route('income.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Income
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 mt-2">Edit Money Received</h1>
            <p class="text-xs text-slate-500">Update incoming funds and payment source.</p>
        </div>

        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-sky-100 shadow-sm">
            @if ($errors->any())
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="edit-income-form" action="{{ route('income.update', $income) }}" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Amount (₹) *</label>
                    <input
                        type="number"
                        step="0.01"
                        name="amount"
                        required
                        value="{{ old('amount', $income->amount) }}"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-lg font-bold text-emerald-600 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                    >
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Source *</label>
                        <select
                            name="source"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        >
                            @foreach($incomeSources as $src)
                                <option value="{{ $src }}" {{ old('source', $income->source) === $src ? 'selected' : '' }}>{{ $src }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Method *</label>
                        <select
                            name="payment_method"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        >
                            @foreach($paymentMethods as $pm)
                                <option value="{{ $pm }}" {{ old('payment_method', $income->payment_method) === $pm ? 'selected' : '' }}>{{ $pm }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Date *</label>
                        <input
                            type="date"
                            name="date"
                            required
                            value="{{ old('date', $income->date ? \Carbon\Carbon::parse($income->date)->format('Y-m-d') : '') }}"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        >
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Time</label>
                        <input
                            type="time"
                            name="time"
                            value="{{ old('time', $income->time) }}"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                        >
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Category (Optional)</label>
                    <select
                        name="category_id"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                    >
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $income->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Description</label>
                    <input
                        type="text"
                        name="description"
                        value="{{ old('description', $income->description) }}"
                        placeholder="Optional description"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                    >
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Notes</label>
                    <textarea
                        name="notes"
                        rows="2"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                    >{{ old('notes', $income->notes) }}</textarea>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Tally / Allocation Option *</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <label class="relative flex flex-col p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer hover:border-emerald-500 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/40">
                            <div class="flex items-center gap-2">
                                <input type="radio" name="tally_mode" value="tally_current" {{ old('tally_mode', $income->tally_mode) === 'tally_current' ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500">
                                <span class="font-bold text-slate-900 text-xs">Tally Current</span>
                            </div>
                            <span class="text-[10px] text-slate-500 mt-1">Offset against current expense</span>
                        </label>
                        <label class="relative flex flex-col p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer hover:border-indigo-500 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/40">
                            <div class="flex items-center gap-2">
                                <input type="radio" name="tally_mode" value="tally_future" {{ old('tally_mode', $income->tally_mode) === 'tally_future' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                                <span class="font-bold text-slate-900 text-xs">Tally for Future</span>
                            </div>
                            <span class="text-[10px] text-slate-500 mt-1">Carry forward / reserve for future</span>
                        </label>
                        <label class="relative flex flex-col p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer hover:border-slate-400 transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/30">
                            <div class="flex items-center gap-2">
                                <input type="radio" name="tally_mode" value="separate" {{ old('tally_mode', $income->tally_mode ?? 'separate') === 'separate' ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500">
                                <span class="font-bold text-slate-900 text-xs">Have it Separate</span>
                            </div>
                            <span class="text-[10px] text-slate-500 mt-1">Independent standalone income</span>
                        </label>
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end gap-3">
                    <a href="{{ route('income.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl font-semibold shadow-sm transition active:scale-95">Save Changes</button>
                </div>
            </form>
            <x-linked-sync-prompt form-id="edit-income-form" :linked="$creditLinked ?? []" target-label="the linked debt" />
        </div>
    </div>
</x-app-layout>

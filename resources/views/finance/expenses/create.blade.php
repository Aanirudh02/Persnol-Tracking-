<x-app-layout title="Add Expense">
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <a href="{{ route('expenses.index') }}" class="text-sm font-semibold text-slate-600 hover:underline">&larr; Back to Expenses</a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 mt-2">Record New Expense</h1>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-sm">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Amount (₹) *</label>
                    <input type="number" step="0.01" name="amount" required value="{{ old('amount') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-lg font-bold">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Description *</label>
                    <input type="text" name="description" required value="{{ old('description') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Category</label>
                        <select name="category_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                            <optgroup label="Active">
                                @foreach($categories->where('is_archived', false) as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}{{ $cat->is_voluntary ? ' (Voluntary)' : '' }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Archived">
                                @foreach($categories->where('is_archived', true) as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                            @foreach($paymentMethods as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Date *</label>
                        <input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Time</label>
                        <input type="time" name="time" value="{{ old('time', date('H:i')) }}" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Paid by</label>
                        <select name="paid_by_type" id="paid_by_type" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl" onchange="document.getElementById('friend_paid_wrap').classList.toggle('hidden', this.value !== 'friend')">
                            <option value="me">Me</option>
                            <option value="friend">Friend / Parent</option>
                        </select>
                    </div>
                    <div id="friend_paid_wrap" class="hidden">
                        <label class="block font-semibold text-slate-700 mb-1">Who paid</label>
                        <select name="paid_by_friend_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                            @foreach($friends as $f)
                                <option value="{{ $f->id }}">{{ $f->name }} ({{ $f->role }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Split with friend (optional)</label>
                    <select name="split_with_friend_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                        <option value="">None</option>
                        @foreach($friends as $f)
                            <option value="{{ $f->id }}">{{ $f->name }} ({{ $f->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">My share</label>
                        <input type="number" step="0.01" name="split_my_share" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Friend share</label>
                        <input type="number" step="0.01" name="split_friend_share" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Under parent expense (sub-item)</label>
                    <select name="parent_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                        <option value="">None — top-level</option>
                        @foreach($parents as $p)
                            <option value="{{ $p->id }}">{{ $p->description }} · ₹{{ $p->amount }} ({{ $p->date->format('d M') }})</option>
                        @endforeach
                    </select>
                </div>
                <label class="flex items-center gap-2 font-semibold text-slate-700">
                    <input type="checkbox" name="is_voluntary" value="1" class="rounded border-slate-300">
                    Voluntary spend (separate from main totals)
                </label>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Receipt</label>
                    <input type="file" name="receipt_image" accept="image/*" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold">Save Expense</button>
            </form>
        </div>
    </div>
</x-app-layout>

<x-app-layout title="Edit Savings">
    <div class="mx-auto max-w-xl space-y-6">
        <div>
            <a href="{{ route('savings.index') }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600">&larr; Back to Savings</a>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-900">Edit Savings Record</h1>
            <p class="text-xs text-slate-500">Update amount, fund goal, or allocation details.</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @if ($errors->any())
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 space-y-1">
                    <p class="font-bold">Please fix the following errors:</p>
                    <ul class="list-disc pl-5 text-xs text-rose-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('savings.update', $saving) }}" method="POST" class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                @csrf
                @method('PUT')

                <div class="sm:col-span-2">
                    <label class="font-bold text-slate-700">Amount Saved (₹)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $saving->amount) }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-base font-black text-indigo-600">
                </div>

                <div>
                    <label class="font-bold text-slate-700">Source / How it came</label>
                    <input type="text" name="source" value="{{ old('source', $saving->source) }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs font-semibold">
                </div>

                <div>
                    <label class="font-bold text-slate-700">Goal / Fund Allocation</label>
                    <input type="text" name="goal_or_category" value="{{ old('goal_or_category', $saving->goal_or_category) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs font-semibold">
                </div>

                <div>
                    <label class="font-bold text-slate-700">Date Saved</label>
                    <input type="date" name="saved_date" value="{{ old('saved_date', $saving->saved_date->toDateString()) }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs">
                </div>

                <div>
                    <label class="font-bold text-slate-700">Status</label>
                    <select name="status" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs font-semibold">
                        <option value="active" @selected(old('status', $saving->status) === 'active')>Active</option>
                        <option value="locked" @selected(old('status', $saving->status) === 'locked')>Locked (Reserved)</option>
                        <option value="withdrawn" @selected(old('status', $saving->status) === 'withdrawn')>Withdrawn</option>
                    </select>
                </div>

                <div>
                    <label class="font-bold text-slate-700">Withdrawn Amount (₹)</label>
                    <input type="number" step="0.01" min="0" name="withdrawn_amount" value="{{ old('withdrawn_amount', $saving->withdrawn_amount) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs font-semibold">
                </div>

                <div class="sm:col-span-2">
                    <label class="font-bold text-slate-700">Notes</label>
                    <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs">{{ old('notes', $saving->notes) }}</textarea>
                </div>

                <div class="sm:col-span-2 flex items-center justify-end gap-2 pt-2">
                    <a href="{{ route('savings.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-2.5 font-bold text-white shadow-md hover:bg-indigo-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

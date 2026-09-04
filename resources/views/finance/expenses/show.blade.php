<x-app-layout title="Expense">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a href="{{ route('expenses.index') }}" class="text-sm font-semibold text-slate-600 hover:underline">&larr; Expenses</a>
            <h1 class="text-2xl font-bold text-slate-900 mt-2">{{ $expense->description }}</h1>
            <p class="text-sm text-slate-500">₹{{ number_format($expense->amount, 2) }} · {{ $expense->category?->name ?? 'Uncategorized' }} · {{ $expense->date->format('d M Y') }}</p>
            @if($expense->is_voluntary)<span class="inline-block mt-2 text-xs font-bold px-2 py-1 rounded-full bg-pink-50 text-pink-700">Voluntary</span>@endif
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm text-sm space-y-2">
            <p>Paid by: <strong>{{ $expense->paid_by }}</strong> · {{ $expense->payment_method }}</p>
            <p>Cash total (parent): <strong>₹{{ number_format($expense->amount, 2) }}</strong></p>
            <p>Explained by sub-items / food: <strong>₹{{ number_format($expense->subItemsExplainedTotal(), 2) }}</strong></p>
            @if(abs($expense->subItemsExplainedTotal() - (float) $expense->amount) > 0.01 && $expense->subItemsExplainedTotal() > 0)
                <p class="text-amber-700 text-xs">Breakdown does not match parent total yet — edit amounts or add remaining items.</p>
            @endif
            @if($expense->notes)<p class="text-slate-500">{{ $expense->notes }}</p>@endif
            <a href="{{ route('expenses.edit', $expense) }}" class="inline-block text-sm font-semibold text-slate-800 hover:underline">Edit expense</a>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3">
            <h2 class="font-bold text-slate-900">Sub-items & linked food</h2>
            <p class="text-xs text-slate-500">Parent category <strong>{{ $expense->category?->name ?? '—' }}</strong> is inherited — not asked again.</p>

            @forelse($expense->subItems as $sub)
                <div class="flex justify-between text-sm border-b border-slate-100 pb-2">
                    <span>{{ $sub->description }}</span>
                    <span class="font-semibold">₹{{ number_format($sub->amount, 2) }}</span>
                </div>
            @empty
                @if($expense->foodEntries->isEmpty())
                    <p class="text-sm text-slate-500">No sub-items yet.</p>
                @endif
            @endforelse

            @foreach($expense->foodEntries as $food)
                <div class="flex justify-between text-sm border-b border-slate-100 pb-2">
                    <span>{{ $food->item_name }} <span class="text-xs text-slate-400">{{ $food->is_snack ? 'snack' : 'food' }}{{ $food->category ? ' · '.$food->category->name : '' }}</span></span>
                    <span class="font-semibold">₹{{ number_format($food->amount, 2) }}</span>
                </div>
            @endforeach

            <form action="{{ route('expenses.store') }}" method="POST" class="pt-3 grid grid-cols-2 gap-3 text-sm">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $expense->id }}">
                <input type="hidden" name="date" value="{{ $expense->date->toDateString() }}">
                <input type="hidden" name="payment_method" value="{{ $expense->payment_method }}">
                <input type="hidden" name="category_id" value="{{ $expense->category_id }}">
                <input type="text" name="description" required placeholder="Sub-item name" class="col-span-2 px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                <input type="number" step="0.01" name="amount" required placeholder="Amount" class="col-span-2 px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                <button class="col-span-2 py-2.5 rounded-xl bg-slate-900 text-white font-semibold">Add sub-item</button>
            </form>
        </div>

        @if($unlinkableFoods->count())
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3">
                <h2 class="font-bold text-slate-900">Link food / snacks to this expense</h2>
                <form action="{{ route('expenses.link-food', $expense) }}" method="POST" class="space-y-2 text-sm">
                    @csrf
                    @foreach($unlinkableFoods as $food)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="food_entry_ids[]" value="{{ $food->id }}" class="rounded border-slate-300">
                            <span>{{ $food->item_name }} · ₹{{ number_format($food->amount, 2) }} · {{ $food->date->format('d M') }}</span>
                        </label>
                    @endforeach
                    <button class="w-full py-2.5 rounded-xl bg-slate-900 text-white font-semibold">Link selected</button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>

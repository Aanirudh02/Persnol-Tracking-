@props(['expense', 'currentIncomeId' => null])

@php
    $total = $expense->totalAmount();
    $remaining = round($expense->untalliedAmount(), 2);
    $tallied = round($expense->totalTalliedAmount(), 2);
    // This income's slice comes first so it always starts the bar.
    $segments = $expense->incomeTallies->sortByDesc(fn ($tally) => $tally->income_id === $currentIncomeId)->values();
@endphp

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-[11px] font-bold">
        <span class="text-slate-600">₹{{ number_format($tallied, 2) }} tallied of ₹{{ number_format($total, 2) }}</span>
        @if($remaining > 0)
            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-amber-800">₹{{ number_format($remaining, 2) }} remaining</span>
        @else
            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-800">✓ Fully tallied</span>
        @endif
    </div>
    <div class="flex h-2.5 w-full gap-0.5 overflow-hidden rounded-full bg-amber-100" role="img" aria-label="₹{{ number_format($tallied, 2) }} of ₹{{ number_format($total, 2) }} tallied">
        @foreach($segments as $segment)
            <div class="h-full {{ $segment->income_id === $currentIncomeId ? 'bg-indigo-600' : 'bg-sky-400' }}" style="width: {{ $total > 0 ? min(100, round((float) $segment->allocated_amount / $total * 100, 2)) : 0 }}%"></div>
        @endforeach
    </div>
    <div class="flex flex-wrap gap-1.5 text-[10px] font-semibold">
        @foreach($segments as $segment)
            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2 py-0.5 text-slate-600">
                <span class="h-2 w-2 rounded-full {{ $segment->income_id === $currentIncomeId ? 'bg-indigo-600' : 'bg-sky-400' }}"></span>
                {{ $segment->income_id === $currentIncomeId ? 'This income' : ($segment->income?->source ?? 'Another income') }}
                · ₹{{ number_format((float) $segment->allocated_amount, 2) }}
            </span>
        @endforeach
    </div>
</div>

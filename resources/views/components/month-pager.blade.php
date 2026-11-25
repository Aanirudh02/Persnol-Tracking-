@props([
    'filter',
    'action' => null,
    'keep' => null,
])

@php
    /** @var \App\Services\MonthFilter $filter */
    $actionUrl = $action ?? request()->url();
    $keptParams = $keep ?? request()->except([$filter->key, 'page']);
    $monthUrl = fn (string $month): string => $actionUrl.'?'.\Illuminate\Support\Arr::query(array_merge($keptParams, [$filter->key => $month]));
    $navButton = 'inline-flex items-center gap-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 shadow-2xs hover:bg-slate-50 hover:text-slate-900 dark:hover:bg-slate-800';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }} data-month-pager>
    <a href="{{ $monthUrl($filter->previousValue()) }}" class="{{ $navButton }}" rel="prev" aria-label="Previous month: {{ $filter->previous()->format('F Y') }}">
        <span aria-hidden="true">&larr;</span> {{ $filter->previous()->format('M Y') }}
    </a>

    <form method="GET" action="{{ $actionUrl }}" class="flex items-center">
        @foreach($keptParams as $paramName => $paramValue)
            @if(is_array($paramValue))
                @foreach($paramValue as $paramItem)
                    <input type="hidden" name="{{ $paramName }}[]" value="{{ $paramItem }}">
                @endforeach
            @elseif($paramValue !== null && $paramValue !== '')
                <input type="hidden" name="{{ $paramName }}" value="{{ $paramValue }}">
            @endif
        @endforeach
        <label class="sr-only" for="month-pager-{{ $filter->key }}">Month</label>
        <input
            type="month"
            id="month-pager-{{ $filter->key }}"
            name="{{ $filter->key }}"
            value="{{ $filter->value() }}"
            required
            onchange="if (this.value) { this.form.requestSubmit(); }"
            class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold text-slate-800 dark:text-white shadow-2xs cursor-pointer"
            title="Choose a month"
        >
    </form>

    <a href="{{ $monthUrl($filter->nextValue()) }}" class="{{ $navButton }}" rel="next" aria-label="Next month: {{ $filter->next()->format('F Y') }}">
        {{ $filter->next()->format('M Y') }} <span aria-hidden="true">&rarr;</span>
    </a>

    @if($filter->wasInvalid)
        <span class="text-[11px] font-semibold text-amber-700" role="status">That month was not valid, so {{ $filter->label() }} is shown.</span>
    @endif
</div>

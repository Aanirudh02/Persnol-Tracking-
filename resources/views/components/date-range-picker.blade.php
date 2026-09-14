@props([
    'action' => null,
    'period' => request('period', 'all'),
    'fromDate' => request('from_date'),
    'toDate' => request('to_date'),
    'extraParams' => [],
])

@php
    $actionUrl = $action ?? request()->url();
    $currentPeriod = $period ?: 'all';
    $isCustom = $currentPeriod === 'custom';
@endphp

<div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all duration-200">
    <form id="date-range-picker-form" action="{{ $actionUrl }}" method="GET" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <!-- Maintain active extra filters -->
        @foreach($extraParams as $key => $val)
            @if(is_array($val))
                @foreach($val as $subVal)
                    <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}">
                @endforeach
            @elseif(!empty($val) && !in_array($key, ['period', 'from_date', 'to_date']))
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endif
        @endforeach

        <input type="hidden" name="period" id="drp-period-input" value="{{ $currentPeriod }}">

        <!-- Quick Presets -->
        <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200/60 dark:border-slate-700">
            @foreach(['all' => 'All Time', 'day' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $pKey => $pLabel)
                <button type="button" onclick="selectDateRangePreset('{{ $pKey }}')" 
                    class="drp-preset-btn px-3 py-1.5 text-xs rounded-lg transition-all cursor-pointer font-medium {{ $currentPeriod === $pKey ? 'bg-slate-900 text-white shadow-xs font-semibold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 hover:bg-slate-200/60' }}">
                    {{ $pLabel }}
                </button>
            @endforeach
            <button type="button" onclick="toggleDateRangeCustom()" 
                class="drp-preset-btn px-3 py-1.5 text-xs rounded-lg transition-all flex items-center gap-1 cursor-pointer font-medium {{ $isCustom ? 'bg-slate-900 text-white shadow-xs font-semibold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 hover:bg-slate-200/60' }}">
                <span>📅</span>
                Custom Range
            </button>
        </div>

        <!-- Custom Date Inputs -->
        <div id="drp-custom-container" class="{{ $isCustom ? 'flex' : 'hidden' }} flex-wrap items-center gap-2">
            <input type="date" name="from_date" value="{{ $fromDate }}" class="text-xs px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white focus:ring-2 focus:ring-slate-900">
            <span class="text-xs text-slate-400">to</span>
            <input type="date" name="to_date" value="{{ $toDate }}" class="text-xs px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white focus:ring-2 focus:ring-slate-900">
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-all cursor-pointer">
                Apply
            </button>
        </div>
    </form>
</div>

<script>
    function selectDateRangePreset(period) {
        document.getElementById('drp-period-input').value = period;
        document.getElementById('drp-custom-container').classList.add('hidden');
        document.getElementById('drp-custom-container').classList.remove('flex');
        document.getElementById('date-range-picker-form').submit();
    }

    function toggleDateRangeCustom() {
        const customContainer = document.getElementById('drp-custom-container');
        document.getElementById('drp-period-input').value = 'custom';
        customContainer.classList.remove('hidden');
        customContainer.classList.add('flex');
    }
</script>

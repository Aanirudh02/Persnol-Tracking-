@props(['active' => null])

@php
    $tabs = [
        'income' => ['route' => 'income.index', 'label' => 'Income', 'icon' => '↗', 'tone' => 'bg-emerald-600'],
        'expenses' => ['route' => 'expenses.index', 'label' => 'Expenses', 'icon' => '↘', 'tone' => 'bg-rose-600'],
        'personal' => ['route' => 'personal-expenses.index', 'label' => 'Personal Expenses', 'icon' => '👤', 'tone' => 'bg-pink-600'],
        'all' => ['route' => 'all-expenses.index', 'label' => 'All', 'icon' => '≡', 'tone' => 'bg-sky-600'],
        'bulk' => ['route' => 'bulk-entry.create', 'label' => 'Bulk add', 'icon' => '＋', 'tone' => 'bg-slate-900'],
    ];
@endphp

<nav aria-label="Income and expenses" class="-mx-1 flex gap-1.5 overflow-x-auto px-1 pb-1">
    @foreach($tabs as $key => $tab)
        <a href="{{ route($tab['route']) }}"
           @if($active === $key) aria-current="page" @endif
           class="inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition {{ $active === $key ? $tab['tone'].' text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">
            <span aria-hidden="true">{{ $tab['icon'] }}</span>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>

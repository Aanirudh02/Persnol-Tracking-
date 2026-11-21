@props(['active' => false, 'icon' => 'circle'])

@php
$classes = $active
    ? 'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold bg-slate-900 text-white shadow-sm transition-all'
    : 'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <span class="w-5 h-5 flex items-center justify-center text-current opacity-80">
        <x-nav-icon :name="$icon" />
    </span>
    <span>{{ $slot }}</span>
</a>

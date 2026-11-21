@php $onLessons = request()->routeIs('lessons.*'); @endphp
<nav aria-label="Mistakes and lessons" class="flex flex-wrap items-center gap-2">
    <a href="{{ route('mistakes.index') }}" @if(! $onLessons) aria-current="page" @endif
       class="rounded-xl px-4 py-2 text-xs font-bold transition {{ ! $onLessons ? 'bg-rose-600 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">
        ⚠️ Mistakes
    </a>
    <a href="{{ route('lessons.index') }}" @if($onLessons) aria-current="page" @endif
       class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $onLessons ? 'bg-sky-600 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">
        💡 Lessons
    </a>
</nav>

@if ($paginator->hasPages() || $paginator->total() > 0)
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-600 dark:text-slate-400">
        <!-- Left: X to Y of Z (total entries) -->
        <div>
            @if ($paginator->total() > 0)
                <p>
                    Showing
                    <span class="font-bold text-slate-900 dark:text-white">{{ $paginator->firstItem() }}</span>
                    to
                    <span class="font-bold text-slate-900 dark:text-white">{{ $paginator->lastItem() }}</span>
                    of
                    <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $paginator->total() }}</span>
                    <span class="text-slate-400 font-medium">(total entries)</span>
                </p>
            @else
                <p>No entries found</p>
            @endif
        </div>

        <!-- Right: < Previous, Page Numbers, Next > -->
        @if ($paginator->hasPages())
            <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center gap-1.5 flex-wrap">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-300 dark:text-slate-600 cursor-not-allowed text-xs font-semibold select-none flex items-center gap-1">
                        <span>&lsaquo;</span> Previous
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold transition flex items-center gap-1">
                        <span>&lsaquo;</span> Previous
                    </a>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="px-2 py-1 text-slate-400 select-none">&hellip;</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="px-3 py-1.5 rounded-xl bg-indigo-600 text-white font-bold text-xs shadow-xs select-none">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 font-semibold text-xs transition">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold transition flex items-center gap-1">
                        Next <span>&rsaquo;</span>
                    </a>
                @else
                    <span class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-300 dark:text-slate-600 cursor-not-allowed text-xs font-semibold select-none flex items-center gap-1">
                        Next <span>&rsaquo;</span>
                    </span>
                @endif
            </nav>
        @endif
    </div>
@endif

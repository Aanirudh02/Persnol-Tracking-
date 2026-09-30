<x-app-layout title="Statements">
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Statements & Financial Reports</h1>
                <p class="text-xs text-slate-500">Generate, organize, and export official statements for your normal and personal expenses.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('statements.create', ['type' => 'normal']) }}" style="background-color: #0f172a; color: #ffffff;" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <span>+</span> New Normal Statement
                </a>
                <a href="{{ route('statements.create', ['type' => 'personal']) }}" style="background-color: #db2777; color: #ffffff;" class="px-4 py-2 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <span>+</span> New Personal Statement
                </a>
            </div>
        </div>

        <!-- Top Metric Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Normal Statements</span>
                    <span class="text-lg">📘</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">₹{{ number_format($normalStatements->sum('total_amount'), 2) }}</div>
                <div class="mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 text-[11px] text-slate-500">
                    {{ $normalStatements->count() }} {{ Str::plural('statement', $normalStatements->count()) }} generated
                </div>
            </div>

            <div class="rounded-3xl border border-pink-100 dark:border-pink-950/40 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Personal Statements</span>
                    <span class="text-lg">🛍️</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-pink-600">₹{{ number_format($personalStatements->sum('total_amount'), 2) }}</div>
                <div class="mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 text-[11px] text-slate-500">
                    {{ $personalStatements->count() }} {{ Str::plural('statement', $personalStatements->count()) }} generated
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Saved Reports</span>
                    <span class="text-lg">📑</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ $normalStatements->count() + $personalStatements->count() }}</div>
                <div class="mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 text-[11px] text-slate-500">
                    Ready for print & export
                </div>
            </div>
        </div>

        <!-- Tab Selection Bar -->
        <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
            <button type="button" id="tab-btn-normal" onclick="switchStatementTab('normal')" 
                class="px-4 py-2 text-sm transition flex items-center gap-2 cursor-pointer font-bold border-b-2 border-slate-900 text-slate-900 dark:border-white dark:text-white">
                <span>📘</span> Normal Expenses Statements ({{ $normalStatements->count() }})
            </button>
            <button type="button" id="tab-btn-personal" onclick="switchStatementTab('personal')" 
                class="px-4 py-2 text-sm transition flex items-center gap-2 cursor-pointer text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white">
                <span>🛍️</span> Personal Expenses Statements ({{ $personalStatements->count() }})
            </button>
        </div>

        <!-- Normal Statements Tab Content -->
        <div id="tab-content-normal" class="space-y-4">
            <!-- Category Quick Statement Generator -->
            @if($normalCategories->isNotEmpty())
                <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🏷️</span>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Classify by Normal Category & Take Statement</h2>
                        </div>
                        <a href="{{ route('statements.create', ['type' => 'normal']) }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                            + Custom Statement &rarr;
                        </a>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                        @foreach($normalCategories as $nCat)
                            <a href="{{ route('statements.create', ['type' => 'normal', 'category_id' => $nCat->id]) }}" class="group flex flex-col justify-between p-3 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 hover:border-slate-300 hover:shadow-xs transition cursor-pointer">
                                <div>
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate group-hover:text-indigo-600 transition">{{ $nCat->name }}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $nCat->expenses_count ?? 0 }} items</div>
                                </div>
                                <div class="mt-2 flex items-center justify-between pt-1 border-t border-slate-200/60 dark:border-slate-700 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                    <span>₹{{ number_format((float) ($nCat->expenses_sum_amount ?? 0), 0) }}</span>
                                    <span class="text-[10px] text-indigo-600 opacity-0 group-hover:opacity-100 transition">&rarr;</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($normalStatements->isEmpty())
                <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-12 text-center shadow-sm">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center mx-auto mb-3 text-2xl font-bold">
                        📄
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">No Normal Statements Yet</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">Create a customized statement for your college and tracked regular expenses.</p>
                    <a href="{{ route('statements.create', ['type' => 'normal']) }}" style="background-color: #0f172a; color: #ffffff;" class="mt-4 inline-flex items-center px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition cursor-pointer">
                        + Create First Statement
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($normalStatements as $st)
                        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                        {{ $st->period_type }}
                                    </span>
                                    @if($allowStatementDeletion)
                                        <form action="{{ route('statements.destroy', $st) }}" method="POST" onsubmit="return confirm('Delete this statement?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-400 hover:text-rose-600 transition cursor-pointer px-2 py-0.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40" title="Delete statement">
                                                <span>🗑️</span> Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <h3 class="font-bold text-sm text-slate-900 dark:text-white mt-3">{{ $st->title }}</h3>
                                <p class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1"><span>🕒</span> Taken: {{ $st->created_at->format('D, M j, Y • h:i A') }}</p>
                            </div>

                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] uppercase font-semibold text-slate-400 block">Total Amount</span>
                                    <span class="text-lg font-bold text-slate-900 dark:text-white">₹{{ number_format($st->total_amount, 2) }}</span>
                                </div>
                                <a href="{{ route('statements.show', $st) }}" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                                    View / Print &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Personal Statements Tab Content -->
        <div id="tab-content-personal" class="hidden space-y-4">
            <!-- Category Quick Statement Generator -->
            @if($personalCategories->isNotEmpty())
                <div class="rounded-3xl border border-pink-100 dark:border-pink-950/40 bg-white dark:bg-slate-900 p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🛍️</span>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Classify by Personal Category & Take Statement</h2>
                        </div>
                        <a href="{{ route('statements.create', ['type' => 'personal']) }}" class="text-xs font-bold text-pink-600 dark:text-pink-400 hover:underline">
                            + Custom Personal Statement &rarr;
                        </a>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                        @foreach($personalCategories as $pCat)
                            <a href="{{ route('statements.create', ['type' => 'personal', 'category_id' => $pCat->id]) }}" class="group flex flex-col justify-between p-3 rounded-2xl border border-pink-50 dark:border-pink-950/30 bg-pink-50/30 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 hover:border-pink-300 hover:shadow-xs transition cursor-pointer">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $pCat->color ?? '#ec4899' }}"></span>
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate group-hover:text-pink-600 transition">{{ $pCat->name }}</div>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $pCat->personal_expenses_count ?? 0 }} items</div>
                                </div>
                                <div class="mt-2 flex items-center justify-between pt-1 border-t border-pink-100 dark:border-pink-950/40 text-[11px] font-bold text-pink-700 dark:text-pink-300">
                                    <span>₹{{ number_format((float) ($pCat->personal_expenses_sum_amount ?? 0), 0) }}</span>
                                    <span class="text-[10px] text-pink-600 opacity-0 group-hover:opacity-100 transition">&rarr;</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($personalStatements->isEmpty())
                <div class="rounded-3xl border border-pink-100 dark:border-pink-950/40 bg-white dark:bg-slate-900 p-12 text-center shadow-sm">
                    <div class="w-14 h-14 rounded-2xl bg-pink-50 dark:bg-pink-950/40 text-pink-600 flex items-center justify-center mx-auto mb-3 text-2xl font-bold">
                        🛍️
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">No Personal Statements Yet</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">Create a customized statement dedicated to your personal, family, and tour expenses.</p>
                    <a href="{{ route('statements.create', ['type' => 'personal']) }}" style="background-color: #db2777; color: #ffffff;" class="mt-4 inline-flex items-center px-4 py-2.5 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-xs font-bold transition cursor-pointer">
                        + Create Personal Statement
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($personalStatements as $st)
                        <div class="rounded-3xl border border-pink-100 dark:border-pink-950/40 bg-white dark:bg-slate-900 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="rounded-full bg-pink-50 dark:bg-pink-950/40 px-2.5 py-0.5 text-[10px] font-bold text-pink-700 dark:text-pink-300 uppercase tracking-wider">
                                        {{ $st->period_type }}
                                    </span>
                                    @if($allowStatementDeletion)
                                        <form action="{{ route('statements.destroy', $st) }}" method="POST" onsubmit="return confirm('Delete this statement?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-400 hover:text-rose-600 transition cursor-pointer px-2 py-0.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40" title="Delete statement">
                                                <span>🗑️</span> Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <h3 class="font-bold text-sm text-slate-900 dark:text-white mt-3">{{ $st->title }}</h3>
                                <p class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1"><span>🕒</span> Taken: {{ $st->created_at->format('D, M j, Y • h:i A') }}</p>
                            </div>

                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] uppercase font-semibold text-slate-400 block">Personal Spend</span>
                                    <span class="text-lg font-bold text-pink-600">₹{{ number_format($st->total_amount, 2) }}</span>
                                </div>
                                <a href="{{ route('statements.show', $st) }}" class="px-3.5 py-2 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-xs font-semibold transition">
                                    View / Print &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script>
        function switchStatementTab(tab) {
            const normalBtn = document.getElementById('tab-btn-normal');
            const personalBtn = document.getElementById('tab-btn-personal');
            const normalContent = document.getElementById('tab-content-normal');
            const personalContent = document.getElementById('tab-content-personal');

            if (tab === 'normal') {
                normalBtn.className = 'px-4 py-2 text-sm transition flex items-center gap-2 cursor-pointer font-bold border-b-2 border-slate-900 text-slate-900 dark:border-white dark:text-white';
                personalBtn.className = 'px-4 py-2 text-sm transition flex items-center gap-2 cursor-pointer text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white';
                normalContent.classList.remove('hidden');
                personalContent.classList.add('hidden');
            } else {
                personalBtn.className = 'px-4 py-2 text-sm transition flex items-center gap-2 cursor-pointer font-bold border-b-2 border-pink-600 text-pink-600';
                normalBtn.className = 'px-4 py-2 text-sm transition flex items-center gap-2 cursor-pointer text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white';
                personalContent.classList.remove('hidden');
                normalContent.classList.add('hidden');
            }
        }

        // Initialize tab from URL parameter if provided
        @if(request('tab') === 'personal')
            switchStatementTab('personal');
        @endif
    </script>
</x-app-layout>

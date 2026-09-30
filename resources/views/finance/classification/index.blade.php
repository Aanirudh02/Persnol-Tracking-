<x-app-layout title="Classify Expenses">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400 dark:text-slate-500 mb-1">
                        <a href="{{ route('finance.index') }}" class="hover:text-violet-600 transition">Finance</a>
                        <span>/</span>
                        <span class="text-slate-600 dark:text-slate-300 font-medium">Classifications</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">🏷️ Expense Classification</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tag your expenses one by one. Export a PDF report — zero extra storage.</p>
                </div>
                <a href="{{ route('classification.print', array_filter(['domain' => $domain, 'period' => $period, 'from_date' => $fromDate, 'to_date' => $toDate, 'category_id' => $categoryId])) }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-violet-500/20 transition active:scale-95 shrink-0">
                    <span>📄</span> Quick PDF Preview
                </a>
            </div>

            {{-- Inline Add Classification Type --}}
            <div class="bg-violet-50 dark:bg-violet-950/30 border border-violet-200 dark:border-violet-800/40 rounded-2xl p-4"
                 x-data="{ open: false }">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-violet-800 dark:text-violet-200">Classification Types</span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($classifications as $cls)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold text-white"
                                      style="background-color: {{ $cls['color'] ?? '#6b7280' }}">
                                    {{ $cls['icon'] ?? '' }} {{ $cls['name'] }}
                                </span>
                            @endforeach
                            @if($classifications->isEmpty())
                                <span class="text-xs text-violet-400 italic">No types yet — add one below</span>
                            @endif
                        </div>
                    </div>
                    <button type="button" @click="open = !open"
                            class="text-xs font-bold px-3 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-700 text-white transition shrink-0">
                        <span x-show="!open">+ Add Type</span>
                        <span x-show="open" x-cloak>✕ Close</span>
                    </button>
                </div>

                {{-- Quick Add Form --}}
                <div x-show="open" x-cloak x-transition class="mt-3 pt-3 border-t border-violet-200 dark:border-violet-800/40">
                    <form action="{{ route('options.store') }}" method="POST" class="flex gap-2 items-end flex-wrap">
                        @csrf
                        <input type="hidden" name="type" value="expense_classification">
                        <div class="flex-1 min-w-[160px]">
                            <label class="block text-xs font-semibold text-violet-700 dark:text-violet-300 mb-1">Tag Name</label>
                            <input type="text" name="name" placeholder="e.g. Emergency, Fun…" required maxlength="100"
                                   class="w-full px-3 py-2 text-sm border border-violet-200 dark:border-violet-700 dark:bg-slate-800 dark:text-slate-100 rounded-xl focus:ring-2 focus:ring-violet-400 focus:outline-none">
                        </div>
                        <button type="submit"
                                class="px-5 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-xl transition active:scale-95">
                            Add
                        </button>
                        <a href="{{ route('settings.index') }}#classifications" class="px-3 py-2 text-xs text-violet-600 dark:text-violet-300 hover:underline font-semibold">
                            Manage all →
                        </a>
                    </form>
                </div>
            </div>
        </div>


        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('classification.index') }}" id="filter-form"
              class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-4 space-y-4">

            {{-- Row 1: Domain + Period --}}
            <div class="flex flex-wrap gap-3 items-center">
                {{-- Domain Scope --}}
                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mr-1">Scope</span>
                    @foreach(['normal' => '📋 Normal', 'personal' => '🛍️ Personal', 'all' => '📚 All'] as $val => $label)
                        <a href="{{ request()->fullUrlWithQuery(['domain' => $val, 'period' => $period]) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition border
                               {{ $domain === $val ? 'bg-sky-600 text-white border-sky-600 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-transparent hover:border-sky-300' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                <div class="hidden sm:block w-px h-6 bg-slate-200 dark:bg-slate-700"></div>

                {{-- Period Presets --}}
                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mr-1">Period</span>
                    @foreach(['day' => 'Today', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year', 'all' => 'All Time', 'custom' => 'Custom'] as $val => $label)
                        <a href="{{ request()->fullUrlWithQuery(['period' => $val, 'domain' => $domain]) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition border
                               {{ $period === $val ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-transparent hover:border-indigo-300' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Row 2: Custom Date + Category + Status --}}
            <div class="flex flex-wrap gap-3 items-end">
                @if($period === 'custom')
                    <div class="flex items-center gap-2">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">From</label>
                            <input type="date" name="from_date" value="{{ $fromDate }}"
                                   class="px-3 py-2 text-sm border border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">To</label>
                            <input type="date" name="to_date" value="{{ $toDate }}"
                                   class="px-3 py-2 text-sm border border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                        </div>
                    </div>
                @endif

                <input type="hidden" name="domain" value="{{ $domain }}">
                <input type="hidden" name="period" value="{{ $period }}">

                {{-- Category Filter --}}
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Category</label>
                    <select name="category_id" onchange="this.form.submit()"
                            class="px-3 py-2 text-sm border border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                        <option value="">All Categories</option>
                        @if(in_array($domain, ['normal','all']))
                            <optgroup label="Normal">
                                @foreach($normalCategories as $cat)
                                    <option value="{{ $cat->id }}" @selected($categoryId == $cat->id)>{{ $cat->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if(in_array($domain, ['personal','all']))
                            <optgroup label="Personal">
                                @foreach($personalCategories as $cat)
                                    <option value="{{ $cat->id }}" @selected($categoryId == $cat->id)>{{ $cat->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Status</label>
                    <select name="status" onchange="this.form.submit()"
                            class="px-3 py-2 text-sm border border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                        <option value="all" @selected($statusFilter === 'all')>All</option>
                        <option value="unclassified" @selected($statusFilter === 'unclassified')>Unclassified Only</option>
                        @foreach($classifications as $cls)
                            <option value="{{ $cls['name'] }}" @selected($statusFilter === $cls['name'])>{{ $cls['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                @if($period === 'custom')
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition">Apply</button>
                @endif
            </div>
        </form>

        @php
            $totalExpenses = $expenses->count() + $personalExpenses->count();
            $allItems = collect();
            foreach($expenses as $e) {
                $allItems->push(['type'=>'expense','model'=>$e]);
            }
            foreach($personalExpenses as $p) {
                $allItems->push(['type'=>'personal_expense','model'=>$p]);
            }
            $allItems = $allItems->sortByDesc(fn($i)=>$i['model']->date)->values();
        @endphp

        {{-- Count Summary --}}
        <div class="flex items-center justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Showing <strong class="text-slate-900 dark:text-white">{{ $totalExpenses }}</strong> expense{{ $totalExpenses !== 1 ? 's' : '' }}
            </p>
            @if($totalExpenses > 0)
                <span class="text-xs text-slate-400 dark:text-slate-500">Select a tag for each expense, then save at the bottom.</span>
            @endif
        </div>

        @if($totalExpenses === 0)
            <div class="text-center py-16 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800">
                <div class="text-5xl mb-3">🔍</div>
                <p class="text-slate-500 dark:text-slate-400 font-medium">No expenses found for this filter.</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Try changing the scope, period, or category filter above.</p>
            </div>
        @else
            {{-- Action Buttons (TOP) --}}
            <div class="flex flex-wrap gap-3 sticky top-16 z-10 bg-[#f4f8fc] dark:bg-slate-950 py-3 -mx-4 px-4 border-b border-slate-200 dark:border-slate-800">
                <button type="submit" form="classification-form" name="action" value="save"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-sm shadow-emerald-500/20 transition active:scale-95">
                    💾 Save Classifications
                </button>
                <button type="submit" form="classification-form" name="action" value="save_and_pdf"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl shadow-sm shadow-sky-500/20 transition active:scale-95">
                    💾📄 Save & Export PDF
                </button>
                <a href="{{ route('classification.print', array_filter(['domain' => $domain, 'period' => $period, 'from_date' => $fromDate, 'to_date' => $toDate, 'category_id' => $categoryId])) }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold rounded-xl shadow-sm shadow-violet-500/20 transition active:scale-95">
                    📄 Export PDF (No Save)
                </a>
            </div>

            {{-- Classification Form --}}
            <form id="classification-form" method="POST" action="{{ route('classification.save') }}">
                @csrf
                <input type="hidden" name="domain" value="{{ $domain }}">
                <input type="hidden" name="period" value="{{ $period }}">
                @if($fromDate)<input type="hidden" name="from_date" value="{{ $fromDate }}">@endif
                @if($toDate)<input type="hidden" name="to_date" value="{{ $toDate }}">@endif
                @if($categoryId)<input type="hidden" name="category_id" value="{{ $categoryId }}">@endif

                <div class="space-y-3">
                    @foreach($allItems as $index => $item)
                        @php
                            $model = $item['model'];
                            $modelType = $item['type'];
                            $inputName = "classifications[{$modelType}][{$model->id}]";
                            $currentClassification = $model->classification;
                        @endphp

                        <div class="bg-white dark:bg-slate-900 rounded-2xl border {{ $currentClassification ? 'border-l-4 border-l-sky-400 border-slate-100 dark:border-slate-800' : 'border-slate-100 dark:border-slate-800' }} shadow-sm p-4 transition hover:shadow-md">
                            <div class="flex flex-col sm:flex-row sm:items-start gap-3">

                                {{-- Index & Type Badge --}}
                                <div class="flex items-center gap-2 sm:flex-col sm:items-center sm:gap-1 shrink-0 min-w-[52px]">
                                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500">#{{ $index + 1 }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide
                                        {{ $modelType === 'expense' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' }}">
                                        {{ $modelType === 'expense' ? 'Normal' : 'Personal' }}
                                    </span>
                                </div>

                                {{-- Expense Details --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <span class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ $model->description ?: '—' }}</span>
                                        @if($model->category)
                                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-full text-[11px] font-medium">{{ $model->category->name }}</span>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap gap-3 text-xs text-slate-500 dark:text-slate-400">
                                        <span>📅 {{ \Carbon\Carbon::parse($model->date)->format('D, d M Y') }}</span>
                                        @if($model->payment_method)
                                            <span>💳 {{ $model->payment_method }}</span>
                                        @endif
                                        @if($model->notes)
                                            <span class="truncate max-w-xs">📝 {{ Str::limit($model->notes, 50) }}</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Amount --}}
                                <div class="text-right shrink-0">
                                    <span class="text-lg font-bold text-slate-900 dark:text-white">₹{{ number_format($model->totalAmount(), 2) }}</span>
                                </div>
                            </div>

                            {{-- Classification Pills --}}
                            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                                <div class="flex flex-wrap gap-2 items-center">
                                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Tag:</span>
                                    @foreach($classifications as $cls)
                                        @php
                                            $isSelected = $currentClassification === $cls['name'];
                                            $color = $cls['color'] ?? '#6b7280';
                                        @endphp
                                        <label class="cursor-pointer group">
                                            <input type="radio"
                                                   name="{{ $inputName }}"
                                                   value="{{ $cls['name'] }}"
                                                   class="sr-only peer"
                                                   {{ $isSelected ? 'checked' : '' }}>
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold border-2 transition-all select-none
                                                peer-checked:text-white peer-checked:shadow-md
                                                {{ $isSelected ? 'text-white' : 'text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:border-slate-400' }}"
                                                style="{{ $isSelected ? "background-color:{$color};border-color:{$color};" : '' }}"
                                                x-data
                                                @click="$el.parentNode.querySelector('input').checked = true; updatePill($el, '{{ $color }}')">
                                                {{ $cls['icon'] ?? '' }} {{ $cls['name'] }}
                                            </span>
                                        </label>
                                    @endforeach

                                    {{-- Clear Tag --}}
                                    <label class="cursor-pointer">
                                        <input type="radio" name="{{ $inputName }}" value="" class="sr-only peer" {{ !$currentClassification ? 'checked' : '' }}>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold border-2 border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:border-rose-400 hover:text-rose-500 transition-all peer-checked:border-slate-400 peer-checked:text-slate-600 dark:peer-checked:text-slate-300 bg-slate-50 dark:bg-slate-800 select-none">
                                            ✕ None
                                        </span>
                                    </label>

                                    @if($currentClassification)
                                        <span class="ml-auto text-xs text-sky-600 dark:text-sky-400 font-medium">✓ Tagged: {{ $currentClassification }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Action Buttons (BOTTOM) --}}
                <div class="flex flex-wrap gap-3 mt-6 pt-6 border-t border-slate-200 dark:border-slate-800">
                    <button type="submit" name="action" value="save"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-emerald-500/20 transition active:scale-95">
                        💾 Save Classifications
                    </button>
                    <button type="submit" name="action" value="save_and_pdf"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-sky-500/20 transition active:scale-95">
                        💾📄 Save & Export to PDF
                    </button>
                    <a href="{{ route('classification.print', array_filter(['domain' => $domain, 'period' => $period, 'from_date' => $fromDate, 'to_date' => $toDate, 'category_id' => $categoryId])) }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-violet-500/20 transition active:scale-95">
                        📄 Export PDF (No Save)
                    </a>
                </div>
            </form>
        @endif
    </div>

    <script>
        function updatePill(el, color) {
            // Uncheck siblings
            const group = el.closest('.flex');
            group.querySelectorAll('label span:not(.sr-only)').forEach(span => {
                span.style.backgroundColor = '';
                span.style.borderColor = '';
                span.classList.remove('text-white', 'shadow-md');
            });
            // Check the clicked one
            el.style.backgroundColor = color;
            el.style.borderColor = color;
            el.classList.add('text-white', 'shadow-md');
        }

        // Init pre-selected pills on load
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input[type=radio]:checked').forEach(input => {
                if (!input.value) return;
                const span = input.nextElementSibling;
                const style = span ? span.getAttribute('style') : '';
                const match = style && style.match(/background-color:([^;]+)/);
                if (match) {
                    span.classList.add('text-white', 'shadow-md');
                }
            });
        });
    </script>
</x-app-layout>


<x-app-layout title="Take Statement">
    <div class="max-w-3xl mx-auto space-y-6">
        <!-- Top Toolbar / Back button -->
        <div class="flex items-center justify-between">
            <a href="{{ route('statements.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-xs hover:bg-slate-50 transition flex items-center gap-1.5">
                &larr; Back to Statements
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ route('expenses.index') }}" class="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-white transition">
                    View Expenses &rarr;
                </a>
            </div>
        </div>

        <!-- Main Form Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8 space-y-6">
            <!-- Header -->
            <div class="border-b border-slate-100 dark:border-slate-800 pb-5">
                <div class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white">
                    <span>📄</span>
                    <h1>Generate Financial Statement</h1>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Select your period and category filters to generate a clean, printable statement report.
                </p>
            </div>

            <form action="{{ route('statements.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Statement Type Selector -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Statement Type</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button type="button" id="btn-type-normal" onclick="setCreateStatementType('normal')"
                            class="flex items-center gap-3 p-4 rounded-2xl border text-left transition cursor-pointer border-slate-900 bg-slate-900 text-white shadow-sm">
                            <span class="text-2xl">📘</span>
                            <div>
                                <strong class="block text-sm font-bold">Normal Expenses</strong>
                                <span class="text-[11px] opacity-80">College & tracked regular expenses</span>
                            </div>
                        </button>
                        <button type="button" id="btn-type-personal" onclick="setCreateStatementType('personal')"
                            class="flex items-center gap-3 p-4 rounded-2xl border text-left transition cursor-pointer border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                            <span class="text-2xl">🛍️</span>
                            <div>
                                <strong class="block text-sm font-bold">Personal Expenses</strong>
                                <span class="text-[11px] opacity-80">Family, tour, hotel, and shopping</span>
                            </div>
                        </button>
                    </div>
                    <input type="hidden" name="type" id="create-statement-type-input" value="{{ $type ?? 'normal' }}">
                </div>

                <!-- Statement Title (Optional) -->
                <div>
                    <label for="create-statement-title" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Statement Title (Optional)</label>
                    <input type="text" name="title" id="create-statement-title" placeholder="e.g. 1st expenses or Trip Aug 2026"
                        class="w-full text-xs px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-slate-900">
                </div>

                <!-- Time Period Selector -->
                <div>
                    <label for="create-statement-period" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Time Period</label>
                    <select name="period_type" id="create-statement-period" onchange="onCreateStatementPeriodChanged(this.value)"
                        class="w-full text-xs px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-slate-900">
                        <option value="all" selected>Default: All Time</option>
                        <option value="day">Today (Day)</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                        <option value="custom">Custom Date Range</option>
                    </select>
                </div>

                <!-- Custom Date Inputs (Appears only when period is 'custom') -->
                <div id="create-custom-date-box" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Start Date</label>
                        <input type="date" name="start_date" id="create-start-date" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">End Date</label>
                        <input type="date" name="end_date" id="create-end-date" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <!-- Categories Selection (Smooth In-Flow, No Lag) -->
                <div class="space-y-3">
                    <label for="category-mode-select" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Categories</label>
                    
                    <select id="category-mode-select" onchange="onCategoryModeChange(this.value)"
                        class="w-full text-xs px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-slate-900 font-medium cursor-pointer">
                        <option value="all" selected>All Categories (Default - include all)</option>
                        <option value="manual">Select Categories Manually...</option>
                    </select>

                    <!-- Default Mode Helper Info -->
                    <div id="all-categories-hint" class="flex items-center gap-2 p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300">
                        <span class="text-emerald-500 font-bold">✓</span>
                        <span>All categories will be automatically included in this statement.</span>
                    </div>

                    <!-- Manual Category Selection Box with Real-Time Search -->
                    <div id="manual-category-container" class="hidden space-y-3 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40">
                        <!-- Search & Quick Selection Header Bar -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 pb-3 border-b border-slate-200 dark:border-slate-700">
                            <div class="relative flex-1">
                                <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                                <input type="text" id="cat-search-input" oninput="filterCategoryList(this.value)" placeholder="Search categories..."
                                    class="w-full pl-8 pr-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-slate-900">
                            </div>
                            <div class="flex items-center justify-between sm:justify-end gap-3 text-xs font-semibold px-1">
                                <span id="selected-cat-counter" class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                                    0 selected
                                </span>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="selectAllCategories(true)" class="text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer">Select All</button>
                                    <span class="text-slate-300 dark:text-slate-600">|</span>
                                    <button type="button" onclick="selectAllCategories(false)" class="text-rose-500 hover:underline cursor-pointer">Clear All</button>
                                </div>
                            </div>
                        </div>

                        <!-- Normal Categories Grid -->
                        <div id="create-normal-cats" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-60 overflow-y-auto pr-1">
                            @foreach($normalCategories as $nCat)
                                <label class="cat-card flex items-center gap-2.5 p-2.5 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-500 cursor-pointer text-xs transition select-none">
                                    <input type="checkbox" name="category_ids[]" value="{{ $nCat->id }}" data-name="{{ strtolower($nCat->name) }}" onchange="updateSelectedCount()" class="cat-checkbox rounded border-slate-300 text-slate-900 focus:ring-slate-900 cursor-pointer">
                                    <span class="font-medium text-slate-800 dark:text-slate-200 truncate">{{ $nCat->name }}</span>
                                </label>
                            @endforeach
                            <div id="no-normal-cats-match" class="hidden col-span-full py-4 text-center text-xs text-slate-400">
                                No categories match your search.
                            </div>
                        </div>

                        <!-- Personal Categories Grid -->
                        <div id="create-personal-cats" class="hidden grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-60 overflow-y-auto pr-1">
                            @foreach($personalCategories as $pCat)
                                <label class="cat-card flex items-center gap-2.5 p-2.5 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-pink-300 dark:hover:border-pink-800 cursor-pointer text-xs transition select-none">
                                    <input type="checkbox" name="category_ids[]" value="{{ $pCat->id }}" data-name="{{ strtolower($pCat->name) }}" onchange="updateSelectedCount()" class="cat-checkbox rounded border-slate-300 text-pink-600 focus:ring-pink-500 cursor-pointer">
                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $pCat->color ?? '#ec4899' }}"></span>
                                    <span class="font-medium text-slate-800 dark:text-slate-200 truncate">{{ $pCat->name }}</span>
                                </label>
                            @endforeach
                            <div id="no-personal-cats-match" class="hidden col-span-full py-4 text-center text-xs text-slate-400">
                                No personal categories match your search.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manual Individual Transactions Picker (Optional) -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="create-use-custom-exp" onchange="toggleCreateCustomList(this.checked)" class="rounded border-slate-300 cursor-pointer">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Select specific individual transactions manually (Optional)</span>
                        </label>
                        <div id="custom-exp-actions" class="hidden text-xs font-semibold space-x-2">
                            <span class="text-[11px] text-slate-400 mr-1">All selected by default</span>
                            <button type="button" onclick="selectAllCustomExpenses(true)" class="text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer">Select All</button>
                            <span class="text-slate-300 dark:text-slate-600">|</span>
                            <button type="button" onclick="selectAllCustomExpenses(false)" class="text-rose-500 hover:underline cursor-pointer">Clear All</button>
                        </div>
                    </div>

                    <div id="create-custom-expenses-wrapper" class="hidden mt-3 max-h-56 overflow-y-auto p-3 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-1 text-xs">
                        <div id="create-normal-expense-items">
                            @foreach($recentNormalExpenses as $rne)
                                <label class="flex items-center justify-between p-2 hover:bg-white dark:hover:bg-slate-700/60 rounded-xl cursor-pointer border border-transparent hover:border-slate-200 dark:hover:border-slate-750 transition">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <input type="checkbox" name="custom_expense_ids[]" value="{{ $rne->id }}" class="custom-exp-cb rounded border-slate-300 text-slate-900 focus:ring-slate-900 cursor-pointer">
                                        <span class="text-slate-700 dark:text-slate-300 truncate">{{ $rne->date->format('M j, Y') }} - {{ Str::limit($rne->description ?: $rne->category?->name, 35) }}</span>
                                    </div>
                                    <span class="font-bold text-slate-900 dark:text-white flex-shrink-0 ml-2">₹{{ number_format($rne->totalAmount(), 2) }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div id="create-personal-expense-items" class="hidden">
                            @foreach($recentPersonalExpenses as $rpe)
                                <label class="flex items-center justify-between p-2 hover:bg-white dark:hover:bg-slate-700/60 rounded-xl cursor-pointer border border-transparent hover:border-pink-200 dark:hover:border-pink-900 transition">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <input type="checkbox" name="custom_expense_ids[]" value="{{ $rpe->id }}" class="custom-exp-cb rounded border-slate-300 text-pink-600 focus:ring-pink-500 cursor-pointer">
                                        <span class="text-slate-700 dark:text-slate-300 truncate">{{ $rpe->date->format('M j, Y') }} - {{ Str::limit($rpe->description ?: $rpe->category?->name, 35) }}</span>
                                    </div>
                                    <span class="font-bold text-pink-600 flex-shrink-0 ml-2">₹{{ number_format($rpe->amount, 2) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Notes (Optional) -->
                <div>
                    <label for="create-statement-notes" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Statement Notes (Optional)</label>
                    <textarea name="notes" id="create-statement-notes" rows="2" placeholder="Add any special notes or remarks for this report..."
                        class="w-full text-xs px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-slate-900"></textarea>
                </div>

                <!-- Submit Toolbar -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('statements.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        Cancel
                    </a>
                    <button type="submit" style="background-color: #0f172a; color: #ffffff;" class="px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer">
                        Generate Statement &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function setCreateStatementType(type) {
            document.getElementById('create-statement-type-input').value = type;
            const btnNormal = document.getElementById('btn-type-normal');
            const btnPersonal = document.getElementById('btn-type-personal');
            const normalCats = document.getElementById('create-normal-cats');
            const personalCats = document.getElementById('create-personal-cats');
            const normalItems = document.getElementById('create-normal-expense-items');
            const personalItems = document.getElementById('create-personal-expense-items');

            if (type === 'normal') {
                btnNormal.className = 'flex items-center gap-3 p-4 rounded-2xl border text-left transition cursor-pointer border-slate-900 bg-slate-900 text-white shadow-sm';
                btnPersonal.className = 'flex items-center gap-3 p-4 rounded-2xl border text-left transition cursor-pointer border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300';
                normalCats.classList.remove('hidden');
                personalCats.classList.add('hidden');
                normalItems.classList.remove('hidden');
                personalItems.classList.add('hidden');
            } else {
                btnPersonal.className = 'flex items-center gap-3 p-4 rounded-2xl border text-left transition cursor-pointer border-pink-600 bg-pink-600 text-white shadow-sm';
                btnNormal.className = 'flex items-center gap-3 p-4 rounded-2xl border text-left transition cursor-pointer border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300';
                personalCats.classList.remove('hidden');
                normalCats.classList.add('hidden');
                personalItems.classList.remove('hidden');
                normalItems.classList.add('hidden');
            }

            // Reset search input and refresh filter
            const searchInput = document.getElementById('cat-search-input');
            if (searchInput) {
                searchInput.value = '';
                filterCategoryList('');
            }
            
            // If custom transactions is open, ensure active pool has transactions selected
            const customExpChecked = document.getElementById('create-use-custom-exp').checked;
            if (customExpChecked) {
                selectAllCustomExpenses(true);
            }

            const catMode = document.getElementById('category-mode-select').value;
            if (catMode === 'manual') {
                selectAllCategories(true);
            } else {
                updateSelectedCount();
            }
        }

        function onCreateStatementPeriodChanged(val) {
            const box = document.getElementById('create-custom-date-box');
            if (val === 'custom') {
                box.classList.remove('hidden');
                box.classList.add('grid');
            } else {
                box.classList.add('hidden');
                box.classList.remove('grid');
            }
        }

        function onCategoryModeChange(mode) {
            const container = document.getElementById('manual-category-container');
            const hint = document.getElementById('all-categories-hint');
            if (mode === 'manual') {
                container.classList.remove('hidden');
                hint.classList.add('hidden');
                // By default select all categories so user can unselect unwanted ones
                selectAllCategories(true);
            } else {
                container.classList.add('hidden');
                hint.classList.remove('hidden');
                // Clear any manual selections so default all takes place cleanly
                selectAllCategories(false);
            }
        }

        function updateSelectedCount() {
            const currentType = document.getElementById('create-statement-type-input').value;
            const containerId = currentType === 'normal' ? 'create-normal-cats' : 'create-personal-cats';
            const checked = document.querySelectorAll(`#${containerId} .cat-checkbox:checked`).length;
            const counter = document.getElementById('selected-cat-counter');
            if (counter) {
                counter.textContent = `${checked} selected`;
                if (checked > 0) {
                    counter.className = currentType === 'normal'
                        ? 'px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-900 text-white'
                        : 'px-2.5 py-1 rounded-full text-[11px] font-bold bg-pink-600 text-white';
                } else {
                    counter.className = 'px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200';
                }
            }

            // Update card border styles for active feedback
            document.querySelectorAll(`#${containerId} .cat-card`).forEach(card => {
                const cb = card.querySelector('.cat-checkbox');
                if (cb && cb.checked) {
                    card.classList.add('ring-2', currentType === 'normal' ? 'ring-slate-900' : 'ring-pink-500');
                } else {
                    card.classList.remove('ring-2', 'ring-slate-900', 'ring-pink-500');
                }
            });
        }

        function selectAllCategories(state) {
            const currentType = document.getElementById('create-statement-type-input').value;
            const containerId = currentType === 'normal' ? 'create-normal-cats' : 'create-personal-cats';
            document.querySelectorAll(`#${containerId} .cat-checkbox`).forEach(cb => {
                const label = cb.closest('.cat-card');
                if (!label || !label.classList.contains('hidden')) {
                    cb.checked = state;
                }
            });
            updateSelectedCount();
        }

        function filterCategoryList(query) {
            const term = query.toLowerCase().trim();
            const currentType = document.getElementById('create-statement-type-input').value;
            const containerId = currentType === 'normal' ? 'create-normal-cats' : 'create-personal-cats';
            const noMatchMsg = document.getElementById(currentType === 'normal' ? 'no-normal-cats-match' : 'no-personal-cats-match');
            
            let visibleCount = 0;
            document.querySelectorAll(`#${containerId} .cat-card`).forEach(card => {
                const cb = card.querySelector('.cat-checkbox');
                const name = (cb ? cb.dataset.name : '') || card.textContent.toLowerCase();
                if (!term || name.includes(term)) {
                    card.classList.remove('hidden');
                    card.classList.add('flex');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                    card.classList.remove('flex');
                }
            });

            if (noMatchMsg) {
                if (visibleCount === 0) {
                    noMatchMsg.classList.remove('hidden');
                } else {
                    noMatchMsg.classList.add('hidden');
                }
            }
        }

        function toggleCreateCustomList(checked) {
            const wrapper = document.getElementById('create-custom-expenses-wrapper');
            const actions = document.getElementById('custom-exp-actions');
            if (checked) {
                wrapper.classList.remove('hidden');
                if (actions) actions.classList.remove('hidden');
                // By default select all transactions so user can uncheck unwanted ones
                selectAllCustomExpenses(true);
            } else {
                wrapper.classList.add('hidden');
                if (actions) actions.classList.add('hidden');
                selectAllCustomExpenses(false);
            }
        }

        function selectAllCustomExpenses(state) {
            const currentType = document.getElementById('create-statement-type-input').value;
            const containerId = currentType === 'normal' ? 'create-normal-expense-items' : 'create-personal-expense-items';
            document.querySelectorAll(`#${containerId} input[name="custom_expense_ids[]"]`).forEach(cb => {
                cb.checked = state;
            });
        }

        // Initial setup
        @if(($type ?? 'normal') === 'personal')
            setCreateStatementType('personal');
        @else
            updateSelectedCount();
        @endif
    </script>
</x-app-layout>

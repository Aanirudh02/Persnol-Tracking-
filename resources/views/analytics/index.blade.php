<x-app-layout title="Analytics & Trends">
    <div class="space-y-6">
        <!-- 1. HEADER & HIGH-FI DATE FILTER BAR -->
        <div class="bg-white dark:bg-slate-800/95 p-4 sm:p-6 rounded-3xl border border-slate-200/90 dark:border-slate-700/80 shadow-sm space-y-4">
            <!-- Top Row: Title & Domain Selector -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                        <span class="p-2 rounded-2xl bg-indigo-50 dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 text-xl shadow-xs">📊</span>
                        <span>Finance & Life Analytics</span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Statistical graphs (Bar, Line, Pie, Histogram, Scatter, Polygon), scrollable timelines, and drilldown inspection.
                    </p>
                </div>

                <!-- Domain Tabs (Strict Separation: Normal vs Personal) -->
                @php
                    $isPersonal = ($expenseType ?? 'normal') === 'personal';
                    $filterParams = request()->except('expense_type');
                @endphp
                <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-900 p-1.5 rounded-2xl border border-slate-200/80 dark:border-slate-800 text-xs self-start lg:self-auto shadow-inner">
                    <a href="{{ route('analytics.index', array_merge($filterParams, ['expense_type' => 'normal'])) }}"
                        class="px-4 py-2 rounded-xl font-bold transition flex items-center gap-1.5 {{ ! $isPersonal ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-600 hover:text-indigo-600 dark:text-slate-300' }}">
                        <span>📘</span> Normal Expenses
                    </a>
                    <a href="{{ route('analytics.index', array_merge($filterParams, ['expense_type' => 'personal'])) }}"
                        class="px-4 py-2 rounded-xl font-bold transition flex items-center gap-1.5 {{ $isPersonal ? 'bg-pink-600 text-white shadow-sm shadow-pink-600/30' : 'text-slate-600 hover:text-pink-600 dark:text-slate-300' }}">
                        <span>🛍️</span> Personal Expenses
                    </a>
                </div>
            </div>

            <!-- Filter Controls Form -->
            <form id="analyticsFilterForm" method="GET" action="{{ route('analytics.index') }}" class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-700/60">
                <input type="hidden" name="expense_type" value="{{ $expenseType ?? 'normal' }}">
                <input type="hidden" name="period" id="filter-period-input" value="{{ $period ?? 'month' }}">

                <!-- Primary Presets Capsule Bar -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <!-- Segmented Range Buttons -->
                    <div class="flex items-center flex-wrap gap-1 bg-slate-100/90 dark:bg-slate-900/90 p-1 rounded-2xl border border-slate-200/80 dark:border-slate-800 text-xs shadow-inner">
                        <button type="button" onclick="setPeriodFilter('today')"
                            class="period-pill-btn px-3.5 py-1.5 rounded-xl font-bold transition cursor-pointer {{ ($period ?? '') === 'today' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                            Today
                        </button>
                        <button type="button" onclick="setPeriodFilter('week')"
                            class="period-pill-btn px-3.5 py-1.5 rounded-xl font-bold transition cursor-pointer {{ ($period ?? '') === 'week' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                            Week (7D)
                        </button>
                        <button type="button" onclick="setPeriodFilter('month')"
                            class="period-pill-btn px-3.5 py-1.5 rounded-xl font-bold transition cursor-pointer {{ ($period ?? '') === 'month' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                            Month
                        </button>
                        <button type="button" onclick="setPeriodFilter('multi_month')"
                            class="period-pill-btn px-3.5 py-1.5 rounded-xl font-bold transition cursor-pointer {{ ($period ?? '') === 'multi_month' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                            Multi-Month
                        </button>
                        <button type="button" onclick="setPeriodFilter('year')"
                            class="period-pill-btn px-3.5 py-1.5 rounded-xl font-bold transition cursor-pointer {{ ($period ?? '') === 'year' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                            Year
                        </button>
                        <button type="button" onclick="toggleCustomRangeBox()"
                            class="period-pill-btn px-3.5 py-1.5 rounded-xl font-bold transition cursor-pointer flex items-center gap-1.5 {{ ($period ?? '') === 'custom' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                            <span>🗓️</span> Range
                        </button>
                    </div>

                    <!-- Active Period Display Badge -->
                    <div class="flex items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-700/80 text-slate-700 dark:text-slate-300 font-bold border border-slate-200/60 dark:border-slate-600/60">
                            <span>📅</span>
                            <span>{{ $dateFilter['label'] ?? 'Custom' }}</span>
                            <span class="text-slate-400">({{ $days }} {{ $days === 1 ? 'day' : 'days' }})</span>
                        </span>
                    </div>
                </div>

                <!-- Contextual Sub-Bars (High-Fi Pickers based on active period) -->
                <!-- A. Single Month Selector (when period == 'month') -->
                <div id="subbar-month" class="{{ ($period ?? '') === 'month' ? '' : 'hidden' }} flex items-center flex-wrap gap-2 pt-1 text-xs">
                    @php
                        $currMonthCarbon = \Carbon\Carbon::createFromFormat('Y-m', $dateFilter['month_val'] ?? now()->format('Y-m'));
                        $prevMonthVal = $currMonthCarbon->copy()->subMonth()->format('Y-m');
                        $nextMonthVal = $currMonthCarbon->copy()->addMonth()->format('Y-m');
                    @endphp
                    <div class="inline-flex items-center gap-1 bg-white dark:bg-slate-900 p-1 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xs">
                        <button type="button" onclick="submitMonthFilter('{{ $prevMonthVal }}')" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white transition" title="Previous Month">
                            ‹
                        </button>
                        <div class="relative flex items-center">
                            <input type="month" name="month" id="filter-month-input" value="{{ $dateFilter['month_val'] ?? now()->format('Y-m') }}"
                                onchange="document.getElementById('analyticsFilterForm').submit()"
                                class="px-3 py-1 bg-transparent border-0 font-extrabold text-slate-800 dark:text-slate-100 text-xs cursor-pointer focus:ring-0">
                        </div>
                        <button type="button" onclick="submitMonthFilter('{{ $nextMonthVal }}')" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white transition" title="Next Month">
                            ›
                        </button>
                    </div>

                    <button type="button" onclick="submitMonthFilter('{{ now()->format('Y-m') }}')"
                        class="px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-700/80 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold transition">
                        Current Month
                    </button>
                </div>

                <!-- B. Multi-Month Selector (when period == 'multi_month') -->
                <div id="subbar-multi-month" class="{{ ($period ?? '') === 'multi_month' ? '' : 'hidden' }} flex items-center flex-wrap gap-2 pt-1 text-xs">
                    <span class="font-bold text-slate-400">From:</span>
                    <input type="month" name="from_month" value="{{ $dateFilter['from_month'] ?? now()->subMonths(2)->format('Y-m') }}"
                        class="px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold text-xs text-slate-800 dark:text-white">

                    <span class="font-bold text-slate-400">To:</span>
                    <input type="month" name="to_month" value="{{ $dateFilter['to_month'] ?? now()->format('Y-m') }}"
                        class="px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold text-xs text-slate-800 dark:text-white">

                    <button type="submit" class="px-3 py-1 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition shadow-xs">
                        Apply Months
                    </button>

                    <!-- Quick multi-month chips -->
                    <div class="flex items-center gap-1 ml-auto">
                        <button type="button" onclick="applyQuickMultiMonths(3)" class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                            Last 3 Months
                        </button>
                        <button type="button" onclick="applyQuickMultiMonths(6)" class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                            Last 6 Months
                        </button>
                    </div>
                </div>

                <!-- C. Year Selector (when period == 'year') -->
                <div id="subbar-year" class="{{ ($period ?? '') === 'year' ? '' : 'hidden' }} flex items-center flex-wrap gap-2 pt-1 text-xs">
                    @php $currYear = (int) ($dateFilter['year_val'] ?? now()->year); @endphp
                    <div class="inline-flex items-center gap-1 bg-white dark:bg-slate-900 p-1 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xs">
                        <button type="button" onclick="submitYearFilter({{ $currYear - 1 }})" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white transition">
                            ‹
                        </button>
                        <select name="year" onchange="document.getElementById('analyticsFilterForm').submit()"
                            class="px-3 py-1 bg-transparent border-0 font-extrabold text-slate-800 dark:text-slate-100 text-xs cursor-pointer focus:ring-0">
                            @for($y = now()->year; $y >= 2023; $y--)
                                <option value="{{ $y }}" {{ $currYear === $y ? 'selected' : '' }}>Year {{ $y }}</option>
                            @endfor
                        </select>
                        <button type="button" onclick="submitYearFilter({{ $currYear + 1 }})" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white transition">
                            ›
                        </button>
                    </div>

                    <div class="flex items-center gap-1">
                        @for($y = now()->year; $y >= now()->year - 2; $y--)
                            <button type="button" onclick="submitYearFilter({{ $y }})"
                                class="px-2.5 py-1.5 rounded-xl font-bold transition {{ $currYear === $y ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-700/80 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                                {{ $y }}
                            </button>
                        @endfor
                    </div>
                </div>

                <!-- D. Custom Range Box (when period == 'custom') -->
                <div id="subbar-custom-range" class="{{ ($period ?? '') === 'custom' ? '' : 'hidden' }} flex items-center flex-wrap gap-2 pt-1 text-xs">
                    <span class="font-bold text-slate-400">Start Date:</span>
                    <input type="date" name="from_date" id="custom-from-date" value="{{ $dateFilter['from_date'] ?? now()->subDays(30)->toDateString() }}"
                        class="px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold text-xs text-slate-800 dark:text-white">

                    <span class="font-bold text-slate-400">End Date:</span>
                    <input type="date" name="to_date" id="custom-to-date" value="{{ $dateFilter['to_date'] ?? now()->toDateString() }}"
                        class="px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold text-xs text-slate-800 dark:text-white">

                    <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition shadow-xs">
                        Apply Range
                    </button>

                    <div class="flex items-center gap-1 ml-auto">
                        <button type="button" onclick="setCustomQuickDays(14)" class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                            Last 14D
                        </button>
                        <button type="button" onclick="setCustomQuickDays(30)" class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                            Last 30D
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 2. KPI SUMMARY CARDS -->
        @php
            $topCategory = $categorySpending->first();
            $dailyAverage = $days > 0 ? round($totalPeriodSpending / $days, 2) : 0;
        @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 sm:p-5 rounded-3xl bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total {{ $isPersonal ? 'Personal' : 'Normal' }} Spend</span>
                <div class="text-2xl font-black {{ $isPersonal ? 'text-pink-600 dark:text-pink-400' : 'text-indigo-600 dark:text-indigo-400' }} mt-1">
                    ₹{{ number_format($totalPeriodSpending, 2) }}
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ $dateFilter['label'] }}</p>
            </div>

            <div class="p-4 sm:p-5 rounded-3xl bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Daily Average</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                    ₹{{ number_format($dailyAverage, 2) }}
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Average spend per day</p>
            </div>

            <div class="p-4 sm:p-5 rounded-3xl bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Top Category</span>
                <div class="text-xl font-bold text-slate-900 dark:text-white mt-1 truncate" title="{{ $topCategory?->name ?? 'None' }}">
                    {{ $topCategory ? $topCategory->icon . ' ' . $topCategory->name : 'None' }}
                </div>
                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5 font-bold">
                    {{ $topCategory ? '₹' . number_format($topCategory->total, 2) . ' (' . $topCategory->percentage . '%)' : 'No spending' }}
                </p>
            </div>

            <div class="p-4 sm:p-5 rounded-3xl bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Transactions</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                    {{ number_format($totalTransactionsCount) }}
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ $categorySpending->count() }} active categories</p>
            </div>
        </div>

        <!-- 3. ALL STATISTICAL GRAPHS (From Reference Guide + Export Feature) -->
        <!-- SECTION TITLE -->
        <div class="flex items-center justify-between pt-2">
            <div>
                <h2 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📈</span>
                    <span>Statistical Analysis Suite</span>
                </h2>
                <p class="text-xs text-slate-500">6 Statistical Graph Types + Spread & Cumulative. Click elements to drill down, or export each as image.</p>
            </div>

            <button type="button" onclick="openExpenseGroupModal('{{ $expenseType }}', null, 'All Expenses in Period', null, '{{ $isPersonal ? '#ec4899' : '#6366f1' }}')"
                class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:text-slate-900 text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                <span>🔍</span>
                <span>View All ({{ $totalTransactionsCount }})</span>
            </button>
        </div>

        <!-- ROW A: 1. BAR GRAPH & 2. LINE GRAPH -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 1. BAR GRAPH: Compare Categories -->
            <div class="bg-white dark:bg-slate-800/90 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold">1</span>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Bar Graph (Compare Categories)</h3>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Ranked category spending comparison. Click bar to inspect group.</p>
                    </div>
                    <button type="button" onclick="exportChartAsImage('categoryBarChart', 'Bar_Graph_Compare_Categories')"
                        class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-[11px] font-bold text-slate-700 dark:text-slate-200 transition flex items-center gap-1" title="Export as PNG">
                        <span>📷</span> Export Image
                    </button>
                </div>

                @if($categorySpending->isEmpty())
                    <div class="h-64 flex flex-col items-center justify-center text-slate-400 text-xs">
                        <span>No category data recorded in this period.</span>
                    </div>
                @else
                    <div class="h-64 w-full relative">
                        <canvas id="categoryBarChart" class="cursor-pointer"></canvas>
                    </div>
                @endif
            </div>

            <!-- 2. LINE GRAPH: Show Trends Over Time -->
            <div class="bg-white dark:bg-slate-800/90 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-pink-50 dark:bg-pink-950/60 text-pink-600 dark:text-pink-400 flex items-center justify-center text-xs font-bold">2</span>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Line Graph (Trends Over Time)</h3>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Continuous trajectory and daily spikes. Click node to drilldown.</p>
                    </div>
                    <button type="button" onclick="exportChartAsImage('dailyTrendLineChart', 'Line_Graph_Trends_Over_Time')"
                        class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-[11px] font-bold text-slate-700 dark:text-slate-200 transition flex items-center gap-1" title="Export as PNG">
                        <span>📷</span> Export Image
                    </button>
                </div>

                @if($expensesByDay->isEmpty())
                    <div class="h-64 flex flex-col items-center justify-center text-slate-400 text-xs">
                        <span>No timeline data recorded in this period.</span>
                    </div>
                @else
                    <div class="h-64 w-full relative">
                        <canvas id="dailyTrendLineChart" class="cursor-pointer"></canvas>
                    </div>
                @endif
            </div>
        </div>

        <!-- ROW B: 3. PIE CHART & 4. HISTOGRAM -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 3. PIE CHART: Show Proportions -->
            <div class="bg-white dark:bg-slate-800/90 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xs font-bold">3</span>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Pie Chart (Show Proportions)</h3>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Relative share (%) of total spending. Click slice to inspect.</p>
                    </div>
                    <button type="button" onclick="exportChartAsImage('categoryPieChart', 'Pie_Chart_Proportions')"
                        class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-[11px] font-bold text-slate-700 dark:text-slate-200 transition flex items-center gap-1" title="Export as PNG">
                        <span>📷</span> Export Image
                    </button>
                </div>

                @if($categorySpending->isEmpty())
                    <div class="h-64 flex flex-col items-center justify-center text-slate-400 text-xs">
                        <span>No category data recorded in this period.</span>
                    </div>
                @else
                    <div class="h-64 w-full relative">
                        <canvas id="categoryPieChart" class="cursor-pointer"></canvas>
                    </div>
                @endif
            </div>

            <!-- 4. HISTOGRAM: Continuous Data / Price Brackets -->
            <div class="bg-white dark:bg-slate-800/90 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xs font-bold">4</span>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Histogram (Continuous Data)</h3>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Distribution across amount brackets. Click bin to see expenses in that range.</p>
                    </div>
                    <button type="button" onclick="exportChartAsImage('histogramChart', 'Histogram_Continuous_Distribution')"
                        class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-[11px] font-bold text-slate-700 dark:text-slate-200 transition flex items-center gap-1" title="Export as PNG">
                        <span>📷</span> Export Image
                    </button>
                </div>

                @if($totalTransactionsCount === 0)
                    <div class="h-64 flex flex-col items-center justify-center text-slate-400 text-xs">
                        <span>No transactions recorded to build histogram.</span>
                    </div>
                @else
                    <div class="h-64 w-full relative">
                        <canvas id="histogramChart" class="cursor-pointer"></canvas>
                    </div>
                @endif
            </div>
        </div>

        <!-- ROW C: 5. SCATTER PLOT & 6. FREQUENCY POLYGON -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 5. SCATTER PLOT: Relationship Between Variables -->
            <div class="bg-white dark:bg-slate-800/90 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-xs font-bold">5</span>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Scatter Plot (Transaction Scatter)</h3>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">X = Day of period, Y = Amount ₹. Spot clusters & outliers.</p>
                    </div>
                    <button type="button" onclick="exportChartAsImage('scatterPlotChart', 'Scatter_Plot_Transactions')"
                        class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-[11px] font-bold text-slate-700 dark:text-slate-200 transition flex items-center gap-1" title="Export as PNG">
                        <span>📷</span> Export Image
                    </button>
                </div>

                @if(empty($scatterPoints))
                    <div class="h-64 flex flex-col items-center justify-center text-slate-400 text-xs">
                        <span>No individual transaction points recorded.</span>
                    </div>
                @else
                    <div class="h-64 w-full relative">
                        <canvas id="scatterPlotChart" class="cursor-pointer"></canvas>
                    </div>
                @endif
            </div>

            <!-- 6. FREQUENCY POLYGON: Shape of Distribution -->
            <div class="bg-white dark:bg-slate-800/90 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xs font-bold">6</span>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Frequency Polygon (Shape of Distribution)</h3>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Polygon outline of bracket counts showing distribution skewness.</p>
                    </div>
                    <button type="button" onclick="exportChartAsImage('frequencyPolygonChart', 'Frequency_Polygon_Distribution')"
                        class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-[11px] font-bold text-slate-700 dark:text-slate-200 transition flex items-center gap-1" title="Export as PNG">
                        <span>📷</span> Export Image
                    </button>
                </div>

                @if($totalTransactionsCount === 0)
                    <div class="h-64 flex flex-col items-center justify-center text-slate-400 text-xs">
                        <span>No frequency distribution data.</span>
                    </div>
                @else
                    <div class="h-64 w-full relative">
                        <canvas id="frequencyPolygonChart" class="cursor-pointer"></canvas>
                    </div>
                @endif
            </div>
        </div>

        <!-- ROW D: 7. BOX PLOT (SPREAD METRICS) & 8. CUMULATIVE AREA GRAPH -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 7. SPREAD / BOX PLOT METRICS -->
            <div class="bg-white dark:bg-slate-800/90 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-orange-50 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 flex items-center justify-center text-xs font-bold">📦</span>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">Spread & Quartiles (Box Plot Summary)</h3>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                        Dispersion
                    </span>
                </div>

                <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 pt-2">
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700 text-center">
                        <span class="text-[10px] font-bold text-slate-400 block uppercase">Min</span>
                        <span class="text-sm font-black text-slate-900 dark:text-white">₹{{ number_format($boxPlotStats['min'] ?? 0, 0) }}</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700 text-center">
                        <span class="text-[10px] font-bold text-indigo-500 block uppercase">Q1 (25%)</span>
                        <span class="text-sm font-black text-slate-900 dark:text-white">₹{{ number_format($boxPlotStats['q1'] ?? 0, 0) }}</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-100 dark:border-indigo-800 text-center">
                        <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 block uppercase">Median</span>
                        <span class="text-sm font-black text-indigo-600 dark:text-indigo-400">₹{{ number_format($boxPlotStats['median'] ?? 0, 0) }}</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700 text-center">
                        <span class="text-[10px] font-bold text-slate-400 block uppercase">Avg</span>
                        <span class="text-sm font-black text-slate-900 dark:text-white">₹{{ number_format($boxPlotStats['avg'] ?? 0, 0) }}</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700 text-center">
                        <span class="text-[10px] font-bold text-pink-500 block uppercase">Q3 (75%)</span>
                        <span class="text-sm font-black text-slate-900 dark:text-white">₹{{ number_format($boxPlotStats['q3'] ?? 0, 0) }}</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700 text-center">
                        <span class="text-[10px] font-bold text-slate-400 block uppercase">Max</span>
                        <span class="text-sm font-black text-slate-900 dark:text-white">₹{{ number_format($boxPlotStats['max'] ?? 0, 0) }}</span>
                    </div>
                </div>

                <!-- Visual Spread Bar -->
                @php
                    $maxVal = max(1, $boxPlotStats['max'] ?? 1);
                    $q1Pct = min(100, max(0, (($boxPlotStats['q1'] ?? 0) / $maxVal) * 100));
                    $medPct = min(100, max(0, (($boxPlotStats['median'] ?? 0) / $maxVal) * 100));
                    $q3Pct = min(100, max(0, (($boxPlotStats['q3'] ?? 0) / $maxVal) * 100));
                @endphp
                <div class="pt-3 space-y-1.5">
                    <div class="flex justify-between text-[11px] text-slate-400">
                        <span>Min: ₹{{ number_format($boxPlotStats['min'] ?? 0) }}</span>
                        <span>Median: ₹{{ number_format($boxPlotStats['median'] ?? 0) }}</span>
                        <span>Max: ₹{{ number_format($boxPlotStats['max'] ?? 0) }}</span>
                    </div>
                    <div class="relative h-6 bg-slate-100 dark:bg-slate-700 rounded-xl overflow-hidden flex items-center">
                        <!-- IQR Box -->
                        <div class="absolute h-full bg-gradient-to-r from-indigo-400 to-purple-500 opacity-60 rounded-lg"
                            style="left: {{ $q1Pct }}%; width: {{ max(2, $q3Pct - $q1Pct) }}%;"></div>
                        <!-- Median Marker -->
                        <div class="absolute h-full w-1 bg-white shadow-md z-10" style="left: {{ $medPct }}%;"></div>
                    </div>
                    <p class="text-[10px] text-slate-400 text-center">50% of your transactions fall inside the shaded IQR box (₹{{ number_format($boxPlotStats['q1'] ?? 0) }} to ₹{{ number_format($boxPlotStats['q3'] ?? 0) }}).</p>
                </div>
            </div>

            <!-- 8. CUMULATIVE AREA GRAPH -->
            <div class="bg-white dark:bg-slate-800/90 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center text-xs font-bold">8</span>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Cumulative Spending (Area Graph)</h3>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Running total accumulation curve reaching period spend.</p>
                    </div>
                    <button type="button" onclick="exportChartAsImage('cumulativeAreaChart', 'Cumulative_Spending_Area_Graph')"
                        class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-[11px] font-bold text-slate-700 dark:text-slate-200 transition flex items-center gap-1" title="Export as PNG">
                        <span>📷</span> Export Image
                    </button>
                </div>

                @if(empty($cumulativeTimeline))
                    <div class="h-64 flex flex-col items-center justify-center text-slate-400 text-xs">
                        <span>No accumulation data in this period.</span>
                    </div>
                @else
                    <div class="h-64 w-full relative">
                        <canvas id="cumulativeAreaChart" class="cursor-pointer"></canvas>
                    </div>
                @endif
            </div>
        </div>

        <!-- 4. SCROLLABLE DAILY SPENDING TIMELINE (BAR GRAPH) -->
        <div class="bg-white dark:bg-slate-800/90 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                        <span>📅</span>
                        <span>Daily Spending Timeline (Scrollable Bar Graph)</span>
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Scroll horizontally on mobile or wide ranges. Bars never overlap. Click any bar to inspect that day!
                    </p>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 text-[11px] text-slate-600 dark:text-slate-300">
                        <span>↔️</span> Horizontal Scroll
                    </span>
                    <button type="button" onclick="exportChartAsImage('dailyExpenseBarChart', 'Daily_Spending_Timeline_Scrollable')"
                        class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-[11px] font-bold text-slate-700 dark:text-slate-200 transition flex items-center gap-1" title="Export as PNG">
                        <span>📷</span> Export Image
                    </button>
                </div>
            </div>

            <!-- Scrollable Bar Graph Wrapper -->
            @php
                $dayCount = count($expensesByDay);
                $minWidthPx = max(700, $dayCount * 38);
            @endphp
            @if($dayCount === 0)
                <div class="h-56 flex flex-col items-center justify-center text-slate-400 text-xs">
                    <span>No daily transactions in this time window.</span>
                </div>
            @else
                <div class="overflow-x-auto pb-3 pt-1 scrollbar-thin scrollbar-thumb-slate-200 dark:scrollbar-thumb-slate-700">
                    <div style="min-width: {{ $minWidthPx }}px; height: 260px;" class="relative">
                        <canvas id="dailyExpenseBarChart" class="cursor-pointer"></canvas>
                    </div>
                </div>
            @endif
        </div>

        <!-- 5. DETAILED CATEGORY REPORT TABLE -->
        <div class="bg-white dark:bg-slate-800/90 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-700/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                        <span>📑</span>
                        <span>{{ $isPersonal ? 'Personal ' : 'Normal ' }}Category Summary Report</span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Breakdown with percentage share, average spend, and instant drilldown group inspection.
                    </p>
                </div>
                <span class="text-xs font-semibold px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 self-start sm:self-auto">
                    {{ $categorySpending->count() }} Categories Active
                </span>
            </div>

            @if($categorySpending->isEmpty())
                <div class="p-12 text-center text-slate-400 text-xs">
                    No expense categories recorded for this time window.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100 dark:border-slate-700">
                            <tr>
                                <th class="py-3 px-4">Rank & Category</th>
                                <th class="py-3 px-4">Total Amount (₹)</th>
                                <th class="py-3 px-4 min-w-[140px]">% Share</th>
                                <th class="py-3 px-4 text-center">Transactions</th>
                                <th class="py-3 px-4">Avg / Item</th>
                                <th class="py-3 px-4 text-right">Drilldown Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-medium">
                            @foreach($categorySpending as $idx => $cat)
                                <tr class="hover:bg-indigo-50/50 dark:hover:bg-slate-700/40 transition cursor-pointer group"
                                    onclick="openExpenseGroupModal('{{ $expenseType }}', {{ $cat->category_id }}, '{{ addslashes($cat->name) }}', null, '{{ $cat->color }}')">
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold text-slate-400 group-hover:text-indigo-600">
                                                #{{ $idx + 1 }}
                                            </span>
                                            <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $cat->color }}"></span>
                                            <span class="font-bold text-slate-900 dark:text-white text-sm">
                                                {{ $cat->icon ?? '🏷️' }} {{ $cat->name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white text-sm">
                                        ₹{{ number_format($cat->total, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-2 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-500"
                                                    style="width: {{ $cat->percentage }}%; background-color: {{ $cat->color }}"></div>
                                            </div>
                                            <span class="text-xs font-bold w-12 text-right">{{ $cat->percentage }}%</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-bold text-slate-800 dark:text-slate-200">
                                        {{ $cat->count }}
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-500">
                                        ₹{{ number_format($cat->average, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button"
                                            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-indigo-600 hover:text-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold transition cursor-pointer inline-flex items-center gap-1.5 shadow-xs">
                                            <span>🔍</span>
                                            <span>View Group ({{ $cat->count }})</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-slate-800/80 font-bold text-slate-900 dark:text-white border-t border-slate-200 dark:border-slate-700">
                            <tr>
                                <td class="py-3 px-4">Total Period Spend</td>
                                <td class="py-3 px-4 text-sm font-extrabold {{ $isPersonal ? 'text-pink-600 dark:text-pink-400' : 'text-indigo-600 dark:text-indigo-400' }}">
                                    ₹{{ number_format($totalPeriodSpending, 2) }}
                                </td>
                                <td class="py-3 px-4">100%</td>
                                <td class="py-3 px-4 text-center">{{ $totalTransactionsCount }}</td>
                                <td class="py-3 px-4">₹{{ $totalTransactionsCount > 0 ? number_format($totalPeriodSpending / $totalTransactionsCount, 2) : '0.00' }}</td>
                                <td class="py-3 px-4 text-right"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

        <!-- 6. LIFESTYLE TRENDS (SCOOTER & SLEEP) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-2">
            <!-- Scooter Travel Distance -->
            <div class="bg-white dark:bg-slate-800/90 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                        <span>🛵</span>
                        <span>Scooter Travel Distance (km)</span>
                    </h3>
                    <button type="button" onclick="exportChartAsImage('scooterChart', 'Scooter_Travel_Distance')"
                        class="px-2 py-0.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-[10px] font-bold text-slate-700 dark:text-slate-200 transition">
                        📷 Export
                    </button>
                </div>
                <div class="h-56 w-full">
                    <canvas id="scooterChart"></canvas>
                </div>
            </div>

            <!-- Sleep & Well-being Duration -->
            <div class="bg-white dark:bg-slate-800/90 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                        <span>💤</span>
                        <span>Sleep & Well-being Trends</span>
                    </h3>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-indigo-500 font-semibold">Avg: {{ $avgSleepDuration }}h &bull; {{ $avgDayRating }}/5</span>
                        <button type="button" onclick="exportChartAsImage('sleepChart', 'Sleep_Wellbeing_Trends')"
                            class="px-2 py-0.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-[10px] font-bold text-slate-700 dark:text-slate-200 transition">
                            📷 Export
                        </button>
                    </div>
                </div>
                <div class="h-56 w-full">
                    <canvas id="sleepChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- UNIVERSAL GROUP OF EXPENSES DRILLDOWN MODAL -->
    <div id="expense-group-modal" class="hidden fixed inset-0 z-50 bg-black/75 backdrop-blur-md flex items-center justify-center p-3 sm:p-6 overflow-y-auto" onclick="if(event.target === this) closeExpenseGroupModal()">
        <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[90vh] overflow-hidden my-6 animate-in fade-in zoom-in-95 duration-200" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-900/90">
                <div class="flex items-center gap-3 min-w-0 pr-4">
                    <div id="modal-category-color-bar" class="w-3.5 h-10 rounded-full bg-indigo-600 shrink-0"></div>
                    <div class="min-w-0">
                        <h3 id="modal-group-title" class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white truncate">
                            Expense Group
                        </h3>
                        <p id="modal-group-subtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Loading transactions...
                        </p>
                    </div>
                </div>

                <button type="button" onclick="closeExpenseGroupModal()" class="p-2 rounded-xl bg-slate-200/60 dark:bg-slate-800 hover:bg-slate-200 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition cursor-pointer" title="Close modal">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Filter / Search Row inside Modal -->
            <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center gap-3">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 text-xs">🔍</span>
                    <input type="text" id="modal-expense-filter" placeholder="Filter group by description, paid by, notes..."
                        class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div id="modal-total-badge" class="px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-extrabold text-xs whitespace-nowrap">
                    ₹0.00
                </div>
            </div>

            <!-- Category breakdown for the clicked bar / point / slice (click a chip to filter) -->
            <div id="modal-category-chips" class="hidden px-5 py-3 border-b border-slate-100 dark:border-slate-800 flex flex-wrap gap-1.5"></div>

            <!-- Modal Content List -->
            <div id="modal-expenses-list" class="p-4 sm:p-6 overflow-y-auto space-y-2.5 flex-1 divide-y divide-slate-100 dark:divide-slate-800/80">
                <div class="py-12 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-2">
                    <span class="animate-spin text-2xl">⏳</span>
                    <span>Fetching group details...</span>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/90 flex items-center justify-between text-xs">
                <span id="modal-item-count" class="text-slate-500">0 items found</span>
                <button type="button" onclick="closeExpenseGroupModal()"
                    class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold transition cursor-pointer">
                    Done
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT: CHARTS, DRILLDOWNS, EXPORTS, AND DYNAMIC FILTERS -->
    <script>
        let currentGroupExpenses = [];

        // Global Chart Image Exporter (exports crisp PNG with background)
        function exportChartAsImage(canvasId, fileName = 'chart') {
            const canvas = document.getElementById(canvasId);
            if (!canvas) {
                if (window.showToast) window.showToast('Chart not ready for export', 'error');
                return;
            }
            try {
                const isDark = document.documentElement.classList.contains('dark');
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = canvas.width;
                tempCanvas.height = canvas.height;
                const ctx = tempCanvas.getContext('2d');

                // Fill background color
                ctx.fillStyle = isDark ? '#0f172a' : '#ffffff';
                ctx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                ctx.drawImage(canvas, 0, 0);

                const link = document.createElement('a');
                link.download = `${fileName}.png`;
                link.href = tempCanvas.toDataURL('image/png');
                link.click();
                if (window.showToast) window.showToast(`Exported ${fileName}.png!`, 'success');
            } catch (err) {
                console.error('Export failed:', err);
                if (window.showToast) window.showToast('Could not export chart image', 'error');
            }
        }

        // Date Filter Helpers
        function setPeriodFilter(period) {
            document.getElementById('filter-period-input').value = period;
            if (period === 'today' || period === 'week') {
                document.getElementById('analyticsFilterForm').submit();
            } else if (period === 'month') {
                toggleSubbar('month');
            } else if (period === 'multi_month') {
                toggleSubbar('multi_month');
            } else if (period === 'year') {
                toggleSubbar('year');
            }
        }

        function toggleCustomRangeBox() {
            document.getElementById('filter-period-input').value = 'custom';
            toggleSubbar('custom');
        }

        function toggleSubbar(type) {
            ['month', 'multi-month', 'year', 'custom-range'].forEach(s => {
                const el = document.getElementById(`subbar-${s}`);
                if (el) el.classList.add('hidden');
            });
            const activeEl = document.getElementById(type === 'custom' ? 'subbar-custom-range' : (type === 'multi_month' ? 'subbar-multi-month' : `subbar-${type}`));
            if (activeEl) activeEl.classList.remove('hidden');
        }

        function submitMonthFilter(monthVal) {
            document.getElementById('filter-period-input').value = 'month';
            document.getElementById('filter-month-input').value = monthVal;
            document.getElementById('analyticsFilterForm').submit();
        }

        function submitYearFilter(yearVal) {
            document.getElementById('filter-period-input').value = 'year';
            let yearSelect = document.querySelector('select[name="year"]');
            if (yearSelect) yearSelect.value = yearVal;
            document.getElementById('analyticsFilterForm').submit();
        }

        function applyQuickMultiMonths(monthCount) {
            const today = new Date();
            const toMonth = today.toISOString().slice(0, 7);
            const startD = new Date(today.getFullYear(), today.getMonth() - (monthCount - 1), 1);
            const fromMonth = startD.toISOString().slice(0, 7);
            document.querySelector('input[name="from_month"]').value = fromMonth;
            document.querySelector('input[name="to_month"]').value = toMonth;
            document.getElementById('analyticsFilterForm').submit();
        }

        function setCustomQuickDays(numDays) {
            const today = new Date();
            const toDate = today.toISOString().slice(0, 10);
            const past = new Date(today);
            past.setDate(past.getDate() - numDays);
            const fromDate = past.toISOString().slice(0, 10);
            document.getElementById('custom-from-date').value = fromDate;
            document.getElementById('custom-to-date').value = toDate;
            document.getElementById('analyticsFilterForm').submit();
        }

        // Initialize All Charts
        document.addEventListener('DOMContentLoaded', function () {
            const catData = @json($categorySpending);
            const dailyData = @json($expensesByDay);
            const histogramBins = @json($histogramData);
            const scatterPoints = @json($scatterPoints);
            const cumulativeData = @json($cumulativeTimeline);
            const expenseType = @json($expenseType ?? 'normal');
            const defaultColors = ['#6366f1', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#06b6d4', '#14b8a6', '#f97316', '#64748b'];

            // 1. BAR GRAPH: Compare Categories
            const barCtx = document.getElementById('categoryBarChart');
            if (barCtx && catData.length > 0) {
                new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: catData.map(c => c.name),
                        datasets: [{
                            label: 'Spend (₹)',
                            data: catData.map(c => c.total),
                            backgroundColor: catData.map((c, i) => c.color || defaultColors[i % defaultColors.length]),
                            borderRadius: 8,
                            maxBarThickness: 36
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ` Total: ₹${Number(ctx.raw).toLocaleString()} (${catData[ctx.dataIndex]?.percentage}%)`
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: val => '₹' + Number(val).toLocaleString(), font: { size: 10 } },
                                grid: { color: 'rgba(148, 163, 184, 0.1)' }
                            },
                            x: {
                                ticks: { font: { size: 11, weight: '600' } },
                                grid: { display: false }
                            }
                        },
                        onClick: function (evt, elements) {
                            if (elements && elements.length > 0) {
                                const index = elements[0].index;
                                const clickedCat = catData[index];
                                if (clickedCat) {
                                    openExpenseGroupModal(expenseType, clickedCat.category_id, clickedCat.name, null, clickedCat.color);
                                }
                            }
                        }
                    }
                });
            }

            // 2. LINE GRAPH: Trends Over Time
            const lineCtx = document.getElementById('dailyTrendLineChart');
            if (lineCtx && dailyData.length > 0) {
                new Chart(lineCtx, {
                    type: 'line',
                    data: {
                        labels: dailyData.map(d => {
                            const p = d.date.split('-');
                            return p.length === 3 ? `${p[2]}/${p[1]}` : d.date;
                        }),
                        datasets: [{
                            label: 'Daily Spend (₹)',
                            data: dailyData.map(d => Number(d.total)),
                            borderColor: expenseType === 'personal' ? '#ec4899' : '#6366f1',
                            backgroundColor: expenseType === 'personal' ? 'rgba(236, 72, 153, 0.12)' : 'rgba(99, 102, 241, 0.12)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2.5,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: expenseType === 'personal' ? '#ec4899' : '#6366f1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: items => dailyData[items[0].dataIndex]?.date || '',
                                    label: ctx => ` Spend: ₹${Number(ctx.raw).toLocaleString()} (${dailyData[ctx.dataIndex]?.count} items)`
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: val => '₹' + Number(val).toLocaleString(), font: { size: 10 } },
                                grid: { color: 'rgba(148, 163, 184, 0.1)' }
                            },
                            x: {
                                ticks: { font: { size: 10, weight: '600' } },
                                grid: { display: false }
                            }
                        },
                        onClick: function (evt, elements) {
                            if (elements && elements.length > 0) {
                                const index = elements[0].index;
                                const clickedDay = dailyData[index];
                                if (clickedDay) {
                                    openExpenseGroupModal(expenseType, null, null, clickedDay.date, expenseType === 'personal' ? '#ec4899' : '#6366f1');
                                }
                            }
                        }
                    }
                });
            }

            // 3. PIE / DONUT CHART: Show Proportions
            const pieCtx = document.getElementById('categoryPieChart');
            if (pieCtx && catData.length > 0) {
                new Chart(pieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: catData.map(c => c.name),
                        datasets: [{
                            data: catData.map(c => c.total),
                            backgroundColor: catData.map((c, i) => c.color || defaultColors[i % defaultColors.length]),
                            borderWidth: 2,
                            borderColor: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    font: { size: 11, weight: 'bold' },
                                    color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (ctx) {
                                        const val = ctx.raw || 0;
                                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                        const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                        return ` ${ctx.label}: ₹${Number(val).toLocaleString()} (${pct}%)`;
                                    }
                                }
                            }
                        },
                        onClick: function (evt, elements) {
                            if (elements && elements.length > 0) {
                                const index = elements[0].index;
                                const clickedCat = catData[index];
                                if (clickedCat) {
                                    openExpenseGroupModal(expenseType, clickedCat.category_id, clickedCat.name, null, clickedCat.color);
                                }
                            }
                        }
                    }
                });
            }

            // 4. HISTOGRAM: Continuous Data (Price Buckets)
            const histCtx = document.getElementById('histogramChart');
            if (histCtx && histogramBins.length > 0) {
                new Chart(histCtx, {
                    type: 'bar',
                    data: {
                        labels: histogramBins.map(b => b.label),
                        datasets: [{
                            label: 'Transactions Count',
                            data: histogramBins.map(b => b.count),
                            backgroundColor: '#10b981',
                            borderRadius: 6,
                            maxBarThickness: 34
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => {
                                        const bin = histogramBins[ctx.dataIndex];
                                        return [` Count: ${ctx.raw} transactions`, ` Total: ₹${bin?.total.toLocaleString()}`];
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0, font: { size: 10 } },
                                grid: { color: 'rgba(148, 163, 184, 0.1)' }
                            },
                            x: {
                                ticks: { font: { size: 10, weight: '600' } },
                                grid: { display: false }
                            }
                        },
                        onClick: function (evt, elements) {
                            if (elements && elements.length > 0) {
                                const index = elements[0].index;
                                const bin = histogramBins[index];
                                if (bin) {
                                    openExpenseGroupModal(expenseType, null, `Bracket: ${bin.label}`, null, '#10b981', bin.min, bin.max);
                                }
                            }
                        }
                    }
                });
            }

            // 5. SCATTER PLOT: Transaction Scatter
            const scatCtx = document.getElementById('scatterPlotChart');
            if (scatCtx && scatterPoints.length > 0) {
                new Chart(scatCtx, {
                    type: 'scatter',
                    data: {
                        datasets: [{
                            label: 'Expenses',
                            data: scatterPoints.map(p => ({ x: p.x, y: p.y, raw: p })),
                            backgroundColor: scatterPoints.map(p => p.color || '#3b82f6'),
                            borderColor: '#ffffff',
                            borderWidth: 1,
                            pointRadius: 6,
                            pointHoverRadius: 9
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        const r = ctx.raw.raw;
                                        return [` ${r.description}: ₹${Number(r.y).toLocaleString()}`, ` Date: ${r.date} (${r.category})`];
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                title: { display: true, text: 'Day in Selected Period', font: { size: 10, weight: 'bold' } },
                                grid: { color: 'rgba(148, 163, 184, 0.1)' }
                            },
                            y: {
                                title: { display: true, text: 'Amount (₹)', font: { size: 10, weight: 'bold' } },
                                beginAtZero: true,
                                ticks: { callback: val => '₹' + Number(val).toLocaleString(), font: { size: 10 } },
                                grid: { color: 'rgba(148, 163, 184, 0.1)' }
                            }
                        },
                        onClick: function (evt, elements) {
                            if (elements && elements.length > 0) {
                                const idx = elements[0].index;
                                const p = scatterPoints[idx];
                                if (p && p.raw_date) {
                                    openExpenseGroupModal(expenseType, null, null, p.raw_date, p.color);
                                }
                            }
                        }
                    }
                });
            }

            // 6. FREQUENCY POLYGON: Shape of Distribution
            const polyCtx = document.getElementById('frequencyPolygonChart');
            if (polyCtx && histogramBins.length > 0) {
                new Chart(polyCtx, {
                    type: 'line',
                    data: {
                        labels: histogramBins.map(b => b.label),
                        datasets: [{
                            label: 'Frequency',
                            data: histogramBins.map(b => b.count),
                            borderColor: '#a855f7',
                            backgroundColor: 'rgba(168, 85, 247, 0.15)',
                            fill: true,
                            tension: 0.25,
                            borderWidth: 2.5,
                            pointRadius: 5,
                            pointHoverRadius: 8,
                            pointBackgroundColor: '#a855f7'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ` Frequency: ${ctx.raw} transactions in ${histogramBins[ctx.dataIndex]?.label}`
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0, font: { size: 10 } },
                                grid: { color: 'rgba(148, 163, 184, 0.1)' }
                            },
                            x: {
                                ticks: { font: { size: 10, weight: '600' } },
                                grid: { display: false }
                            }
                        },
                        onClick: function (evt, elements) {
                            if (elements && elements.length > 0) {
                                const index = elements[0].index;
                                const bin = histogramBins[index];
                                if (bin) {
                                    openExpenseGroupModal(expenseType, null, `Bracket: ${bin.label}`, null, '#a855f7', bin.min, bin.max);
                                }
                            }
                        }
                    }
                });
            }

            // 8. CUMULATIVE AREA GRAPH
            const cumCtx = document.getElementById('cumulativeAreaChart');
            if (cumCtx && cumulativeData.length > 0) {
                new Chart(cumCtx, {
                    type: 'line',
                    data: {
                        labels: cumulativeData.map(c => c.date),
                        datasets: [{
                            label: 'Cumulative Spend (₹)',
                            data: cumulativeData.map(c => c.cumulative),
                            borderColor: '#0d9488',
                            backgroundColor: 'rgba(13, 148, 136, 0.15)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#0d9488'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: items => cumulativeData[items[0].dataIndex]?.raw_date || '',
                                    label: ctx => [
                                        ` Cumulative: ₹${Number(ctx.raw).toLocaleString()}`,
                                        ` Daily: ₹${Number(cumulativeData[ctx.dataIndex]?.daily).toLocaleString()}`
                                    ]
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: val => '₹' + Number(val).toLocaleString(), font: { size: 10 } },
                                grid: { color: 'rgba(148, 163, 184, 0.1)' }
                            },
                            x: {
                                ticks: { font: { size: 10, weight: '600' } },
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            // 9. SCROLLABLE DAILY SPENDING BAR GRAPH
            const dailyCtx = document.getElementById('dailyExpenseBarChart');
            if (dailyCtx && dailyData.length > 0) {
                new Chart(dailyCtx, {
                    type: 'bar',
                    data: {
                        labels: dailyData.map(d => {
                            const p = d.date.split('-');
                            return p.length === 3 ? `${p[2]}/${p[1]}` : d.date;
                        }),
                        datasets: [{
                            label: 'Daily Spend (₹)',
                            data: dailyData.map(d => Number(d.total)),
                            backgroundColor: expenseType === 'personal' ? '#ec4899' : '#6366f1',
                            borderRadius: 6,
                            maxBarThickness: 26
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: items => dailyData[items[0].dataIndex]?.date || '',
                                    label: ctx => [
                                        ` Spend: ₹${Number(ctx.raw).toLocaleString()}`,
                                        ` Items: ${dailyData[ctx.dataIndex]?.count || 0}`
                                    ]
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: val => '₹' + Number(val).toLocaleString(), font: { size: 10 } },
                                grid: { color: 'rgba(148, 163, 184, 0.1)' }
                            },
                            x: {
                                ticks: { font: { size: 10, weight: '600' }, autoSkip: false },
                                grid: { display: false }
                            }
                        },
                        onClick: function (evt, elements) {
                            if (elements && elements.length > 0) {
                                const index = elements[0].index;
                                const clickedDay = dailyData[index];
                                if (clickedDay) {
                                    openExpenseGroupModal(expenseType, null, null, clickedDay.date, expenseType === 'personal' ? '#ec4899' : '#6366f1');
                                }
                            }
                        }
                    }
                });
            }

            // Scooter Distance Bar Graph
            const sCtx = document.getElementById('scooterChart');
            if (sCtx) {
                const tripData = @json($scooterDistanceByDay);
                new Chart(sCtx, {
                    type: 'bar',
                    data: {
                        labels: tripData.map(t => t.date.slice(5)),
                        datasets: [{
                            label: 'Distance (km)',
                            data: tripData.map(t => t.total_km),
                            backgroundColor: '#06b6d4',
                            borderRadius: 6,
                            maxBarThickness: 24
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            // Sleep Duration Line Chart
            const slCtx = document.getElementById('sleepChart');
            if (slCtx) {
                const sleepData = @json($sleepRecords);
                new Chart(slCtx, {
                    type: 'line',
                    data: {
                        labels: sleepData.map(s => s.record_date.slice(5)),
                        datasets: [{
                            label: 'Sleep Hours',
                            data: sleepData.map(s => s.sleep_duration_hours || 0),
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.15)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            // Live filter input in Drilldown Modal
            const filterInput = document.getElementById('modal-expense-filter');
            if (filterInput) {
                filterInput.addEventListener('input', function (e) {
                    const q = e.target.value.toLowerCase().trim();
                    renderModalExpenses(currentGroupExpenses.filter(item => {
                        return (item.description && item.description.toLowerCase().includes(q)) ||
                               (item.notes && item.notes.toLowerCase().includes(q)) ||
                               (item.category && item.category.toLowerCase().includes(q)) ||
                               (item.payment_method && item.payment_method.toLowerCase().includes(q)) ||
                               (item.paid_by && item.paid_by.toLowerCase().includes(q)) ||
                               String(item.amount).includes(q);
                    }));
                });
            }
        });

        // Open Drilldown Modal for clicked Category, Date, or Price Bin
        async function openExpenseGroupModal(type, categoryId, categoryName, date, color, binMin = null, binMax = null) {
            const modal = document.getElementById('expense-group-modal');
            const titleEl = document.getElementById('modal-group-title');
            const subtitleEl = document.getElementById('modal-group-subtitle');
            const colorBar = document.getElementById('modal-category-color-bar');
            const listEl = document.getElementById('modal-expenses-list');
            const totalBadge = document.getElementById('modal-total-badge');
            const filterInput = document.getElementById('modal-expense-filter');

            if (!modal) return;

            // Reset modal state
            if (filterInput) filterInput.value = '';
            renderModalCategoryChips([]);
            colorBar.style.backgroundColor = color || '#6366f1';
            titleEl.textContent = categoryName ? `${categoryName}` : (date ? `Expenses on ${date}` : 'Expense Group');
            subtitleEl.textContent = 'Fetching transactions from database...';
            totalBadge.textContent = '₹...';
            listEl.innerHTML = `
                <div class="py-12 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-2">
                    <span class="animate-spin text-2xl">⏳</span>
                    <span>Loading group items...</span>
                </div>
            `;

            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            try {
                const params = new URLSearchParams({
                    type: type || 'normal',
                    period: '{{ $period }}',
                    month: '{{ $dateFilter['month_val'] ?? '' }}',
                    from_month: '{{ $dateFilter['from_month'] ?? '' }}',
                    to_month: '{{ $dateFilter['to_month'] ?? '' }}',
                    year: '{{ $dateFilter['year_val'] ?? '' }}',
                    from_date: '{{ $dateFilter['from_date'] ?? '' }}',
                    to_date: '{{ $dateFilter['to_date'] ?? '' }}'
                });
                if (categoryId !== null && categoryId !== undefined) params.append('category_id', categoryId);
                if (date) params.append('date', date);
                if (binMin !== null) params.append('bin_min', binMin);
                if (binMax !== null) params.append('bin_max', binMax);

                const res = await fetch(`{{ route('analytics.drilldown') }}?${params.toString()}`);
                if (!res.ok) throw new Error('Failed to load group details');
                const data = await res.json();

                titleEl.textContent = data.title;
                subtitleEl.textContent = `${data.count} items recorded · ${data.total_formatted} total · ${data.period_label || ''}`;
                totalBadge.textContent = data.total_formatted;
                colorBar.style.backgroundColor = data.color || color || '#6366f1';

                currentGroupExpenses = data.expenses || [];
                renderModalExpenses(currentGroupExpenses);
                renderModalCategoryChips(data.categories || []);
            } catch (err) {
                console.error(err);
                listEl.innerHTML = `
                    <div class="p-8 text-center text-rose-500 text-xs">
                        ⚠️ Unable to load expenses. Please try again.
                    </div>
                `;
            }
        }

        function renderModalCategoryChips(categories) {
            const chipsEl = document.getElementById('modal-category-chips');
            if (!chipsEl) return;

            // A single-category drilldown (bar / pie slice) needs no chips
            if (categories.length < 2) {
                chipsEl.classList.add('hidden');
                chipsEl.innerHTML = '';
                return;
            }

            const chipClass = 'modal-cat-chip px-2.5 py-1 rounded-full border text-[11px] font-semibold flex items-center gap-1.5 cursor-pointer transition';
            chipsEl.innerHTML = `<button type="button" data-category="" class="${chipClass} bg-slate-900 text-white border-slate-900">All</button>` +
                categories.map(cat => `
                    <button type="button" data-category="${escapeHtml(cat.name)}" class="${chipClass} bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200">
                        <span class="w-2 h-2 rounded-full" style="background-color:${escapeHtml(cat.color)}"></span>
                        ${escapeHtml(cat.name)} · ₹${Number(cat.total).toLocaleString()} (${cat.percentage}%)
                    </button>
                `).join('');
            chipsEl.classList.remove('hidden');

            chipsEl.querySelectorAll('.modal-cat-chip').forEach(chip => {
                chip.addEventListener('click', () => {
                    chipsEl.querySelectorAll('.modal-cat-chip').forEach(c => {
                        c.classList.remove('bg-slate-900', 'text-white', 'border-slate-900');
                    });
                    chip.classList.add('bg-slate-900', 'text-white', 'border-slate-900');
                    const name = chip.dataset.category;
                    renderModalExpenses(name ? currentGroupExpenses.filter(item => item.category === name) : currentGroupExpenses);
                });
            });
        }

        function renderModalExpenses(items) {
            const listEl = document.getElementById('modal-expenses-list');
            const countEl = document.getElementById('modal-item-count');

            if (countEl) countEl.textContent = `${items.length} items shown`;

            if (!items || items.length === 0) {
                listEl.innerHTML = `
                    <div class="py-12 text-center text-slate-400 text-xs">
                        No transactions found for this selection.
                    </div>
                `;
                return;
            }

            listEl.innerHTML = items.map(exp => `
                <div class="pt-2.5 pb-2.5 first:pt-0 flex items-start justify-between gap-3 group">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-slate-900 dark:text-white text-sm">${escapeHtml(exp.description)}</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[10px] font-semibold text-slate-600 dark:text-slate-300">
                                ${escapeHtml(exp.category)}
                            </span>
                            <span class="text-[10px] text-slate-400 font-medium">
                                ${escapeHtml(exp.date)}
                            </span>
                        </div>

                        ${exp.friend_splits ? `
                            <p class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 mt-0.5">
                                👥 ${escapeHtml(exp.friend_splits)}
                            </p>
                        ` : ''}

                        ${exp.notes ? `
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">
                                ${escapeHtml(exp.notes)}
                            </p>
                        ` : ''}
                    </div>

                    <div class="flex items-center gap-2 self-start shrink-0 text-right">
                        <div>
                            <span class="font-extrabold text-slate-900 dark:text-white text-sm">${exp.amount_formatted}</span>
                            <span class="block text-[10px] text-slate-400">${escapeHtml(exp.payment_method)}</span>
                        </div>

                        ${exp.receipt_url ? `
                            <button type="button" onclick="window.openImageModal('${exp.receipt_url}', 'Receipt: ${escapeJs(exp.description)}', '${exp.amount_formatted} · ${escapeJs(exp.date)}')"
                                class="p-1.5 rounded-lg bg-indigo-50 dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 transition cursor-pointer" title="View attached receipt in dialog">
                                📷
                            </button>
                        ` : ''}

                        <a href="${exp.view_url}" target="_blank"
                            class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white transition" title="View details">
                            ↗
                        </a>
                    </div>
                </div>
            `).join('');
        }

        function closeExpenseGroupModal() {
            const modal = document.getElementById('expense-group-modal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeExpenseGroupModal();
            }
        });

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function escapeJs(str) {
            if (!str) return '';
            return String(str).replace(/'/g, "\\'");
        }
    </script>
</x-app-layout>

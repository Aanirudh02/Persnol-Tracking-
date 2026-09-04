<x-app-layout title="Analytics & Trends">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Life & Finance Analytics</h1>
                <p class="text-xs text-slate-500">Visual trends across money, habits, food, scooter trips, and sleep quality.</p>
            </div>

            <!-- Date range selector -->
            <div class="flex items-center gap-1.5 text-xs bg-white dark:bg-slate-800 p-1 rounded-xl border border-sky-100 dark:border-slate-700 shadow-sm">
                <a href="{{ route('analytics.index', ['days' => 7]) }}" class="px-3.5 py-1.5 rounded-lg font-semibold transition {{ $days == 7 ? 'bg-sky-600 text-white shadow-sm shadow-sky-500/20' : 'text-slate-600 hover:text-sky-600' }}">7 Days</a>
                <a href="{{ route('analytics.index', ['days' => 30]) }}" class="px-3.5 py-1.5 rounded-lg font-semibold transition {{ $days == 30 ? 'bg-sky-600 text-white shadow-sm shadow-sky-500/20' : 'text-slate-600 hover:text-sky-600' }}">30 Days</a>
                <a href="{{ route('analytics.index', ['days' => 90]) }}" class="px-3.5 py-1.5 rounded-lg font-semibold transition {{ $days == 90 ? 'bg-sky-600 text-white shadow-sm shadow-sky-500/20' : 'text-slate-600 hover:text-sky-600' }}">90 Days</a>
            </div>
        </div>

        <!-- 4 Grid Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- 1. Finance: Income vs Expenses -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-sky-100 dark:border-slate-800 shadow-sm space-y-3">
                <h2 class="font-bold text-sm text-slate-900 dark:text-white">Income vs Expenses ({{ $days }} Days)</h2>
                <div class="h-64 w-full">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>

            <!-- 2. Finance: Category Breakdown Doughnut -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                <h2 class="font-bold text-sm text-slate-900 dark:text-white">Expense Distribution by Category</h2>
                <div class="h-64 w-full">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <!-- 3. Scooter: Distance Over Time -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                <h2 class="font-bold text-sm text-slate-900 dark:text-white">Scooter Travel Distance (km)</h2>
                <div class="h-64 w-full">
                    <canvas id="scooterChart"></canvas>
                </div>
            </div>

            <!-- 4. Habits: Sleep & Day Rating -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                <div class="flex justify-between items-center">
                    <h2 class="font-bold text-sm text-slate-900 dark:text-white">Sleep & Well-being Duration</h2>
                    <span class="text-xs text-indigo-500 font-semibold">Avg: {{ $avgSleepDuration }}h &bull; Rating: {{ $avgDayRating }}/5</span>
                </div>
                <div class="h-64 w-full">
                    <canvas id="sleepChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Finance Chart
            const fCtx = document.getElementById('financeChart');
            if (fCtx) {
                const expMap = @json($expensesByDay);
                const incMap = @json($incomeByDay);
                const allDates = Array.from(new Set([...Object.keys(expMap), ...Object.keys(incMap)])).sort();

                new Chart(fCtx, {
                    type: 'line',
                    data: {
                        labels: allDates.map(d => d.slice(5)),
                        datasets: [
                            {
                                label: 'Income (₹)',
                                data: allDates.map(d => incMap[d] || 0),
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                fill: true,
                                tension: 0.3
                            },
                            {
                                label: 'Expenses (₹)',
                                data: allDates.map(d => expMap[d] || 0),
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                fill: true,
                                tension: 0.3
                            }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }

            // 2. Category Doughnut
            const cCtx = document.getElementById('categoryChart');
            if (cCtx) {
                const catData = @json($categorySpending);
                new Chart(cCtx, {
                    type: 'doughnut',
                    data: {
                        labels: catData.map(c => c.name),
                        datasets: [{
                            data: catData.map(c => c.total),
                            backgroundColor: ['#6366f1', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#06b6d4', '#64748b']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }

            // 3. Scooter Distance
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
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }

            // 4. Sleep Duration
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
                            tension: 0.4
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }
        });
    </script>
</x-app-layout>

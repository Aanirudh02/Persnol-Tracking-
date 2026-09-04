<x-app-layout title="Reports & Analytics Export">
    <div class="space-y-6">
        <!-- Header with Filter & Export -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-sky-100 shadow-sm">
            <div>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200/60 mb-2">
                    📊 Data Intelligence
                </span>
                <h1 class="text-2xl font-bold text-slate-900">Reports & Export</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                    Period: <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }}</span> to <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a
                    href="{{ route('reports.export', ['preset' => $filter, 'from_date' => $startDate, 'to_date' => $endDate]) }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-sm transition active:scale-95"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export to CSV
                </a>
            </div>
        </div>

        <!-- Filter Presets Bar -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            @php
                $presets = [
                    'today' => 'Today',
                    'this_week' => 'This Week',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month',
                    'this_year' => 'This Year',
                ];
            @endphp
            @foreach($presets as $key => $label)
                <a
                    href="{{ route('reports.index', ['preset' => $key]) }}"
                    class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition {{ $filter === $key ? 'bg-sky-600 text-white shadow-md shadow-sky-500/20' : 'bg-white text-slate-600 hover:bg-sky-50 border border-sky-100' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <!-- KPI Metrics Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Expenses -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-sky-100 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span class="font-medium">Total Expenses</span>
                    <span class="p-1.5 rounded-lg bg-rose-50 text-rose-600 font-bold">💳</span>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-rose-600">₹{{ number_format($totalExpenses, 2) }}</div>
                <div class="text-[11px] text-slate-400 mt-1">{{ count($expenses) }} transactions recorded</div>
            </div>

            <!-- Income -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-sky-100 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span class="font-medium">Total Income</span>
                    <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 font-bold">💵</span>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-emerald-600">₹{{ number_format($totalIncome, 2) }}</div>
                <div class="text-[11px] text-slate-400 mt-1">{{ count($incomes) }} receipts logged</div>
            </div>

            <!-- Petrol -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-sky-100 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span class="font-medium">Petrol / Fuel</span>
                    <span class="p-1.5 rounded-lg bg-sky-50 text-sky-600 font-bold">⛽</span>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-sky-600">₹{{ number_format($totalPetrol, 2) }}</div>
                <div class="text-[11px] text-slate-400 mt-1">{{ count($fuelEntries) }} fuel logs</div>
            </div>

            <!-- Distance -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-sky-100 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span class="font-medium">Scooter Distance</span>
                    <span class="p-1.5 rounded-lg bg-cyan-50 text-cyan-600 font-bold">🛵</span>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-cyan-600">{{ number_format($totalDistance, 1) }} km</div>
                <div class="text-[11px] text-slate-400 mt-1">{{ count($trips) }} scooter trips</div>
            </div>
        </div>

        <!-- Net Financial Balance Card -->
        <div class="bg-gradient-to-r from-sky-50 via-white to-blue-50/50 rounded-2xl p-5 border border-sky-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="text-xs font-semibold text-sky-800 uppercase tracking-wider">Net Balance (Income − Expenses)</div>
                <div class="text-2xl font-black {{ ($totalIncome - $totalExpenses) >= 0 ? 'text-emerald-600' : 'text-rose-600' }} mt-1">
                    {{ ($totalIncome - $totalExpenses) >= 0 ? '+' : '' }}₹{{ number_format($totalIncome - $totalExpenses, 2) }}
                </div>
            </div>
            <div class="text-xs text-slate-500 max-w-sm">
                Calculated across all transactions between {{ \Carbon\Carbon::parse($startDate)->format('d M') }} and {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}.
            </div>
        </div>

        <!-- Detailed Breakdown Tables (Mobile Responsive) -->
        <div class="space-y-6">

            <!-- Expenses Breakdown Table -->
            <div class="bg-white rounded-2xl border border-sky-100 shadow-sm overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-base">💳</span>
                        <h3 class="font-bold text-sm text-slate-900">Expenses Log</h3>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700">{{ count($expenses) }}</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-sky-50/60 text-slate-500 font-semibold border-b border-sky-100">
                            <tr>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Description</th>
                                <th class="py-3 px-4">Category</th>
                                <th class="py-3 px-4">Method</th>
                                <th class="py-3 px-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($expenses as $item)
                                <tr class="hover:bg-sky-50/30 transition">
                                    <td class="py-3 px-4 text-slate-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($item->date)->format('M d, Y') }}</td>
                                    <td class="py-3 px-4 font-semibold text-slate-900">{{ $item->description }}</td>
                                    <td class="py-3 px-4 text-slate-500">{{ $item->category?->name ?? 'General' }}</td>
                                    <td class="py-3 px-4 text-slate-500">{{ $item->payment_method }}</td>
                                    <td class="py-3 px-4 text-right font-bold text-rose-600">₹{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400">No expenses recorded for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Scooter Trips & Fuel Table -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Trips -->
                <div class="bg-white rounded-2xl border border-sky-100 shadow-sm overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                            <span>🛵</span> Scooter Trips ({{ count($trips) }})
                        </h3>
                    </div>
                    <div class="overflow-x-auto max-h-72">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-sky-50/60 text-slate-500 font-semibold border-b border-sky-100">
                                <tr>
                                    <th class="py-2.5 px-3">Date</th>
                                    <th class="py-2.5 px-3">Route</th>
                                    <th class="py-2.5 px-3 text-right">Distance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($trips as $trip)
                                    <tr class="hover:bg-sky-50/30">
                                        <td class="py-2.5 px-3 whitespace-nowrap text-slate-500">{{ \Carbon\Carbon::parse($trip->date)->format('M d') }}</td>
                                        <td class="py-2.5 px-3 text-slate-900 font-medium">{{ $trip->title ?? 'Ride' }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-cyan-700">{{ $trip->distance_km }} km</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-6 text-center text-slate-400">No trips logged in this range.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Petrol -->
                <div class="bg-white rounded-2xl border border-sky-100 shadow-sm overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                            <span>⛽</span> Petrol Refuels ({{ count($fuelEntries) }})
                        </h3>
                    </div>
                    <div class="overflow-x-auto max-h-72">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-sky-50/60 text-slate-500 font-semibold border-b border-sky-100">
                                <tr>
                                    <th class="py-2.5 px-3">Date</th>
                                    <th class="py-2.5 px-3">Litres</th>
                                    <th class="py-2.5 px-3 text-right">Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($fuelEntries as $fuel)
                                    <tr class="hover:bg-sky-50/30">
                                        <td class="py-2.5 px-3 whitespace-nowrap text-slate-500">{{ \Carbon\Carbon::parse($fuel->date)->format('M d') }}</td>
                                        <td class="py-2.5 px-3 text-slate-700">{{ $fuel->litres }} L</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-sky-700">₹{{ number_format($fuel->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-6 text-center text-slate-400">No fuel records for this range.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Mistakes & Learnings -->
            @if(count($mistakes) > 0)
                <div class="bg-white rounded-2xl border border-sky-100 shadow-sm p-5">
                    <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2 mb-3">
                        <span>⚠️</span> Mistakes & Lessons Recorded ({{ count($mistakes) }})
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($mistakes as $mistake)
                            <div class="p-3.5 rounded-xl bg-amber-50/40 border border-amber-200/70">
                                <div class="flex items-center justify-between text-xs text-amber-900 font-bold mb-1">
                                    <span>{{ $mistake->title }}</span>
                                    <span class="text-[10px] text-slate-500">{{ \Carbon\Carbon::parse($mistake->date)->format('M d') }}</span>
                                </div>
                                <p class="text-xs text-slate-600 line-clamp-2">{{ $mistake->lesson_learned ?? $mistake->what_happened }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

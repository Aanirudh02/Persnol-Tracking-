<x-app-layout title="Petrol & Fuel Statement">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('petrol.index') }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600">&larr; Back to Petrol Log</a>
                <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Petrol Statement & Fuel Log</h1>
                <p class="text-xs text-slate-500">Comprehensive summary of fuel purchases, volume, cost, and calculated mileage.</p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50">
                    🖨️ Print Statement
                </button>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm text-xs">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Period</label>
                    <div class="flex items-center gap-1.5">
                        @foreach(['week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year', 'custom' => 'Custom'] as $key => $lbl)
                            <a href="{{ route('petrol.statement', array_merge(request()->query(), ['period' => $key])) }}" class="rounded-xl px-3 py-1.5 font-bold transition {{ $period === $key ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $lbl }}</a>
                        @endforeach
                    </div>
                </div>

                @if($period === 'custom')
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">From</label>
                        <input type="date" name="from_date" value="{{ $fromDate ?: $startDate }}" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold">
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">To</label>
                        <input type="date" name="to_date" value="{{ $toDate ?: $endDate }}" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold">
                    </div>
                @endif

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Vehicle</label>
                    <select name="vehicle_id" onchange="this.form.submit()" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold">
                        <option value="all">All Vehicles</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" @selected($vehicleId == $v->id)>{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if($period === 'custom')
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-1.5 font-bold text-white">Apply</button>
                @endif
            </form>
        </div>

        <!-- Metric summary -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Fuel Cost</span>
                <div class="mt-1 text-2xl font-black text-slate-900">₹{{ number_format($totalSpent, 2) }}</div>
                <span class="text-[11px] text-slate-500">{{ $entriesDesc->count() }} fill-ups</span>
            </div>
            <div class="rounded-3xl border border-sky-100 bg-sky-50/50 p-4 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-sky-600">Total Volume</span>
                <div class="mt-1 text-2xl font-black text-sky-700">{{ number_format($totalLitres, 2) }} L</div>
                <span class="text-[11px] text-sky-600 font-semibold">Litres pumped</span>
            </div>
            <div class="rounded-3xl border border-amber-100 bg-amber-50/50 p-4 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600">Average Fuel Rate</span>
                <div class="mt-1 text-2xl font-black text-amber-700">₹{{ number_format($avgPricePerLitre, 2) }}<span class="text-xs font-normal text-slate-400">/L</span></div>
                <span class="text-[11px] text-amber-600 font-semibold">Weighted average</span>
            </div>
            <div class="rounded-3xl border border-emerald-100 bg-emerald-50/50 p-4 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Calculated Mileage</span>
                @php
                    $validMileages = $entriesDesc->filter(fn($e) => $e['mileage'] !== null);
                    $avgMileage = $validMileages->isNotEmpty() ? round($validMileages->avg('mileage'), 1) : null;
                @endphp
                <div class="mt-1 text-2xl font-black text-emerald-700">{{ $avgMileage ? $avgMileage.' km/L' : '—' }}</div>
                <span class="text-[11px] text-emerald-600 font-semibold">Average fuel efficiency</span>
            </div>
        </div>

        <!-- Statement Table -->
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden" id="petrol-statement-print">
            <div class="p-4 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between text-xs">
                <span class="font-bold text-slate-800 uppercase">Period: {{ $startDate ?: 'Beginning' }} to {{ $endDate ?: 'Today' }}</span>
                <span class="text-slate-500 font-semibold">{{ $entriesDesc->count() }} records</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Vehicle</th>
                            <th class="px-4 py-3">Station</th>
                            <th class="px-4 py-3">Litres</th>
                            <th class="px-4 py-3">Rate (₹/L)</th>
                            <th class="px-4 py-3 font-black text-slate-900">Total (₹)</th>
                            <th class="px-4 py-3">Odometer</th>
                            <th class="px-4 py-3">Run (km)</th>
                            <th class="px-4 py-3 text-emerald-700 font-bold">Mileage</th>
                            <th class="px-4 py-3">Expense Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold">
                        @forelse($entriesDesc as $item)
                            @php $fuel = $item['entry']; @endphp
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-4 py-3 text-slate-800 whitespace-nowrap">{{ $fuel->date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $fuel->vehicle?->name ?? 'TVS Pep+' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $fuel->petrol_station ?: '—' }}</td>
                                <td class="px-4 py-3 text-sky-700 font-bold">{{ number_format($fuel->litres, 2) }} L</td>
                                <td class="px-4 py-3 text-slate-600">₹{{ number_format($fuel->price_per_litre, 2) }}</td>
                                <td class="px-4 py-3 font-black text-slate-900 text-sm">₹{{ number_format($fuel->amount, 2) }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $fuel->odometer ? number_format($fuel->odometer).' km' : '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $item['distance'] ? number_format($item['distance']).' km' : '—' }}</td>
                                <td class="px-4 py-3 font-bold text-emerald-600">{{ $item['mileage'] ? $item['mileage'].' km/L' : '—' }}</td>
                                <td class="px-4 py-3 text-xs">
                                    @if($fuel->expense)
                                        <span class="rounded-lg bg-emerald-50 text-emerald-700 px-2 py-0.5 font-bold border border-emerald-100">Linked #{{ $fuel->expense->id }}</span>
                                    @else
                                        <span class="rounded-lg bg-slate-100 text-slate-500 px-2 py-0.5 font-medium">Not Linked</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-12 text-center text-slate-400">No petrol log entries found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

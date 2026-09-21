<x-app-layout :title="$group->title . ' · Mileage Cycle'">
    <div class="space-y-6 max-w-5xl mx-auto">
        <!-- Breadcrumb / Back Navigation -->
        <div class="flex items-center justify-between">
            <a href="{{ route('odometer.index') }}" class="text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center gap-1.5 transition">
                <span>&larr;</span>
                <span>Back to Odometer Dashboard</span>
            </a>

            <form action="{{ route('odometer.groups.destroy', $group) }}" method="POST" onsubmit="return confirm('Delete this entire cycle and all its readings?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-1.5 rounded-xl border border-rose-200 dark:border-rose-900 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-300 text-xs font-semibold hover:bg-rose-100 transition">
                    Delete Cycle
                </button>
            </form>
        </div>

        <!-- Cycle Header Card -->
        <div class="p-6 rounded-2xl bg-gradient-to-br from-indigo-900 via-slate-900 to-indigo-950 text-white shadow-xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/10 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $group->status === 'active' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-700 text-slate-300 border border-slate-600' }}">
                            {{ ucfirst($group->status) }} Cycle
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-white">{{ $group->title }}</h1>
                    </div>
                    <p class="text-xs text-indigo-200 mt-1">
                        Vehicle: <strong class="text-white">{{ $group->vehicle?->name ?? 'Default Vehicle' }}</strong> · 
                        Created on {{ $group->created_at->format('d M Y, h:i A') }}
                    </p>
                </div>

                @if($group->calculated_mileage > 0)
                    <div class="text-right bg-white/10 px-4 py-2.5 rounded-xl border border-white/10">
                        <div class="text-[11px] uppercase tracking-wider text-indigo-200">Fuel Efficiency</div>
                        <div class="text-2xl font-black text-emerald-400">{{ number_format($group->calculated_mileage, 1) }} <span class="text-sm font-semibold text-white">km/L</span></div>
                        <div class="text-xs text-indigo-200 mt-0.5">₹{{ number_format($group->cost_per_km, 2) }} / km</div>
                    </div>
                @endif
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <span class="text-[11px] uppercase tracking-wider text-indigo-300">Total Distance</span>
                    <div class="text-xl font-bold text-white mt-1">{{ number_format($group->total_km ?? 0, 1) }} <span class="text-xs font-normal text-indigo-200">km</span></div>
                    <p class="text-[11px] text-indigo-200 mt-0.5">{{ number_format($group->start_odometer, 1) }} &rarr; {{ number_format($group->end_odometer ?? $group->start_odometer, 1) }} km</p>
                </div>

                <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <span class="text-[11px] uppercase tracking-wider text-indigo-300">Fuel Consumed</span>
                    <div class="text-xl font-bold text-white mt-1">{{ $group->total_litres ? number_format($group->total_litres, 2) . ' L' : '—' }}</div>
                    <p class="text-[11px] text-indigo-200 mt-0.5">Refill to full tank</p>
                </div>

                <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <span class="text-[11px] uppercase tracking-wider text-indigo-300">Fuel Expense</span>
                    <div class="text-xl font-bold text-white mt-1">{{ $group->total_fuel_cost ? '₹' . number_format($group->total_fuel_cost, 0) : '—' }}</div>
                    <p class="text-[11px] text-indigo-200 mt-0.5">Refill cost</p>
                </div>

                <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <span class="text-[11px] uppercase tracking-wider text-indigo-300">Total Legs</span>
                    <div class="text-xl font-bold text-white mt-1">{{ $group->readings->count() }}</div>
                    <p class="text-[11px] text-indigo-200 mt-0.5">Readings in cycle</p>
                </div>
            </div>

            @if($group->startFuelEntry || $group->endFuelEntry)
                <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-indigo-200">
                    @if($group->startFuelEntry)
                        <div>
                            <strong class="text-white">Starting Petrol Refuel:</strong> 
                            {{ \Carbon\Carbon::parse($group->startFuelEntry->date)->format('d M Y') }} · ₹{{ number_format($group->startFuelEntry->amount, 0) }} ({{ $group->startFuelEntry->litres }} L)
                        </div>
                    @endif
                    @if($group->endFuelEntry)
                        <div>
                            <strong class="text-white">Ending Petrol Refuel:</strong> 
                            {{ \Carbon\Carbon::parse($group->endFuelEntry->date)->format('d M Y') }} · ₹{{ number_format($group->endFuelEntry->amount, 0) }} ({{ $group->endFuelEntry->litres }} L)
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Legs Breakdown List -->
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Trip Legs Breakdown</h2>

            <div class="space-y-3">
                @foreach($group->readings as $index => $reading)
                    <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start sm:items-center gap-3">
                            <span class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm shrink-0 {{ $reading->reading_type === 'source' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : ($reading->reading_type === 'ending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300') }}">
                                {{ $reading->reading_type === 'source' ? '🏁' : ($reading->reading_type === 'ending' ? '⛽' : ($index)) }}
                            </span>
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                                    <span>{{ $reading->trip_name ?: ucfirst($reading->reading_type) }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                        {{ $reading->reading_type }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    @if($reading->source_location || $reading->destination)
                                        <span class="font-medium text-slate-700 dark:text-slate-300">
                                            {{ $reading->source_location ?: '—' }} &rarr; {{ $reading->destination ?: '—' }}
                                        </span> · 
                                    @endif
                                    {{ \Carbon\Carbon::parse($reading->reading_date)->format('d M Y') }}
                                    @if($reading->reading_time)
                                        at {{ \Carbon\Carbon::parse($reading->reading_time)->format('h:i A') }}
                                    @endif
                                    @if($reading->duration_minutes)
                                        · {{ $reading->duration_minutes }} mins
                                    @endif
                                    @if($reading->avg_speed_kmh)
                                        · <strong>{{ $reading->avg_speed_kmh }} km/h</strong> avg
                                    @endif
                                </div>
                                @if($reading->notes)
                                    <p class="text-xs text-slate-500 italic mt-1">“{{ $reading->notes }}”</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-4 self-end sm:self-center">
                            <div class="text-right">
                                <div class="font-bold text-slate-900 dark:text-white text-base">
                                    {{ number_format($reading->odometer_km, 1) }} <span class="text-xs font-normal text-slate-400">km</span>
                                </div>
                                @if($reading->distance_km > 0)
                                    <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                        +{{ number_format($reading->distance_km, 1) }} km
                                    </div>
                                @endif
                            </div>

                            @if($reading->image_url)
                                <a href="{{ $reading->image_url }}" target="_blank" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-indigo-50 hover:text-indigo-600 transition" title="View Odometer Photo">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </a>
                            @endif

                            <form action="{{ route('odometer.readings.destroy', $reading) }}" method="POST" onsubmit="return confirm('Delete this reading?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950 transition" title="Delete reading">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>

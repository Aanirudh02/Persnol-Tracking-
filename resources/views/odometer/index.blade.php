<x-app-layout title="Odometer & Mileage Cycles">
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" stroke-width="2"></circle>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7v5l3 3"></path>
                    </svg>
                    <span>Odometer & Mileage Cycles</span>
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    Track odometer readings, daily trip legs, and calculate true full-tank mileage (km/L) and cost per km.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('petrol.index') }}" class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-sm">
                    ⛽ Petrol Log
                </a>
                <a href="#quick-record" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm shadow-sm transition">
                    + Record Reading
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 text-sm flex items-center justify-between">
                <span>{{ session('success') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 dark:text-emerald-400 hover:text-emerald-900">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-200 text-sm flex items-center justify-between">
                <span>{{ session('error') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 dark:text-rose-400 hover:text-rose-900">&times;</button>
            </div>
        @endif

        <!-- High-level Metric Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Latest Odometer</span>
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                    {{ number_format($latestOdometer, 1) }} <span class="text-sm font-medium text-slate-400">km</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Current meter reading</p>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Tracked</span>
                <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 mt-1">
                    {{ number_format($totalKmLogged, 1) }} <span class="text-sm font-medium text-slate-400">km</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Across all completed cycles</p>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Avg Mileage</span>
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                    {{ $averageMileage > 0 ? number_format($averageMileage, 1) : '—' }} 
                    @if($averageMileage > 0)
                        <span class="text-sm font-medium text-slate-400">km/L</span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Real-world full-tank average</p>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Best Mileage</span>
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">
                    {{ $bestMileage > 0 ? number_format($bestMileage, 1) : '—' }}
                    @if($bestMileage > 0)
                        <span class="text-sm font-medium text-slate-400">km/L</span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Personal record efficiency</p>
            </div>
        </div>

        <!-- ACTIVE CYCLE BANNER -->
        @if($activeGroup)
            <div class="p-6 rounded-2xl bg-gradient-to-br from-indigo-900 via-slate-900 to-indigo-950 text-white shadow-lg space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-indigo-800/60 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                Active Cycle
                            </span>
                            <h2 class="text-xl font-bold tracking-tight text-white">{{ $activeGroup->title }}</h2>
                        </div>
                        <p class="text-xs text-indigo-200 mt-1">
                            Vehicle: <strong class="text-white">{{ $activeGroup->vehicle?->name ?? 'Default Vehicle' }}</strong> · 
                            Started at: <strong class="text-white">{{ number_format($activeGroup->start_odometer, 1) }} km</strong> 
                            @if($activeGroup->startFuelEntry)
                                · Linked refuel: ₹{{ number_format($activeGroup->startFuelEntry->amount, 0) }} ({{ $activeGroup->startFuelEntry->litres }} L)
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="switchToTab('intermediate')" class="px-3.5 py-2 rounded-xl bg-indigo-500 hover:bg-indigo-400 text-white text-xs font-semibold transition cursor-pointer">
                            + Add Trip Leg
                        </button>
                        <button type="button" onclick="switchToTab('ending')" class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold transition cursor-pointer">
                            🏁 End Cycle & Refuel
                        </button>
                    </div>
                </div>

                <!-- Distance Progress in Active Cycle -->
                @php
                    $cycleDistance = $lastReading ? max(0, round($lastReading->odometer_km - $activeGroup->start_odometer, 2)) : 0;
                @endphp
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-white/5 p-4 rounded-xl border border-white/10">
                    <div>
                        <span class="text-[11px] uppercase tracking-wider text-indigo-300">Start Odometer</span>
                        <div class="text-lg font-bold text-white mt-0.5">{{ number_format($activeGroup->start_odometer, 1) }} km</div>
                    </div>
                    <div>
                        <span class="text-[11px] uppercase tracking-wider text-indigo-300">Current Odometer</span>
                        <div class="text-lg font-bold text-white mt-0.5">{{ number_format($lastReading?->odometer_km ?? $activeGroup->start_odometer, 1) }} km</div>
                    </div>
                    <div>
                        <span class="text-[11px] uppercase tracking-wider text-emerald-300">Distance Travelled</span>
                        <div class="text-lg font-bold text-emerald-400 mt-0.5">+{{ number_format($cycleDistance, 1) }} km</div>
                    </div>
                    <div>
                        <span class="text-[11px] uppercase tracking-wider text-indigo-300">Trip Legs</span>
                        <div class="text-lg font-bold text-white mt-0.5">{{ $activeReadings->count() }} recorded</div>
                    </div>
                </div>

                <!-- Timeline of Legs in Active Cycle -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-300">Cycle Timeline & Legs</h3>
                    <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
                        @foreach($activeReadings as $reading)
                            <div class="p-3 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs transition">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm {{ $reading->reading_type === 'source' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : ($reading->reading_type === 'ending' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-sky-500/20 text-sky-300 border border-sky-500/40') }}">
                                        {{ $reading->reading_type === 'source' ? 'S' : ($reading->reading_type === 'ending' ? 'E' : 'L') }}
                                    </span>
                                    <div>
                                        <div class="font-semibold text-white flex items-center gap-2">
                                            <span>{{ $reading->trip_name ?: ucfirst($reading->reading_type) }}</span>
                                            @if($reading->source_location || $reading->destination)
                                                <span class="text-indigo-300 text-[11px]">
                                                    ({{ $reading->source_location ?: '—' }} &rarr; {{ $reading->destination ?: '—' }})
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-indigo-200 text-[11px] mt-0.5">
                                            {{ \Carbon\Carbon::parse($reading->reading_date)->format('d M') }} · {{ $reading->reading_time ? \Carbon\Carbon::parse($reading->reading_time)->format('h:i A') : '' }}
                                            @if($reading->duration_minutes)
                                                · {{ $reading->duration_minutes }} mins
                                            @endif
                                            @if($reading->avg_speed_kmh)
                                                · Avg {{ $reading->avg_speed_kmh }} km/h
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 self-end sm:self-center">
                                    <div class="text-right">
                                        <div class="font-bold text-white text-sm">{{ number_format($reading->odometer_km, 1) }} km</div>
                                        @if($reading->distance_km > 0)
                                            <div class="text-emerald-400 font-semibold text-[11px]">+{{ number_format($reading->distance_km, 1) }} km</div>
                                        @endif
                                    </div>

                                    @if($reading->image_url)
                                        <a href="{{ $reading->image_url }}" target="_blank" class="p-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-indigo-200 hover:text-white" title="View Odometer Photo">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </a>
                                    @endif

                                    <form action="{{ route('odometer.readings.destroy', $reading) }}" method="POST" onsubmit="return confirm('Delete this reading?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg hover:bg-rose-500/20 text-rose-400 hover:text-rose-200 transition" title="Delete reading">
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
        @else
            <!-- No Active Cycle Notice -->
            <div class="p-6 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-base font-bold text-indigo-950 dark:text-indigo-200">Ready to start a new fuel-to-fuel mileage cycle?</h2>
                    <p class="text-xs text-indigo-800 dark:text-indigo-300">
                        Record a <strong>Source Reading</strong> (at refuel or start of week). As you ride daily, add intermediate legs, then close it at your next refuel to get your exact km/L mileage!
                    </p>
                </div>
                <button type="button" onclick="switchToTab('source')" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition whitespace-nowrap cursor-pointer">
                    🟢 Start New Cycle
                </button>
            </div>
        @endif

        <!-- QUICK RECORD CARD WITH TABS (SOURCE / INTERMEDIATE / ENDING) -->
        <div id="quick-record" class="p-6 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-5 scroll-mt-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Record Odometer Reading</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Select the reading type below to log a starting anchor, daily trip leg, or cycle refuel end.
                </p>
            </div>

            <!-- Tab Buttons -->
            <div class="flex items-center gap-2 p-1.5 rounded-xl bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-xs font-semibold">
                <button type="button" onclick="switchToTab('source')" id="tab-btn-source" class="flex-1 py-2 px-3 rounded-lg transition text-slate-700 dark:text-slate-300 cursor-pointer flex items-center justify-center gap-1.5">
                    <span>🟢</span>
                    <span>1. Source (Start Cycle)</span>
                </button>
                <button type="button" onclick="switchToTab('intermediate')" id="tab-btn-intermediate" class="flex-1 py-2 px-3 rounded-lg transition text-slate-700 dark:text-slate-300 cursor-pointer flex items-center justify-center gap-1.5">
                    <span>🟡</span>
                    <span>2. Intermediate (Trip Leg)</span>
                </button>
                <button type="button" onclick="switchToTab('ending')" id="tab-btn-ending" class="flex-1 py-2 px-3 rounded-lg transition text-slate-700 dark:text-slate-300 cursor-pointer flex items-center justify-center gap-1.5">
                    <span>🔴</span>
                    <span>3. Ending (Refuel & Close)</span>
                </button>
            </div>

            <!-- Form -->
            <form action="{{ route('odometer.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="reading_type" id="reading_type" value="{{ $activeGroup ? 'intermediate' : 'source' }}">

                <!-- Common Row: Odometer KM, Date, Time -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="odometer_km" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Odometer Reading (km) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.1" name="odometer_km" id="odometer_km" required
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            placeholder="e.g. 38513.6"
                            value="{{ old('odometer_km') }}">
                        <p id="km-helper" class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            Supports scooter decimal values (e.g. 38513.6).
                        </p>
                    </div>

                    <div>
                        <label for="reading_date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="reading_date" id="reading_date" required
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            value="{{ old('reading_date', now()->toDateString()) }}">
                    </div>

                    <div>
                        <label for="reading_time" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Time
                        </label>
                        <input type="time" name="reading_time" id="reading_time"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            value="{{ old('reading_time', now()->format('H:i')) }}">
                    </div>
                </div>

                <!-- SOURCE SPECIFIC FIELDS -->
                <div id="fields-source" class="space-y-4 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="group_title" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Cycle Title (Optional)
                            </label>
                            <input type="text" name="group_title" id="group_title"
                                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="e.g. Week of {{ now()->startOfWeek()->format('d M') }} – {{ now()->endOfWeek()->format('d M Y') }}">
                        </div>

                        <div>
                            <label for="start_fuel_entry_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Link Starting Petrol Entry (Optional)
                            </label>
                            <select name="start_fuel_entry_id" id="start_fuel_entry_id"
                                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="">-- Select recent refuel (Optional) --</option>
                                @foreach($recentFuelEntries as $fuel)
                                    <option value="{{ $fuel->id }}">
                                        {{ \Carbon\Carbon::parse($fuel->date)->format('d M') }} · ₹{{ number_format($fuel->amount, 0) }} ({{ $fuel->litres }} L) {{ $fuel->petrol_station ? 'at ' . $fuel->petrol_station : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- INTERMEDIATE / ENDING COMMON TRIP FIELDS (From, To, Trip Name) -->
                <div id="fields-trip" class="space-y-4 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="trip_name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Trip Name / Purpose
                            </label>
                            <input type="text" name="trip_name" id="trip_name"
                                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="e.g. Morning College Run, Bakery">
                        </div>

                        <div>
                            <label for="source_location" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                From (Source Location)
                            </label>
                            <input type="text" name="source_location" id="source_location"
                                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="e.g. Home, Goldwins">
                        </div>

                        <div>
                            <label for="destination" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                To (Destination)
                            </label>
                            <input type="text" name="destination" id="destination"
                                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="e.g. College, Supermarket">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="duration_minutes" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Duration (Minutes, Optional)
                            </label>
                            <input type="number" name="duration_minutes" id="duration_minutes" min="1" max="1440"
                                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="e.g. 25 (auto-computes avg speed km/h)">
                        </div>

                        <div>
                            <label for="vehicle_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Vehicle
                            </label>
                            <select name="vehicle_id" id="vehicle_id"
                                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ $defaultVehicle?->id === $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->name }} ({{ $vehicle->model ?? 'Scooter' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ENDING SPECIFIC FIELDS (Ending refuel entry / fuel calculation) -->
                <div id="fields-ending" class="space-y-4 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200">
                        <strong>Full-Tank Refuel Method:</strong> Linking your ending petrol entry (refill back to full) provides the exact litres consumed to calculate true mileage (km/L).
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label for="end_fuel_entry_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Link Ending Petrol Refuel Entry
                            </label>
                            <select name="end_fuel_entry_id" id="end_fuel_entry_id"
                                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="">-- Select ending petrol entry --</option>
                                @foreach($recentFuelEntries as $fuel)
                                    <option value="{{ $fuel->id }}">
                                        {{ \Carbon\Carbon::parse($fuel->date)->format('d M') }} · ₹{{ number_format($fuel->amount, 0) }} ({{ $fuel->litres }} L) {{ $fuel->petrol_station ? 'at ' . $fuel->petrol_station : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="manual_litres" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Or Manual Litres
                            </label>
                            <input type="number" step="0.01" name="manual_litres" id="manual_litres"
                                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="e.g. 3.45">
                        </div>
                    </div>
                </div>

                <!-- Optional Image & Notes (Common to all types) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <div>
                        <label for="odometer_image" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Odometer Photo (Optional · Cloudinary / Supabase)
                        </label>
                        <input type="file" name="odometer_image" id="odometer_image" accept="image/*"
                            class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-300">
                        <p class="text-[11px] text-slate-400 mt-1">Saved directly to your Cloudinary `odometer` folder or storage.</p>
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Notes / Remarks (Optional)
                        </label>
                        <input type="text" name="notes" id="notes"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            placeholder="e.g. Heavy traffic on Avinashi road, gentle driving">
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2 flex justify-end">
                    <button type="submit" id="submit-btn" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm shadow-sm transition flex items-center gap-2 cursor-pointer">
                        <span id="btn-icon">💾</span>
                        <span id="btn-text">Save Reading</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- COMPLETED CYCLES HISTORY -->
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Completed Mileage Cycles</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Past fuel-to-fuel cycles with calculated distance, fuel consumed, and true km/L efficiency.
                    </p>
                </div>
            </div>

            @if($completedGroups->isEmpty())
                <div class="py-12 text-center text-slate-400">
                    <span class="text-3xl">🛵</span>
                    <p class="text-sm font-medium mt-2">No completed cycles yet.</p>
                    <p class="text-xs text-slate-500 mt-0.5">Start your first cycle above with a Source reading!</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($completedGroups as $group)
                        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">{{ $group->title }}</h3>
                                    @if($group->calculated_mileage > 0)
                                        @php
                                            $badgeClass = $group->calculated_mileage >= 45 
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' 
                                                : ($group->calculated_mileage >= 35 
                                                    ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border-sky-200 dark:border-sky-800' 
                                                    : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800');
                                        @endphp
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeClass }}">
                                            {{ number_format($group->calculated_mileage, 1) }} km/L
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    From {{ number_format($group->start_odometer, 1) }} km &rarr; {{ number_format($group->end_odometer ?? $group->start_odometer, 1) }} km · 
                                    Vehicle: {{ $group->vehicle?->name ?? 'Default' }} · 
                                    {{ $group->created_at->format('d M Y') }}
                                </p>
                            </div>

                            <div class="flex items-center gap-4 sm:gap-6 self-start sm:self-center">
                                <div class="text-right">
                                    <div class="text-base font-bold text-slate-900 dark:text-white">
                                        {{ number_format($group->total_km ?? 0, 1) }} <span class="text-xs font-normal text-slate-500">km</span>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $group->total_litres ? number_format($group->total_litres, 2) . ' L' : '—' }} 
                                        @if($group->cost_per_km > 0)
                                            · ₹{{ number_format($group->cost_per_km, 2) }}/km
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <a href="{{ route('odometer.show', $group) }}" class="px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950 hover:bg-indigo-100 dark:hover:bg-indigo-900 text-indigo-700 dark:text-indigo-300 text-xs font-semibold transition">
                                        View Legs
                                    </a>

                                    <form action="{{ route('odometer.groups.destroy', $group) }}" method="POST" onsubmit="return confirm('Delete this entire cycle and all its readings?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950 text-rose-500 hover:text-rose-700 transition" title="Delete cycle">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="pt-2">
                        {{ $completedGroups->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Client-side Tab Switcher Script -->
    <script>
        function switchToTab(type) {
            const readingTypeInput = document.getElementById('reading_type');
            const fieldsSource = document.getElementById('fields-source');
            const fieldsTrip = document.getElementById('fields-trip');
            const fieldsEnding = document.getElementById('fields-ending');
            const btnSource = document.getElementById('tab-btn-source');
            const btnIntermediate = document.getElementById('tab-btn-intermediate');
            const btnEnding = document.getElementById('tab-btn-ending');
            const btnText = document.getElementById('btn-text');
            const kmHelper = document.getElementById('km-helper');

            readingTypeInput.value = type;

            // Reset tab styles
            [btnSource, btnIntermediate, btnEnding].forEach(btn => {
                btn.className = 'flex-1 py-2 px-3 rounded-lg transition text-slate-700 dark:text-slate-300 cursor-pointer flex items-center justify-center gap-1.5 hover:bg-white/50';
            });

            if (type === 'source') {
                btnSource.className = 'flex-1 py-2 px-3 rounded-lg transition bg-white dark:bg-slate-800 text-indigo-700 dark:text-indigo-300 shadow-sm font-bold flex items-center justify-center gap-1.5';
                fieldsSource.classList.remove('hidden');
                fieldsTrip.classList.add('hidden');
                fieldsEnding.classList.add('hidden');
                btnText.textContent = 'Start New Cycle (Source)';
                kmHelper.textContent = 'Enter initial starting odometer reading (e.g. 38509.0).';
            } else if (type === 'intermediate') {
                btnIntermediate.className = 'flex-1 py-2 px-3 rounded-lg transition bg-white dark:bg-slate-800 text-sky-700 dark:text-sky-300 shadow-sm font-bold flex items-center justify-center gap-1.5';
                fieldsSource.classList.add('hidden');
                fieldsTrip.classList.remove('hidden');
                fieldsEnding.classList.add('hidden');
                btnText.textContent = 'Save Trip Leg (Intermediate)';
                kmHelper.textContent = 'Enter current odometer reading to calculate trip leg distance.';
            } else if (type === 'ending') {
                btnEnding.className = 'flex-1 py-2 px-3 rounded-lg transition bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 shadow-sm font-bold flex items-center justify-center gap-1.5';
                fieldsSource.classList.add('hidden');
                fieldsTrip.classList.remove('hidden');
                fieldsEnding.classList.remove('hidden');
                btnText.textContent = 'Finish Cycle & Calculate Mileage';
                kmHelper.textContent = 'Enter final odometer reading at refuel point.';
            }
        }

        // Initialize with proper active tab on page load
        document.addEventListener('DOMContentLoaded', () => {
            const hasActive = @json((bool)$activeGroup);
            switchToTab(hasActive ? 'intermediate' : 'source');
        });
    </script>
</x-app-layout>

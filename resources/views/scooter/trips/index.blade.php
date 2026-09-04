<x-app-layout title="Scooter Trips">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Scooter Trips</h1>
                <p class="text-sm text-slate-500 mt-0.5">Log completed rides with start/end places, distance, and petrol from mileage.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('petrol.index') }}" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                    Petrol Log
                </a>
                <a href="{{ route('scooter.plan') }}" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm shadow-sm transition">
                    Log completed trip
                </a>
            </div>
        </div>

        <!-- ONGOING TRIP BANNER -->
        @if($activeTrip)
            <div class="p-5 rounded-3xl bg-gradient-to-r from-sky-500 via-cyan-600 to-blue-600 text-white shadow-lg shadow-sky-500/20 border border-sky-400/40 space-y-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-cyan-500/20 flex items-center justify-center text-xl animate-pulse">🛵</span>
                        <div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-400 text-slate-950 uppercase tracking-wider">Trip In Progress</span>
                            <h2 class="font-bold text-lg mt-1">{{ $activeTrip->title ?? 'Ongoing Ride' }}</h2>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-mono text-cyan-200">Started {{ \Carbon\Carbon::parse($activeTrip->start_time)->format('g:i A') }}</span>
                        @if($activeTrip->start_address)
                            <p class="text-xs text-cyan-100 mt-0.5">📍 From: {{ $activeTrip->start_address }}</p>
                        @endif
                        @if($activeTrip->start_latitude && $activeTrip->start_longitude)
                            <p class="text-[10px] text-cyan-300 font-mono">{{ number_format($activeTrip->start_latitude, 5) }}, {{ number_format($activeTrip->start_longitude, 5) }}</p>
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $activeTrip->start_latitude }},{{ $activeTrip->start_longitude }}" target="_blank"
                               class="inline-block mt-1 text-[10px] bg-white/20 hover:bg-white/30 px-2 py-0.5 rounded-full transition font-semibold">
                                🗺️ View Start on Maps
                            </a>
                        @endif
                    </div>
                </div>

                @if($activeTrip->to_and_fro)
                    <div class="flex items-center gap-2 text-xs bg-white/10 rounded-xl px-3 py-1.5 w-fit">
                        <span>🔁</span>
                        <span class="font-semibold">Round Trip (To &amp; Fro) — distance will be doubled</span>
                    </div>
                @endif

                <!-- End Trip Form -->
                <form action="{{ route('scooter.end', $activeTrip) }}" method="POST" enctype="multipart/form-data"
                      class="pt-3 border-t border-cyan-800/60 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                    @csrf
                    <input type="hidden" name="end_latitude" id="end-trip-lat">
                    <input type="hidden" name="end_longitude" id="end-trip-lon">

                    <div>
                        <label class="block text-cyan-200 text-xs font-semibold mb-1">📍 Destination</label>
                        <div class="flex gap-1.5">
                            <input type="text" name="end_address" id="end-trip-addr" value="Current GPS Location" required
                                   class="flex-1 px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-white text-xs placeholder-white/50">
                            <button type="button" onclick="window.fetchEndLocation()" class="px-2.5 py-1.5 rounded-xl bg-cyan-500 text-slate-950 font-bold text-xs whitespace-nowrap">📍 GPS</button>
                        </div>
                        <p id="end-coords-preview" class="text-[10px] text-cyan-300 font-mono mt-1 hidden"></p>
                    </div>

                    <div>
                        <label class="block text-cyan-200 text-xs font-semibold mb-1">📷 Speedometer (Optional)</label>
                        <input type="file" name="speedometer_image" accept="image/*"
                               class="w-full px-3 py-1.5 rounded-xl bg-white/10 border border-white/20 text-white text-xs">
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-bold text-sm shadow-md transition">
                            🏁 Finish &amp; End Trip
                        </button>
                    </div>
                </form>
            </div>

            <script>
                window.fetchEndLocation = async function() {
                    try {
                        const coords = await window.getCurrentCoordinates();
                        document.getElementById('end-trip-lat').value = coords.latitude;
                        document.getElementById('end-trip-lon').value = coords.longitude;
                        document.getElementById('end-trip-addr').value = `${coords.latitude.toFixed(5)}, ${coords.longitude.toFixed(5)}`;
                        const preview = document.getElementById('end-coords-preview');
                        preview.textContent = `✅ GPS: ${coords.latitude.toFixed(6)}, ${coords.longitude.toFixed(6)}`;
                        preview.classList.remove('hidden');
                        window.showToast('📍 End Location Acquired!', 'info');
                    } catch (e) {
                        window.showToast('GPS: ' + e.message, 'warning');
                    }
                };
            </script>
        @endif

        <!-- Metrics -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Distance</span>
                <div class="text-2xl font-bold text-cyan-600 mt-1">{{ number_format($totalDistance, 1) }} <span class="text-base font-medium text-slate-400">km</span></div>
                <p class="text-xs text-slate-500 mt-1">~{{ number_format($totalLitres ?? 0, 2) }} L · ₹{{ number_format($totalFuelCost ?? 0, 0) }}</p>
                <span class="text-[11px] text-slate-400">GPS recorded</span>
            </div>
            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Trips</span>
                <div class="text-2xl font-bold text-slate-900 mt-1">{{ $totalTrips }}</div>
                <span class="text-[11px] text-slate-400">Completed</span>
            </div>
            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Riding Time</span>
                <div class="text-2xl font-bold text-slate-900 mt-1">{{ $totalDuration }} <span class="text-base font-medium text-slate-400">min</span></div>
                <span class="text-[11px] text-slate-400">~{{ round($totalDuration / 60, 1) }} hrs</span>
            </div>
            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Latest Odometer</span>
                <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($latestOdometer) }} <span class="text-base font-medium text-slate-400">km</span></div>
                <span class="text-[11px] text-slate-400">Speedometer reading</span>
            </div>
        </div>

        <!-- Completed Trips List -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 font-bold text-base text-slate-900">
                Trip History
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($trips as $t)
                    <div class="p-4 hover:bg-slate-50/60 transition">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                            <!-- Left: Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="font-bold text-slate-900 text-sm">{{ $t->title ?? 'Scooter Ride' }}</span>
                                    <span class="px-2.5 py-0.5 rounded-full bg-cyan-100 text-cyan-800 font-mono font-bold text-xs">
                                        {{ number_format((float) ($t->distance_km ?? 0), 2) }} km
                                    </span>
                                    @if($t->one_way_km !== null)
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-semibold">
                                            One way {{ number_format((float) $t->one_way_km, 2) }} km
                                        </span>
                                    @endif
                                    @if($t->estimated_litres)
                                        <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 text-[10px] font-semibold">
                                            ~{{ number_format((float) $t->estimated_litres, 3) }} L
                                            @if($t->estimated_fuel_cost)
                                                · ₹{{ number_format((float) $t->estimated_fuel_cost, 0) }}
                                            @endif
                                        </span>
                                    @endif
                                    @if($t->to_and_fro)
                                        <span class="px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 text-[10px] font-semibold">Round trip</span>
                                    @endif
                                    @if($t->vehicle)
                                        <span class="px-2 py-0.5 rounded-full bg-slate-50 text-slate-500 text-[10px] font-semibold">{{ $t->vehicle->name }} · {{ $t->vehicle->effectiveMileage() }} km/L</span>
                                    @endif
                                </div>

                                <!-- Route: Source → Stops → Destination -->
                                <div class="text-xs text-slate-500 space-y-0.5">
                                    <div class="flex items-start gap-1.5">
                                        <span class="text-green-500 mt-0.5">🟢</span>
                                        <span>
                                            <span class="font-semibold text-slate-700">From:</span>
                                            {{ $t->from_label ?? $t->start_address ?? 'Unknown' }}
                                            @if($t->start_latitude && $t->start_longitude)
                                                <span class="text-slate-400 font-mono">({{ number_format($t->start_latitude, 4) }}, {{ number_format($t->start_longitude, 4) }})</span>
                                            @endif
                                        </span>
                                    </div>

                                    @if($t->stops && count($t->stops) > 0)
                                        @foreach($t->stops as $stop)
                                            <div class="flex items-start gap-1.5 ml-2">
                                                <span class="text-yellow-500 mt-0.5">🟡</span>
                                                <span>
                                                    <span class="font-semibold text-slate-600">Stop:</span>
                                                    {{ $stop['label'] ?? 'Unnamed stop' }}
                                                    @if(!empty($stop['lat']) && !empty($stop['lng']))
                                                        <span class="text-slate-400 font-mono">({{ number_format($stop['lat'], 4) }}, {{ number_format($stop['lng'], 4) }})</span>
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    @endif

                                    <div class="flex items-start gap-1.5">
                                        <span class="text-red-500 mt-0.5">🔴</span>
                                        <span>
                                            <span class="font-semibold text-slate-700">To:</span>
                                            {{ $t->to_label ?? $t->end_address ?? 'Unknown' }}
                                            @if($t->end_latitude && $t->end_longitude)
                                                <span class="text-slate-400 font-mono">({{ number_format($t->end_latitude, 4) }}, {{ number_format($t->end_longitude, 4) }})</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                <p class="text-[11px] text-slate-400 mt-1.5">
                                    {{ $t->date->format('d M Y') }}
                                    @if($t->start_time)• {{ \Carbon\Carbon::parse($t->start_time)->format('g:i A') }}@endif
                                    @if($t->end_time)→ {{ \Carbon\Carbon::parse($t->end_time)->format('g:i A') }}@endif
                                </p>
                            </div>

                            <!-- Right: Actions -->
                            <div class="flex flex-row sm:flex-col items-center sm:items-end gap-2 flex-shrink-0">
                                @if($t->start_latitude && $t->start_longitude && $t->end_latitude && $t->end_longitude)
                                    <a href="https://www.google.com/maps/dir/?api=1&origin={{ $t->start_latitude }},{{ $t->start_longitude }}&destination={{ $t->end_latitude }},{{ $t->end_longitude }}"
                                       target="_blank"
                                       class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 text-xs font-semibold hover:bg-blue-100 transition">
                                        Maps Route
                                    </a>
                                @endif
                                <a href="{{ route('scooter.edit', $t) }}"
                                   class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                                    Edit
                                </a>
                                <form action="{{ route('scooter.destroy', $t) }}" method="POST" onsubmit="return confirm('Delete this trip?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-50 text-rose-700 text-xs font-semibold hover:bg-rose-100 transition">
                                        Delete
                                    </button>
                                </form>
                                @if($t->speedometer_image)
                                    <a href="{{ asset('storage/' . $t->speedometer_image) }}" target="_blank"
                                       class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-semibold hover:bg-slate-200 transition">
                                        Odo
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center">
                        <div class="text-4xl mb-3">🛵</div>
                        <p class="text-slate-400 text-sm">No completed trips yet. Start your first ride!</p>
                    </div>
                @endforelse
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $trips->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

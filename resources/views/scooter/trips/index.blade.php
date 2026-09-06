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

        <!-- Ongoing GPS trip (optional) — dismiss if you only log completed trips -->
        @if($activeTrip)
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-sm space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold text-amber-900">Unfinished live trip: {{ $activeTrip->title ?? 'Ongoing Ride' }}</p>
                        <p class="text-xs text-amber-800 mt-0.5">You can discard this and use “Log completed trip” with start/end places instead.</p>
                    </div>
                    <form action="{{ route('scooter.destroy', $activeTrip) }}" method="POST" onsubmit="return confirm('Discard this unfinished trip?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-2 rounded-xl bg-white border border-amber-300 text-amber-900 text-xs font-semibold hover:bg-amber-100">Discard unfinished trip</button>
                    </form>
                </div>
            </div>
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

                                    @php $stops = is_array($t->stops) ? $t->stops : []; @endphp
                                    @if(count($stops) > 0)
                                        @foreach($stops as $stop)
                                            <div class="flex items-start gap-1.5 ml-2">
                                                <span class="text-yellow-500 mt-0.5">•</span>
                                                <span>
                                                    <span class="font-semibold text-slate-600">Stop:</span>
                                                    {{ is_array($stop) ? ($stop['label'] ?? 'Unnamed stop') : (string) $stop }}
                                                    @if(is_array($stop) && !empty($stop['lat']) && !empty($stop['lng']))
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
                                <a href="{{ $t->mapsDirUrl() }}"
                                   target="_blank"
                                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 text-xs font-semibold hover:bg-blue-100 transition">
                                    Maps Route
                                </a>
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

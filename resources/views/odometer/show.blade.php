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

                            <button type="button" onclick='openEditReadingModal(@json($reading))' class="p-2 rounded-xl text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-slate-700 transition cursor-pointer" title="Edit reading">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>

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

    <!-- DIALOG MODAL: EDIT ODOMETER READING -->
    <div id="edit-reading-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto" onclick="if(event.target === this) closeEditReadingModal()">
        <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden my-8" onclick="event.stopPropagation()">
            <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>✏️</span>
                        <span>Edit Odometer Reading</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Update reading details. Leg distances will recalculate automatically.
                    </p>
                </div>
                <button type="button" onclick="closeEditReadingModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center text-lg leading-none transition">
                    &times;
                </button>
            </div>

            <form id="edit-reading-form" action="" method="POST" enctype="multipart/form-data" class="p-5 sm:p-6 space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label for="edit_odometer_km" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Odometer (km) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.1" name="odometer_km" id="edit_odometer_km" required
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="edit_reading_date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="reading_date" id="edit_reading_date" required
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="edit_reading_time" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Time
                        </label>
                        <input type="time" name="reading_time" id="edit_reading_time"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label for="edit_trip_name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Trip Name / Purpose
                        </label>
                        <input type="text" name="trip_name" id="edit_trip_name"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="edit_source_location" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            From (Location)
                        </label>
                        <input type="text" name="source_location" id="edit_source_location"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="edit_destination" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            To (Destination)
                        </label>
                        <input type="text" name="destination" id="edit_destination"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label for="edit_duration_minutes" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Duration (Minutes)
                        </label>
                        <input type="number" name="duration_minutes" id="edit_duration_minutes" min="1" max="1440"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="edit_odometer_image" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Replace Photo (Optional)
                        </label>
                        <input type="file" name="odometer_image" id="edit_odometer_image" accept="image/*"
                            class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-300">
                    </div>
                </div>

                <div>
                    <label for="edit_notes" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Notes
                    </label>
                    <input type="text" name="notes" id="edit_notes"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="closeEditReadingModal()"
                        class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-md shadow-indigo-600/20 transition cursor-pointer">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditReadingModal(reading) {
            const form = document.getElementById('edit-reading-form');
            form.action = `/odometer/readings/${reading.id}`;

            document.getElementById('edit_odometer_km').value = reading.odometer_km;
            document.getElementById('edit_reading_date').value = reading.reading_date ? reading.reading_date.substring(0, 10) : '';
            document.getElementById('edit_reading_time').value = reading.reading_time ? reading.reading_time.substring(0, 5) : '';
            document.getElementById('edit_trip_name').value = reading.trip_name || '';
            document.getElementById('edit_source_location').value = reading.source_location || '';
            document.getElementById('edit_destination').value = reading.destination || '';
            document.getElementById('edit_duration_minutes').value = reading.duration_minutes || '';
            document.getElementById('edit_notes').value = reading.notes || '';

            document.getElementById('edit-reading-modal').classList.remove('hidden');
        }

        function closeEditReadingModal() {
            document.getElementById('edit-reading-modal').classList.add('hidden');
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeEditReadingModal();
            }
        });
    </script>
</x-app-layout>


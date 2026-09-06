<x-app-layout title="Edit Trip">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a href="{{ route('scooter.index') }}" class="text-sm font-semibold text-slate-600 hover:underline">&larr; Back to trips</a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 mt-2">Edit trip</h1>
            <p class="text-sm text-slate-500">Fix start/end places, recalculate one-way and round-trip km + petrol.</p>
        </div>

        <form action="{{ route('scooter.update', $trip) }}" method="POST" class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4 text-sm" id="trip-plan-form">
            @csrf
            @method('PUT')
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Title</label>
                <input type="text" name="title" value="{{ old('title', $trip->title) }}" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
            </div>

            <div class="relative">
                <label class="block font-semibold text-slate-700 mb-1">Start location *</label>
                <input type="text" id="from_label" name="from_label" required autocomplete="off" placeholder="112, Goldwins, Chinniyampalayam, Coimbatore 641062" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl" value="{{ old('from_label', $trip->from_label ?? $trip->start_address) }}">
                <input type="hidden" name="start_latitude" id="start_latitude" value="{{ old('start_latitude', $trip->start_latitude) }}">
                <input type="hidden" name="start_longitude" id="start_longitude" value="{{ old('start_longitude', $trip->start_longitude) }}">
                <input type="hidden" name="start_address" id="start_address" value="{{ old('start_address', $trip->start_address) }}">
                <p id="from_picked" class="mt-1 text-xs text-slate-500 {{ $trip->start_latitude ? '' : 'hidden' }}">@if($trip->start_latitude)Selected coords: {{ number_format($trip->start_latitude, 5) }}, {{ number_format($trip->start_longitude, 5) }}@endif</p>
                <a id="from_maps" href="https://www.google.com/maps/search/?api=1&query={{ $trip->start_latitude }},{{ $trip->start_longitude }}" target="_blank" class="{{ $trip->start_latitude ? '' : 'hidden' }} text-xs font-semibold text-slate-700 hover:underline">View start on Google Maps</a>
                <div id="from_suggest" class="geo-suggest hidden"></div>
            </div>

            <div class="relative">
                <label class="block font-semibold text-slate-700 mb-1">End location *</label>
                <input type="text" id="to_label" name="to_label" required autocomplete="off" placeholder="414-A Tex Park Road, Nehru Nagar West, Coimbatore 641014" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl" value="{{ old('to_label', $trip->to_label ?? $trip->end_address) }}">
                <input type="hidden" name="end_latitude" id="end_latitude" value="{{ old('end_latitude', $trip->end_latitude) }}">
                <input type="hidden" name="end_longitude" id="end_longitude" value="{{ old('end_longitude', $trip->end_longitude) }}">
                <input type="hidden" name="end_address" id="end_address" value="{{ old('end_address', $trip->end_address) }}">
                <p id="to_picked" class="mt-1 text-xs text-slate-500 {{ $trip->end_latitude ? '' : 'hidden' }}">@if($trip->end_latitude)Selected coords: {{ number_format($trip->end_latitude, 5) }}, {{ number_format($trip->end_longitude, 5) }}@endif</p>
                <a id="to_maps" href="https://www.google.com/maps/search/?api=1&query={{ $trip->end_latitude }},{{ $trip->end_longitude }}" target="_blank" class="{{ $trip->end_latitude ? '' : 'hidden' }} text-xs font-semibold text-slate-700 hover:underline">View end on Google Maps</a>
                <div id="to_suggest" class="geo-suggest hidden"></div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Vehicle</label>
                    <select name="vehicle_id" id="vehicle_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" data-mileage="{{ $v->effectiveMileage() }}" @selected(old('vehicle_id', $trip->vehicle_id) == $v->id)>
                                {{ $v->name }} — Mileage {{ $v->effectiveMileage() }} km/L
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Date</label>
                    <input type="date" name="date" value="{{ old('date', $trip->date?->toDateString() ?? date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
            </div>

            <label class="flex items-center gap-2 font-semibold text-slate-700">
                <input type="checkbox" name="to_and_fro" id="to_and_fro" value="1" @checked(old('to_and_fro', $trip->to_and_fro)) class="rounded border-slate-300">
                To and fro (double distance & petrol)
            </label>

            <div id="route-preview" class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-sm space-y-1.5">
                <p class="font-semibold text-slate-800">Distance & petrol</p>
                <p>Mileage: <strong id="preview-mileage">{{ number_format($defaultVehicle?->effectiveMileage() ?? 40, 1) }}</strong> km/L</p>
                <p>One way: <strong id="preview-one-way">{{ $trip->one_way_km !== null ? number_format($trip->one_way_km, 2) : '—' }}</strong> km</p>
                <p>Trip distance: <strong id="preview-total">{{ $trip->distance_km !== null ? number_format($trip->distance_km, 2) : '—' }}</strong> km</p>
                <p>Est. petrol used: <strong id="preview-litres">{{ $trip->estimated_litres !== null ? number_format($trip->estimated_litres, 3) : '—' }}</strong> L</p>
                <a id="dir_maps" href="https://www.google.com/maps/dir/?api=1&origin={{ $trip->start_latitude }},{{ $trip->start_longitude }}&destination={{ $trip->end_latitude }},{{ $trip->end_longitude }}" target="_blank" class="{{ $trip->start_latitude && $trip->end_latitude ? '' : 'hidden' }} inline-block pt-1 text-xs font-semibold text-slate-700 hover:underline">Open route on Google Maps</a>
                <p id="preview-hint" class="text-xs text-slate-400 {{ $trip->start_latitude && $trip->end_latitude ? 'hidden' : '' }}">Re-pick suggestions if the map pin was wrong.</p>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">{{ old('notes', $trip->notes) }}</textarea>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold">Save changes & recalculate</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const vehicleSelect = document.getElementById('vehicle_id');
            const getMileage = () => parseFloat(vehicleSelect?.selectedOptions?.[0]?.dataset?.mileage || '40');
            const refresh = () => window.previewTripRoute(getMileage());

            window.bindPlaceAutocomplete('from_label', 'from_suggest', {
                lat: 'start_latitude', lng: 'start_longitude', address: 'start_address',
                picked: 'from_picked', maps: 'from_maps'
            }, refresh);
            window.bindPlaceAutocomplete('to_label', 'to_suggest', {
                lat: 'end_latitude', lng: 'end_longitude', address: 'end_address',
                picked: 'to_picked', maps: 'to_maps'
            }, refresh);

            vehicleSelect?.addEventListener('change', refresh);
            document.getElementById('to_and_fro')?.addEventListener('change', refresh);

            document.getElementById('trip-plan-form')?.addEventListener('submit', async (e) => {
                const startLat = document.getElementById('start_latitude');
                const endLat = document.getElementById('end_latitude');
                if (startLat?.value && endLat?.value) return;
                e.preventDefault();
                const form = e.target;
                const resolve = async (inputId, latId, lngId, addressId) => {
                    const latEl = document.getElementById(latId);
                    if (latEl?.value) return true;
                    const q = document.getElementById(inputId)?.value?.trim() || '';
                    if (q.length < 3) return false;
                    try {
                        const res = await fetch(`/geo/search?q=${encodeURIComponent(q)}`);
                        const data = await res.json();
                        if (!Array.isArray(data) || !data[0]) return false;
                        document.getElementById(inputId).value = data[0].label;
                        latEl.value = data[0].lat;
                        document.getElementById(lngId).value = data[0].lng;
                        document.getElementById(addressId).value = data[0].label;
                        return true;
                    } catch (err) {
                        return false;
                    }
                };
                await resolve('from_label', 'start_latitude', 'start_longitude', 'start_address');
                await resolve('to_label', 'end_latitude', 'end_longitude', 'end_address');
                form.submit();
            });

            refresh();
        });
    </script>
</x-app-layout>

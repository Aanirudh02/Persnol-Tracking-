import Chart from 'chart.js/auto';

window.Chart = Chart;

// Global Toast System
window.showToast = function (message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    const colors = {
        success: 'bg-emerald-600 text-white shadow-emerald-500/20',
        error: 'bg-rose-600 text-white shadow-rose-500/20',
        info: 'bg-indigo-600 text-white shadow-indigo-500/20',
        warning: 'bg-amber-600 text-white shadow-amber-500/20',
    };

    toast.className = `flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium transition-all duration-300 transform translate-y-2 opacity-0 ${colors[type] || colors.info}`;
    const text = document.createElement('span');
    text.textContent = message;
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'ml-auto opacity-75 hover:opacity-100';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.textContent = '×';
    close.addEventListener('click', () => toast.remove());
    toast.append(text, close);

    container.appendChild(toast);
    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
};

// Geolocation helper
window.getCurrentCoordinates = function() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Geolocation is not supported by your browser'));
        } else {
            navigator.geolocation.getCurrentPosition(
                (pos) => resolve({
                    latitude: pos.coords.latitude,
                    longitude: pos.coords.longitude
                }),
                (err) => reject(err),
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }
    });
};

// Reverse geocoding helper (fallback display)
window.fetchAddressFromCoords = async function(lat, lon) {
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`);
        if (!res.ok) return `${lat.toFixed(4)}, ${lon.toFixed(4)}`;
        const data = await res.json();
        return data.display_name || `${lat.toFixed(4)}, ${lon.toFixed(4)}`;
    } catch (e) {
        return `${lat.toFixed(4)}, ${lon.toFixed(4)}`;
    }
};

// Light theme only — keep helpers for compatibility but force light
window.initTheme = function() {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');
};
window.toggleTheme = function() {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');
};
window.initTheme();

// Keep inline modal implementations consistent across the legacy Blade views.
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    document.querySelectorAll('.fixed:not(.hidden)').forEach((element) => {
        if (element.id === 'toast-container') return;
        if (element.classList.contains('z-50')) element.classList.add('hidden');
    });
});

const syncModalScrollLock = () => {
    const hasOpenModal = [...document.querySelectorAll('.fixed.z-50')]
        .some((element) => !element.classList.contains('hidden'));
    document.body.classList.toggle('modal-open', hasOpenModal);
};

const modalObserver = new MutationObserver(syncModalScrollLock);
modalObserver.observe(document.documentElement, { attributes: true, subtree: true, attributeFilter: ['class'] });
document.addEventListener('DOMContentLoaded', syncModalScrollLock);

function debounce(fn, ms) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    };
}

window.bindPlaceAutocomplete = function(inputId, suggestId, hiddenIds, onPick) {
    const input = document.getElementById(inputId);
    const box = document.getElementById(suggestId);
    if (!input || !box) return;
    let latestResults = [];

    const applyPlace = (label, lat, lng) => {
        input.value = label;
        if (hiddenIds.lat) document.getElementById(hiddenIds.lat).value = lat;
        if (hiddenIds.lng) document.getElementById(hiddenIds.lng).value = lng;
        if (hiddenIds.address) document.getElementById(hiddenIds.address).value = label;
        if (hiddenIds.picked) {
            const picked = document.getElementById(hiddenIds.picked);
            if (picked) {
                picked.textContent = 'Selected: ' + label;
                picked.classList.remove('hidden');
            }
        }
        if (hiddenIds.maps) {
            const maps = document.getElementById(hiddenIds.maps);
            if (maps) {
                maps.href = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
                maps.classList.remove('hidden');
            }
        }
        box.classList.add('hidden');
        if (typeof onPick === 'function') onPick();
    };

    const run = debounce(async () => {
        const q = input.value.trim();
        if (q.length < 3) {
            box.classList.add('hidden');
            box.innerHTML = '';
            return;
        }
        try {
            const res = await fetch(`/geo/search?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            if (!Array.isArray(data) || !data.length) {
                box.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">No matches — try a shorter place name</div>';
                box.classList.remove('hidden');
                return;
            }
            latestResults = data;
            box.innerHTML = '';
            data.forEach((place) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.lat = place.lat;
                button.dataset.lng = place.lng;
                button.dataset.label = place.label;
                button.textContent = place.label;
                box.appendChild(button);
            });
            box.classList.remove('hidden');
            box.querySelectorAll('button').forEach((btn) => {
                btn.addEventListener('click', () => applyPlace(btn.dataset.label, btn.dataset.lat, btn.dataset.lng));
            });
        } catch (e) {
            box.innerHTML = '<div class="px-3 py-2 text-xs text-rose-500">Search failed — check connection</div>';
            box.classList.remove('hidden');
        }
    }, 350);

    input.addEventListener('input', () => {
        latestResults = [];
        if (hiddenIds.lat) document.getElementById(hiddenIds.lat).value = '';
        if (hiddenIds.lng) document.getElementById(hiddenIds.lng).value = '';
        if (hiddenIds.picked) {
            const picked = document.getElementById(hiddenIds.picked);
            if (picked) picked.classList.add('hidden');
        }
        if (hiddenIds.maps) {
            const maps = document.getElementById(hiddenIds.maps);
            if (maps) maps.classList.add('hidden');
        }
        run();
    });

    input.addEventListener('keydown', async (event) => {
        if (event.key !== 'Enter') return;

        event.preventDefault();
        const q = input.value.trim();
        if (q.length < 3) return;

        try {
            const data = latestResults.length
                ? latestResults
                : await fetch(`/geo/search?q=${encodeURIComponent(q)}`).then((response) => response.json());
            if (!Array.isArray(data) || !data[0]) return;

            applyPlace(data[0].label, data[0].lat, data[0].lng);
            input.form?.requestSubmit();
        } catch (e) {
            latestResults = [];
        }
    });

    document.addEventListener('click', (e) => {
        if (!box.contains(e.target) && e.target !== input) box.classList.add('hidden');
    });
};

// ─── Trip Map State ────────────────────────────────────────────────────────
let _tripMap = null;
let _tripMapLayer = null;
let _tripMapMarkers = [];

function _ensureTripMap() {
    const container = document.getElementById('trip-map-preview');
    if (!container) return false;
    if (_tripMap) return true;

    if (typeof window.L === 'undefined') return false;

    _tripMap = window.L.map(container, { zoomControl: true, attributionControl: true });
    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(_tripMap);

    return true;
}

function _clearTripMapOverlay() {
    if (_tripMapLayer) { _tripMapLayer.remove(); _tripMapLayer = null; }
    _tripMapMarkers.forEach(m => m.remove());
    _tripMapMarkers = [];
}

function _makeMarker(lat, lng, color, label) {
    if (!_tripMap || typeof window.L === 'undefined') return;
    const icon = window.L.divIcon({
        html: `<div style="
            width:28px;height:28px;border-radius:50% 50% 50% 0;
            background:${color};transform:rotate(-45deg);
            border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,.3);">
        </div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 28],
        className: '',
    });
    const marker = window.L.marker([lat, lng], { icon })
        .bindTooltip(label, { permanent: false, direction: 'top', offset: [0, -30] })
        .addTo(_tripMap);
    _tripMapMarkers.push(marker);
    return marker;
}

// ─── Collect stops from dynamic stop rows ──────────────────────────────────
window.getStopsFromUI = function() {
    const rows = document.querySelectorAll('.stop-row');
    const stops = [];
    rows.forEach(row => {
        const lat = row.querySelector('[data-stop-lat]')?.value;
        const lng = row.querySelector('[data-stop-lng]')?.value;
        const label = row.querySelector('[data-stop-label]')?.value;
        if (lat && lng) {
            stops.push({ lat: parseFloat(lat), lng: parseFloat(lng), label: label || 'Stop' });
        }
    });
    return stops;
};

// ─── Build hidden stops JSON for form submission ───────────────────────────
window.syncStopsInput = function() {
    const stops = window.getStopsFromUI();
    const hidden = document.getElementById('stops_json');
    if (hidden) hidden.value = JSON.stringify(stops);
};

// ─── Update the "Open in Google Maps" link with stops ─────────────────────
function _updateDirMapsLink(slat, slng, elat, elng, stops) {
    const dirMaps = document.getElementById('dir_maps');
    if (!dirMaps) return;
    let url = `https://www.google.com/maps/dir/?api=1&origin=${slat},${slng}&destination=${elat},${elng}&travelmode=driving`;
    if (stops.length) {
        url += '&waypoints=' + stops.map(s => `${s.lat},${s.lng}`).join('|');
    }
    dirMaps.href = url;
    dirMaps.classList.remove('hidden');
}

// ─── Main preview & map updater ────────────────────────────────────────────
window.previewTripRoute = async function(mileage = 40) {
    const slat = document.getElementById('start_latitude')?.value;
    const slng = document.getElementById('start_longitude')?.value;
    const elat = document.getElementById('end_latitude')?.value;
    const elng = document.getElementById('end_longitude')?.value;

    const preview = document.getElementById('route-preview');
    const mileageEl = document.getElementById('preview-mileage');
    const hint = document.getElementById('preview-hint');
    const dirMaps = document.getElementById('dir_maps');

    if (mileageEl) mileageEl.textContent = Number(mileage).toFixed(1);
    if (!preview) return;

    if (!slat || !slng || !elat || !elng) {
        document.getElementById('preview-one-way').textContent = '—';
        document.getElementById('preview-total').textContent = '—';
        document.getElementById('preview-litres').textContent = '—';
        if (hint) hint.classList.remove('hidden');
        if (dirMaps) dirMaps.classList.add('hidden');
        return;
    }

    const stops = window.getStopsFromUI();
    window.syncStopsInput();

    const waypoints = [
        { lat: parseFloat(slat), lng: parseFloat(slng) },
        ...stops.map(s => ({ lat: s.lat, lng: s.lng })),
        { lat: parseFloat(elat), lng: parseFloat(elng) },
    ];

    // ── Distance calculation ──
    try {
        const res = await fetch(`/geo/route?waypoints=${encodeURIComponent(JSON.stringify(waypoints))}`);
        const data = await res.json();
        const oneWay = data.distance_km;
        if (oneWay == null) return;

        const toAndFro = document.querySelector('input[name="to_and_fro"]')?.checked;
        const total = toAndFro ? oneWay * 2 : oneWay;
        const litres = mileage > 0 ? (total / mileage) : 0;

        if (hint) hint.classList.add('hidden');
        document.getElementById('preview-one-way').textContent = oneWay.toFixed(2);
        document.getElementById('preview-total').textContent = total.toFixed(2);
        document.getElementById('preview-litres').textContent = litres.toFixed(3);
        _updateDirMapsLink(slat, slng, elat, elng, stops);
    } catch (e) {}

    // ── Map preview (Leaflet / OSM) ──
    if (!_ensureTripMap()) return;
    _clearTripMapOverlay();

    try {
        // Fetch OSRM route geometry
        const coords = waypoints.map(w => `${w.lng},${w.lat}`).join(';');
        const routeRes = await fetch(
            `https://router.project-osrm.org/route/v1/driving/${coords}?overview=full&geometries=geojson`,
            { headers: { 'User-Agent': 'LifeTracker/1.0' } }
        );
        const routeData = await routeRes.json();
        const geometry = routeData?.routes?.[0]?.geometry;

        if (geometry) {
            _tripMapLayer = window.L.geoJSON(geometry, {
                style: { color: '#3b82f6', weight: 4, opacity: 0.85 },
            }).addTo(_tripMap);
        }

        // Add markers
        _makeMarker(parseFloat(slat), parseFloat(slng), '#16a34a', 'Start');
        stops.forEach((s, i) => _makeMarker(s.lat, s.lng, '#f59e0b', `Stop ${i + 1}: ${s.label}`));
        _makeMarker(parseFloat(elat), parseFloat(elng), '#dc2626', 'End');

        // Fit map bounds
        const allPoints = waypoints.map(w => [w.lat, w.lng]);
        _tripMap.fitBounds(window.L.latLngBounds(allPoints).pad(0.15));
    } catch (e) {
        // Fallback: just centre on start/end midpoint
        const midLat = (parseFloat(slat) + parseFloat(elat)) / 2;
        const midLng = (parseFloat(slng) + parseFloat(elng)) / 2;
        _makeMarker(parseFloat(slat), parseFloat(slng), '#16a34a', 'Start');
        _makeMarker(parseFloat(elat), parseFloat(elng), '#dc2626', 'End');
        _tripMap.setView([midLat, midLng], 12);
    }
};

// ─── Stop row management ───────────────────────────────────────────────────
let _stopIndex = 0;

window.addStopRow = function(initialLabel = '', initialLat = '', initialLng = '') {
    const container = document.getElementById('stops-container');
    if (!container) return;

    const idx = _stopIndex++;
    const row = document.createElement('div');
    row.className = 'stop-row';
    row.dataset.stopIdx = idx;

    const suggestId = `stop_suggest_${idx}`;
    row.innerHTML = `
        <div class="stop-input-wrap">
            <label class="block font-semibold text-slate-700 mb-1 text-xs">📍 Via (stop ${container.children.length + 1})</label>
            <input
                type="text"
                class="w-full px-3 py-2.5 bg-slate-50 border border-amber-300 rounded-xl text-sm"
                autocomplete="off"
                placeholder="e.g. Gandhipuram Bus Stand, Coimbatore"
                value="${initialLabel}"
            >
            <input type="hidden" data-stop-lat value="${initialLat}">
            <input type="hidden" data-stop-lng value="${initialLng}">
            <input type="hidden" data-stop-label value="${initialLabel}">
            <div id="${suggestId}" class="geo-suggest hidden"></div>
        </div>
        <button type="button" class="remove-stop-btn" title="Remove stop" onclick="window.removeStopRow(this)">✕</button>
    `;

    container.appendChild(row);

    // Bind autocomplete on the new input
    const input = row.querySelector('input[type="text"]');
    const box = row.querySelector('.geo-suggest');
    const latEl = row.querySelector('[data-stop-lat]');
    const lngEl = row.querySelector('[data-stop-lng]');
    const labelEl = row.querySelector('[data-stop-label]');

    const applyStop = (label, lat, lng) => {
        input.value = label;
        latEl.value = lat;
        lngEl.value = lng;
        labelEl.value = label;
        box.classList.add('hidden');
        window.syncStopsInput();
        const getMileage = () => parseFloat(document.getElementById('vehicle_id')?.selectedOptions?.[0]?.dataset?.mileage || '40');
        window.previewTripRoute(getMileage());
    };

    const run = debounce(async () => {
        const q = input.value.trim();
        if (q.length < 3) { box.classList.add('hidden'); return; }
        try {
            const res = await fetch(`/geo/search?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            if (!Array.isArray(data) || !data.length) {
                box.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">No matches</div>';
                box.classList.remove('hidden');
                return;
            }
            box.innerHTML = '';
            data.forEach(place => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = place.label;
                btn.addEventListener('click', () => applyStop(place.label, place.lat, place.lng));
                box.appendChild(btn);
            });
            box.classList.remove('hidden');
        } catch (e) {}
    }, 350);

    input.addEventListener('input', () => {
        latEl.value = ''; lngEl.value = ''; labelEl.value = '';
        run();
    });
    input.addEventListener('keydown', async (e) => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const q = input.value.trim();
        if (q.length < 3) return;
        try {
            const res = await fetch(`/geo/search?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            if (data?.[0]) applyStop(data[0].label, data[0].lat, data[0].lng);
        } catch (e) {}
    });
    document.addEventListener('click', (e) => {
        if (!box.contains(e.target) && e.target !== input) box.classList.add('hidden');
    });

    // Renumber existing stop labels
    _renumberStops();

    if (initialLat && initialLng) {
        const getMileage = () => parseFloat(document.getElementById('vehicle_id')?.selectedOptions?.[0]?.dataset?.mileage || '40');
        window.previewTripRoute(getMileage());
    }
};

window.removeStopRow = function(btn) {
    const row = btn.closest('.stop-row');
    if (row) row.remove();
    _renumberStops();
    window.syncStopsInput();
    const getMileage = () => parseFloat(document.getElementById('vehicle_id')?.selectedOptions?.[0]?.dataset?.mileage || '40');
    window.previewTripRoute(getMileage());
};

function _renumberStops() {
    const container = document.getElementById('stops-container');
    if (!container) return;
    [...container.querySelectorAll('.stop-row')].forEach((row, i) => {
        const lbl = row.querySelector('label');
        if (lbl) lbl.textContent = `📍 Via (stop ${i + 1})`;
    });
}


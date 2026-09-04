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
    toast.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-auto opacity-75 hover:opacity-100">&times;</button>
    `;

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
            box.innerHTML = data.map((p) =>
                `<button type="button" data-lat="${p.lat}" data-lng="${p.lng}" data-label="${String(p.label).replace(/"/g, '&quot;')}">${p.label}</button>`
            ).join('');
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

    // If user leaves the field without picking, auto-pick first match
    input.addEventListener('blur', async () => {
        setTimeout(async () => {
            const latEl = hiddenIds.lat ? document.getElementById(hiddenIds.lat) : null;
            if (latEl && latEl.value) return;
            const q = input.value.trim();
            if (q.length < 3) return;
            try {
                const res = await fetch(`/geo/search?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                if (Array.isArray(data) && data[0]) {
                    applyPlace(data[0].label, data[0].lat, data[0].lng);
                }
            } catch (e) {}
        }, 200);
    });

    document.addEventListener('click', (e) => {
        if (!box.contains(e.target) && e.target !== input) box.classList.add('hidden');
    });
};

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

    const waypoints = [
        { lat: parseFloat(slat), lng: parseFloat(slng) },
        { lat: parseFloat(elat), lng: parseFloat(elng) },
    ];
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
        if (dirMaps) {
            dirMaps.href = `https://www.google.com/maps/dir/?api=1&origin=${slat},${slng}&destination=${elat},${elng}`;
            dirMaps.classList.remove('hidden');
        }
    } catch (e) {}
};

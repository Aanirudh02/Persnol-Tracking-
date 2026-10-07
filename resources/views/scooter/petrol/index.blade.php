<x-app-layout title="Petrol / Fuel Tracker">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Petrol & Fuel Tracker</h1>
                <p class="text-xs text-slate-500">Track fuel fills, price per litre, fuel efficiency, and odometer milestones.</p>
            </div>
            <button onclick="document.getElementById('add-petrol-modal').classList.remove('hidden')" class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-semibold text-xs shadow-md shadow-teal-600/20 self-start transition">
                + Record Petrol Fill
            </button>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <span class="text-xs font-semibold text-slate-400">Total Spent</span>
                <div class="text-2xl font-bold text-teal-600 dark:text-teal-400 mt-1">₹{{ number_format($totalSpent, 2) }}</div>
                <span class="text-[10px] text-slate-400">Total fuel cost</span>
            </div>
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <span class="text-xs font-semibold text-slate-400">Total Litres</span>
                <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($totalLitres, 2) }} L</div>
                <span class="text-[10px] text-slate-400">Fuel volume</span>
            </div>
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <span class="text-xs font-semibold text-slate-400">Avg Price / Litre</span>
                <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">₹{{ number_format($avgPricePerLitre, 2) }}</div>
                <span class="text-[10px] text-slate-400">Per litre average</span>
            </div>
            <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <span class="text-xs font-semibold text-slate-400">Latest Odometer</span>
                <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($latestOdometer) }} km</div>
                <span class="text-[10px] text-slate-400">Current reading</span>
            </div>
        </div>

        @if(request()->filled('vehicle_id'))
            <div class="flex items-center justify-between p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 text-xs">
                <span class="font-semibold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                    <span>🛵</span> Showing petrol entries for vehicle: <strong>{{ $vehicles->firstWhere('id', request('vehicle_id'))?->name ?? 'Selected Vehicle' }}</strong>
                </span>
                <a href="{{ route('petrol.index') }}" class="px-3 py-1 rounded-xl bg-white dark:bg-slate-800 border border-amber-200 dark:border-amber-800 font-bold text-amber-700 dark:text-amber-300 hover:bg-amber-100 transition">
                    Clear Vehicle Filter
                </a>
            </div>
        @endif

        <!-- Fuel Entries Table -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 font-semibold border-b border-slate-200/80 dark:border-slate-800">
                        <tr>
                            <th class="py-3.5 px-4">Date</th>
                            <th class="py-3.5 px-4">Vehicle</th>
                            <th class="py-3.5 px-4">Amount</th>
                            <th class="py-3.5 px-4">Litres</th>
                            <th class="py-3.5 px-4">Price / L</th>
                            <th class="py-3.5 px-4">Odometer (Prev → Now)</th>
                            <th class="py-3.5 px-4">Since Last Fill</th>
                            <th class="py-3.5 px-4">Station</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($entries as $fuel)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ $fuel->date->format('d M Y') }}</td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <a href="{{ route('petrol.index', ['vehicle_id' => $fuel->vehicle_id]) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/80 hover:bg-amber-100 transition cursor-pointer" title="Click to filter by {{ $fuel->vehicle?->name ?? 'No vehicle' }}">
                                        🛵 {{ $fuel->vehicle?->name ?? 'No vehicle' }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-teal-600 dark:text-teal-400">₹{{ number_format($fuel->amount, 2) }}</div>
                                    <div class="mt-0.5 flex items-center gap-1">
                                        @if($fuel->expense && $fuel->expense->is_archived)
                                            <a href="{{ route('expenses.show', $fuel->expense_id) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60 hover:bg-purple-100 transition" title="Archived Historical Expense">
                                                <span>📦</span> Archived Expense
                                            </a>
                                        @elseif($fuel->expense && ! $fuel->expense->trashed())
                                            <a href="{{ route('expenses.show', $fuel->expense_id) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition" title="Logged as Active Expense">
                                                <span>💰</span> Expense Logged 
                                            </a>
                                        @elseif($fuel->expense_id)
                                            <div class="inline-flex items-center gap-1">
                                                <a href="{{ route('expenses.show', $fuel->expense_id) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 hover:bg-amber-100 transition" title="Linked Expense Soft-Deleted">
                                                    <span>⚠️</span> Deleted Expense
                                                </a>
                                                <form action="{{ route('petrol.link-expense', $fuel) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 dark:border-teal-800 transition cursor-pointer" title="Re-log as Normal Expense">
                                                        + Re-log
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400" title="Not logged as expense">
                                                <span>⚪</span> Not Logged
                                            </span>
                                            <form action="{{ route('petrol.link-expense', $fuel) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 dark:border-teal-800 transition cursor-pointer" title="Log as Normal Expense">
                                                    + Log
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-medium">{{ $fuel->litres }} L</td>
                                <td class="py-3 px-4 font-mono text-slate-500">₹{{ $fuel->price_per_litre }}</td>
                                @php $fill = $fillLog[$fuel->id] ?? null; @endphp
                                <td class="py-3 px-4 font-mono whitespace-nowrap">
                                    @if($fill && $fill['previous_odometer'])
                                        <span class="text-slate-400">{{ number_format($fill['previous_odometer']) }} →</span>
                                    @endif
                                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $fuel->odometer ? number_format($fuel->odometer) . ' km' : '--' }}</span>
                                    @if($fill && $fill['previous_date'])
                                        <span class="block text-[10px] text-slate-400">prev fill {{ \Carbon\Carbon::parse($fill['previous_date'])->format('d M') }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-mono whitespace-nowrap">
                                    @if($fill && $fill['distance'])
                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">+{{ number_format($fill['distance']) }} km</span>
                                        @if($fill['mileage'])
                                            <span class="block text-[10px] text-slate-400">{{ $fill['mileage'] }} km/L</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">--</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $fuel->petrol_station ?? '--' }}</td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('petrol.edit', $fuel) }}" class="mr-3 text-slate-600 hover:underline font-semibold">Edit</a>
                                    <form action="{{ route('petrol.destroy', $fuel) }}" method="POST" class="inline petrol-delete-form" data-has-expense="{{ $fuel->expense ? '1' : '0' }}">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="delete_linked_expense" value="0">
                                        <button type="submit" class="text-rose-600 hover:underline font-semibold cursor-pointer">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-8 text-center text-slate-400">No petrol records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $entries->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>

    <!-- ADD PETROL MODAL -->
    <div id="add-petrol-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-base text-slate-900 dark:text-white">Record Petrol Fill</h3>
                <button onclick="document.getElementById('add-petrol-modal').classList.add('hidden')" class="text-slate-400 text-2xl font-bold cursor-pointer">&times;</button>
            </div>
            <form action="{{ route('petrol.store') }}" method="POST" class="space-y-3 text-xs">
                @csrf

                <div>
                    <label class="block text-slate-500 mb-1">Vehicle *</label>
                    <select name="vehicle_id" id="petrol-vehicle" onchange="window.refreshFuelPreview()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-semibold">
                        @foreach($vehicles as $veh)
                            <option value="{{ $veh->id }}" @selected($defaultVehicle?->id === $veh->id)>
                                {{ $veh->name }} {{ $veh->is_default ? '(Default)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-500 mb-1">Amount (₹) *</label>
                        <input type="number" step="0.01" name="amount" id="petrol-amt" oninput="window.calcFuelPrice()" required placeholder="500.00" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-bold text-base">
                    </div>
                    <div>
                        <label class="block text-slate-500 mb-1">Litres *</label>
                        <input type="number" step="0.01" name="litres" id="petrol-litres" oninput="window.calcFuelPrice()" required placeholder="4.85" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-bold text-base">
                    </div>
                </div>

                <div class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs flex justify-between">
                    <span>Auto Price / Litre:</span>
                    <span id="petrol-price-display" class="font-mono font-bold">₹0.00 / L</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-500 mb-1">Odometer (km)</label>
                        <input type="number" name="odometer" id="petrol-odometer" oninput="window.refreshFuelPreview()" placeholder="e.g. 12430" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
                    </div>
                    <div>
                        <label class="block text-slate-500 mb-1">Date *</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
                    </div>
                </div>

                <div id="petrol-odometer-preview" class="p-2.5 rounded-xl bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-900 text-xs text-slate-600 dark:text-slate-300 space-y-0.5">
                    <div class="flex justify-between"><span>Previous fill odometer:</span><span id="petrol-prev-odometer" class="font-mono font-bold">--</span></div>
                    <div class="flex justify-between"><span>This entry:</span><span id="petrol-current-odometer" class="font-mono font-bold">--</span></div>
                    <div class="flex justify-between"><span>Distance since last fill:</span><span id="petrol-distance" class="font-mono font-bold text-teal-700 dark:text-teal-300">--</span></div>
                </div>

                <div>
                    <label class="block text-slate-500 mb-1">Petrol Station Name</label>
                    <input type="text" name="petrol_station" placeholder="e.g. Indian Oil, Bharat Petroleum" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
                </div>

                <div>
                    <label class="block text-slate-500 mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
                        <option value="UPI">UPI</option>
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                    </select>
                </div>

                <label class="flex items-start gap-2 rounded-xl border border-teal-200 bg-teal-50 p-3 text-slate-700">
                    <input type="checkbox" name="add_as_expense" value="1" class="mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                    <span><strong>Add this as an expense</strong><span class="block text-[11px] text-slate-500">Uses the amount, date, station, payment method, and notes above.</span></span>
                </label>

                <div>
                    <label class="block text-slate-500 mb-1">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Optional details" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl"></textarea>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-semibold shadow-md transition cursor-pointer">Save Petrol Entry</button>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.petrol-delete-form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!window.confirm('Delete this petrol record?')) {
                    event.preventDefault();
                    return;
                }

                if (form.dataset.hasExpense !== '1') return;

                const disassociateOnly = window.confirm(
                    'This petrol log has a linked expense.\n\nPress OK to disassociate and preserve the expense intact (disassociate let petrol expense be as such).\n\nPress Cancel if you also want to delete the linked expense.'
                );

                form.querySelector('[name="delete_linked_expense"]').value = disassociateOnly ? '0' : '1';
            });
        });

        const vehicleFuelSnapshots = @json($vehicleFuelSnapshots);

        window.refreshFuelPreview = function() {
            const snapshot = vehicleFuelSnapshots[document.getElementById('petrol-vehicle')?.value] || null;
            const current = parseInt(document.getElementById('petrol-odometer')?.value, 10);
            const lit = parseFloat(document.getElementById('petrol-litres').value) || 0;
            const prevEl = document.getElementById('petrol-prev-odometer');
            const currentEl = document.getElementById('petrol-current-odometer');
            const distanceEl = document.getElementById('petrol-distance');
            const hasPrevious = snapshot && snapshot.last_odometer !== null;

            prevEl.textContent = hasPrevious ? `${Number(snapshot.last_odometer).toLocaleString('en-IN')} km (${snapshot.last_date})` : 'No previous fill';
            currentEl.textContent = isNaN(current) ? '--' : `${current.toLocaleString('en-IN')} km`;

            if (hasPrevious && !isNaN(current)) {
                const distance = current - snapshot.last_odometer;
                const mileage = distance > 0 && lit > 0 ? ` · ${(distance / lit).toFixed(1)} km/L` : '';
                distanceEl.textContent = distance < 0 ? `⚠️ ${distance} km (lower than previous)` : `+${distance.toLocaleString('en-IN')} km${mileage}`;
            } else {
                distanceEl.textContent = '--';
            }
        };
        document.addEventListener('DOMContentLoaded', () => window.refreshFuelPreview());

        window.calcFuelPrice = function() {
            window.refreshFuelPreview();
            const amt = parseFloat(document.getElementById('petrol-amt').value) || 0;
            const lit = parseFloat(document.getElementById('petrol-litres').value) || 0;
            const display = document.getElementById('petrol-price-display');
            if (amt > 0 && lit > 0) {
                display.innerText = '₹' + (amt / lit).toFixed(2) + ' / L';
            } else {
                display.innerText = '₹0.00 / L';
            }
        };
    </script>
</x-app-layout>

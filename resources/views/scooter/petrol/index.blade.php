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
                            <th class="py-3.5 px-4">Odometer</th>
                            <th class="py-3.5 px-4">Station</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($entries as $fuel)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ $fuel->date->format('d M Y') }}</td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <a href="{{ route('petrol.index', ['vehicle_id' => $fuel->vehicle_id]) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/80 hover:bg-amber-100 transition cursor-pointer" title="Click to filter by {{ $fuel->vehicle?->name ?? 'TVS Pep+' }}">
                                        🛵 {{ $fuel->vehicle?->name ?? 'TVS Pep+' }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-teal-600 dark:text-teal-400">₹{{ number_format($fuel->amount, 2) }}</div>
                                    <div class="mt-0.5">
                                        @if($fuel->expense_id || $fuel->expense)
                                            <a href="{{ $fuel->expense_id ? route('expenses.show', $fuel->expense_id) : '#' }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition" title="Logged as Expense">
                                                <span>💰</span> Expense Logged 
                                            </a>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400" title="Not logged as expense">
                                                <span>⚪</span> Not Logged
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-medium">{{ $fuel->litres }} L</td>
                                <td class="py-3 px-4 font-mono text-slate-500">₹{{ $fuel->price_per_litre }}</td>
                                <td class="py-3 px-4 font-mono text-slate-500">{{ $fuel->odometer ? number_format($fuel->odometer) . ' km' : '--' }}</td>
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
                            <tr><td colspan="8" class="py-8 text-center text-slate-400">No petrol records found.</td></tr>
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
                    <select name="vehicle_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-semibold">
                        @foreach($vehicles as $veh)
                            <option value="{{ $veh->id }}" {{ ($defaultVehicle && $defaultVehicle->id === $veh->id) || $veh->name === 'TVS Pep+' ? 'selected' : '' }}>
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
                        <input type="number" name="odometer" placeholder="e.g. 12430" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
                    </div>
                    <div>
                        <label class="block text-slate-500 mb-1">Date *</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
                    </div>
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

        window.calcFuelPrice = function() {
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

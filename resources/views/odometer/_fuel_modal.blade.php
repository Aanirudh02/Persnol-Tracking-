<!-- MODAL: ADD / EDIT PETROL ENTRY FOR CYCLE -->
<div id="fuel-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto" onclick="if(event.target === this) closeFuelModal()">
    <div class="relative w-full max-w-xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden my-8" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-emerald-50 dark:from-slate-800/80 dark:to-slate-800">
            <div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="text-xl">⛽</span>
                    <span>Petrol Refuel & Mileage Calculation</span>
                </h3>
                <p id="fuel-modal-cycle-name" class="text-xs text-indigo-700 dark:text-indigo-300 font-medium mt-0.5">
                    Link refuel details to calculate true km/L mileage
                </p>
            </div>
            <button type="button" onclick="closeFuelModal()" class="w-8 h-8 rounded-full bg-white/80 dark:bg-slate-700 hover:bg-white text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center text-lg leading-none transition shadow-xs cursor-pointer">
                &times;
            </button>
        </div>

        <form id="fuel-modal-form" action="" method="POST" class="p-5 sm:p-6 space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="fuel_action" id="fuel_action" value="link_existing">

            <!-- Cycle Title & Vehicle -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label for="fuel_cycle_title" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Cycle Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" id="fuel_cycle_title" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label for="fuel_vehicle_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Vehicle
                    </label>
                    <select name="vehicle_id" id="fuel_vehicle_id"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->model ?? 'Vehicle' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Mode Selector Tabs -->
            <div class="space-y-1">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                    How would you like to provide the petrol refill?
                </label>
                <div class="grid grid-cols-3 gap-1.5 p-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-semibold">
                    <button type="button" onclick="switchFuelAction('link_existing')" id="fuel-tab-link"
                        class="py-2 px-2 rounded-lg text-center transition cursor-pointer bg-white dark:bg-slate-700 text-indigo-700 dark:text-indigo-300 shadow-xs font-bold">
                        🔗 Choose Past Refill
                    </button>
                    <button type="button" onclick="switchFuelAction('create_new')" id="fuel-tab-new"
                        class="py-2 px-2 rounded-lg text-center transition cursor-pointer text-slate-600 dark:text-slate-400 hover:text-slate-900">
                        ✨ Add New Refill
                    </button>
                    <button type="button" onclick="switchFuelAction('manual')" id="fuel-tab-manual"
                        class="py-2 px-2 rounded-lg text-center transition cursor-pointer text-slate-600 dark:text-slate-400 hover:text-slate-900">
                        ✍️ Manual Litres
                    </button>
                </div>
            </div>

            <!-- SECTION 1: LINK EXISTING PETROL ENTRY -->
            <div id="fuel-section-link" class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-3">
                <div>
                    <label for="end_fuel_entry_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Select Ending Refill Entry (Refuel to Full Tank)
                    </label>
                    <select name="end_fuel_entry_id" id="end_fuel_entry_id"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">-- Select from your recent petrol logs --</option>
                        @foreach($recentFuelEntries as $fe)
                            <option value="{{ $fe->id }}">
                                {{ \Carbon\Carbon::parse($fe->date)->format('d M Y') }} · ₹{{ number_format($fe->amount, 0) }} ({{ $fe->litres }} L) {{ $fe->petrol_station ? '· ' . $fe->petrol_station : '' }} {{ $fe->odometer ? '· ' . $fe->odometer . ' km' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                        This fuel entry's refill litres will be used to calculate exact km/L mileage (distance covered &divide; litres).
                    </p>
                </div>

                <div>
                    <label for="start_fuel_entry_id" class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
                        Starting Anchor Refill (Optional)
                    </label>
                    <select name="start_fuel_entry_id" id="start_fuel_entry_id"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 text-xs focus:outline-none">
                        <option value="">-- None / Unknown --</option>
                        @foreach($recentFuelEntries as $fe)
                            <option value="{{ $fe->id }}">
                                {{ \Carbon\Carbon::parse($fe->date)->format('d M Y') }} · ₹{{ number_format($fe->amount, 0) }} ({{ $fe->litres }} L)
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- SECTION 2: CREATE NEW PETROL ENTRY ON THE SPOT -->
            <div id="fuel-section-new" class="hidden p-3.5 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 space-y-3 text-xs">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="new_fuel_litres" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Litres Filled <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" name="new_fuel_litres" id="new_fuel_litres" placeholder="e.g. 5.14"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label for="new_fuel_amount" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Amount Paid (₹) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" name="new_fuel_amount" id="new_fuel_amount" placeholder="e.g. 540"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="new_fuel_date" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Date
                        </label>
                        <input type="date" name="new_fuel_date" id="new_fuel_date" value="{{ date('Y-m-d') }}"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs">
                    </div>
                    <div>
                        <label for="new_fuel_payment_method" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Payment Method
                        </label>
                        <select name="new_fuel_payment_method" id="new_fuel_payment_method"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs">
                            <option value="UPI">UPI / GPay / PhonePe</option>
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="new_fuel_station" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Petrol Station (Optional)
                        </label>
                        <input type="text" name="new_fuel_station" id="new_fuel_station" placeholder="e.g. Shell / HP / IndianOil"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs">
                    </div>
                    <div>
                        <label for="new_fuel_time" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Time (Optional)
                        </label>
                        <input type="time" name="new_fuel_time" id="new_fuel_time" value="{{ date('H:i') }}"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs">
                    </div>
                </div>

                <label class="flex items-center gap-2 cursor-pointer pt-1">
                    <input type="checkbox" name="new_fuel_add_as_expense" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Also record in Petrol Log & Main Expenses</span>
                </label>
            </div>

            <!-- SECTION 3: MANUAL LITRES & COST -->
            <div id="fuel-section-manual" class="hidden p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="manual_litres" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Fuel Consumed (Litres)
                        </label>
                        <input type="number" step="0.01" name="manual_litres" id="manual_litres" placeholder="e.g. 4.80"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label for="manual_fuel_cost" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Total Cost (₹)
                        </label>
                        <input type="number" step="0.01" name="manual_fuel_cost" id="manual_fuel_cost" placeholder="e.g. 500"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    Calculates mileage directly without linking or creating a separate Petrol Log entry.
                </p>
            </div>

            <!-- Notes -->
            <div>
                <label for="fuel_cycle_notes" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Cycle Notes (Optional)
                </label>
                <textarea name="notes" id="fuel_cycle_notes" rows="2" placeholder="e.g. Traffic was heavy, highway ride..."
                    class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
            </div>

            <!-- Footer Buttons -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="unlinkPetrolRefill()" class="text-xs text-rose-600 dark:text-rose-400 hover:underline cursor-pointer font-medium">
                    Remove / Reset Petrol Link
                </button>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeFuelModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md transition cursor-pointer">
                        Save & Recalculate Mileage
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openFuelModal(groupId, title, endFuelId, litres, cost, notes, vehicleId) {
        const modal = document.getElementById('fuel-modal');
        const form = document.getElementById('fuel-modal-form');
        const titleInput = document.getElementById('fuel_cycle_title');
        const subtitle = document.getElementById('fuel-modal-cycle-name');
        const endFuelSelect = document.getElementById('end_fuel_entry_id');
        const manualLitres = document.getElementById('manual_litres');
        const manualCost = document.getElementById('manual_fuel_cost');
        const notesInput = document.getElementById('fuel_cycle_notes');
        const vehicleSelect = document.getElementById('fuel_vehicle_id');

        form.action = `/odometer/groups/${groupId}`;
        titleInput.value = title || '';
        subtitle.textContent = `Editing Cycle: ${title || 'Odometer Cycle'}`;
        if (notesInput) notesInput.value = notes || '';
        if (vehicleSelect && vehicleId) vehicleSelect.value = vehicleId;

        if (endFuelId) {
            switchFuelAction('link_existing');
            if (endFuelSelect) endFuelSelect.value = endFuelId;
        } else if (litres && litres > 0) {
            switchFuelAction('manual');
            if (manualLitres) manualLitres.value = litres;
            if (manualCost) manualCost.value = cost || '';
        } else {
            switchFuelAction('link_existing');
            if (endFuelSelect) endFuelSelect.value = '';
        }

        modal.classList.remove('hidden');
    }

    function closeFuelModal() {
        document.getElementById('fuel-modal')?.classList.add('hidden');
    }

    function switchFuelAction(action) {
        document.getElementById('fuel_action').value = action;
        const tabLink = document.getElementById('fuel-tab-link');
        const tabNew = document.getElementById('fuel-tab-new');
        const tabManual = document.getElementById('fuel-tab-manual');

        const secLink = document.getElementById('fuel-section-link');
        const secNew = document.getElementById('fuel-section-new');
        const secManual = document.getElementById('fuel-section-manual');

        // Reset classes
        [tabLink, tabNew, tabManual].forEach(tab => {
            tab.className = 'py-2 px-2 rounded-lg text-center transition cursor-pointer text-slate-600 dark:text-slate-400 hover:text-slate-900';
        });

        secLink.classList.add('hidden');
        secNew.classList.add('hidden');
        secManual.classList.add('hidden');

        if (action === 'link_existing') {
            tabLink.className = 'py-2 px-2 rounded-lg text-center transition cursor-pointer bg-white dark:bg-slate-700 text-indigo-700 dark:text-indigo-300 shadow-xs font-bold';
            secLink.classList.remove('hidden');
        } else if (action === 'create_new') {
            tabNew.className = 'py-2 px-2 rounded-lg text-center transition cursor-pointer bg-white dark:bg-slate-700 text-indigo-700 dark:text-indigo-300 shadow-xs font-bold';
            secNew.classList.remove('hidden');
        } else if (action === 'manual') {
            tabManual.className = 'py-2 px-2 rounded-lg text-center transition cursor-pointer bg-white dark:bg-slate-700 text-indigo-700 dark:text-indigo-300 shadow-xs font-bold';
            secManual.classList.remove('hidden');
        }
    }

    function unlinkPetrolRefill() {
        if (!confirm('Are you sure you want to remove the linked petrol refill from this cycle? Mileage calculation will be reset.')) {
            return;
        }
        document.getElementById('fuel_action').value = 'unlink';
        document.getElementById('fuel-modal-form').submit();
    }
</script>

<!-- QUICK ADD MODAL -->
<div id="quick-add-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 transition-all duration-300" onclick="if(event.target === this) window.closeQuickAdd()">
    <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transform transition-all">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-lg">+</span>
                <h3 class="font-bold text-base text-slate-900 dark:text-white">Quick Record</h3>
            </div>
            <button type="button" onclick="window.closeQuickAdd()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer">&times;</button>
        </div>

        <!-- Tab Pills -->
        <div class="px-6 pt-3 pb-2 flex gap-1.5 overflow-x-auto border-b border-slate-100 dark:border-slate-800 text-xs no-scrollbar">
            <button type="button" onclick="window.switchQuickTab('expense')" id="qtab-btn-expense" class="qtab-btn px-3 py-1.5 rounded-lg font-medium transition bg-indigo-600 text-white">💰 Expense</button>
            <button type="button" onclick="window.switchQuickTab('income')" id="qtab-btn-income" class="qtab-btn px-3 py-1.5 rounded-lg font-medium transition bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">💵 Income</button>
            <button type="button" onclick="window.switchQuickTab('food')" id="qtab-btn-food" class="qtab-btn px-3 py-1.5 rounded-lg font-medium transition bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">🍔 Food</button>
            <button type="button" onclick="window.switchQuickTab('scooter')" id="qtab-btn-scooter" class="qtab-btn px-3 py-1.5 rounded-lg font-medium transition bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">🛵 Scooter</button>
            <button type="button" onclick="window.switchQuickTab('activity')" id="qtab-btn-activity" class="qtab-btn px-3 py-1.5 rounded-lg font-medium transition bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">🎓 Activity</button>
            <button type="button" onclick="window.switchQuickTab('mistake')" id="qtab-btn-mistake" class="qtab-btn px-3 py-1.5 rounded-lg font-medium transition bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">⚠️ Mistake</button>
            <button type="button" onclick="window.switchQuickTab('note')" id="qtab-btn-note" class="qtab-btn px-3 py-1.5 rounded-lg font-medium transition bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">📝 Note</button>
        </div>

        <!-- Tab Contents -->
        <div class="p-6 max-h-[70vh] overflow-y-auto">
            <!-- 1. Quick Expense Form -->
            <form id="qtab-expense" action="{{ route('expenses.store') }}" method="POST" class="qtab-pane space-y-3">
                @csrf
                <input type="hidden" name="date" value="{{ date('Y-m-d') }}">
                <input type="hidden" name="time" value="{{ date('H:i') }}">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Amount (₹)</label>
                        <input type="number" step="0.01" name="amount" required placeholder="0.00" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-base font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Method</label>
                        <select name="payment_method" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-900 dark:text-white">
                            @foreach($quickAddOptions['paymentMethods'] as $method)
                                <option value="{{ $method }}">{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Description</label>
                    <input type="text" name="description" required placeholder="e.g. Lunch at canteen, petrol, books" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Category</label>
                        <select name="category_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                            @foreach($quickAddOptions['expenseCategories'] as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Paid By / Friend</label>
                        <input type="text" name="paid_by" value="Me" placeholder="Me, Rahul..." class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                    </div>
                </div>

                <button type="submit" class="w-full mt-2 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-md shadow-indigo-600/20 transition">Save Expense</button>
            </form>

            <!-- 2. Quick Income Form -->
            <form id="qtab-income" action="{{ route('income.store') }}" method="POST" class="qtab-pane hidden space-y-3">
                @csrf
                <input type="hidden" name="date" value="{{ date('Y-m-d') }}">
                <input type="hidden" name="time" value="{{ date('H:i') }}">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Amount (₹)</label>
                        <input type="number" step="0.01" name="amount" required placeholder="0.00" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-base font-bold text-emerald-600 dark:text-emerald-400">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Source</label>
                        <select name="source" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                            @foreach($quickAddOptions['incomeSources'] as $source)
                                <option value="{{ $source }}">{{ $source }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                        @foreach($quickAddOptions['paymentMethods'] as $method)
                            <option value="{{ $method }}">{{ $method }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Description</label>
                    <input type="text" name="description" placeholder="Optional notes" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                </div>

                <button type="submit" class="w-full mt-2 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs shadow-md shadow-emerald-600/20 transition">Save Income</button>
            </form>

            <!-- 3. Quick Food Form -->
            <form id="qtab-food" action="{{ route('food.store') }}" method="POST" class="qtab-pane hidden space-y-3">
                @csrf
                <input type="hidden" name="date" value="{{ date('Y-m-d') }}">
                <input type="hidden" name="time" value="{{ date('H:i') }}">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Item Name</label>
                        <input type="text" name="item_name" required placeholder="Tea, Biryani, Puffs..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-orange-400">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Amount (₹)</label>
                        <input type="number" step="0.01" name="amount" id="food-amount" value="0.00" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900">
                    </div>
                </div>

                <div class="flex items-center gap-4 py-1">
                    <label class="flex items-center gap-2 cursor-pointer text-xs">
                        <input type="checkbox" name="is_snack" value="1" checked class="w-4 h-4 rounded text-orange-500 focus:ring-orange-400">
                        <span class="font-medium text-slate-700">Is Snack / Drink?</span>
                    </label>
                    <div class="flex items-center gap-1.5 ml-auto">
                        <span class="text-xs text-slate-500">Qty:</span>
                        <input type="number" name="quantity" value="1" min="1" class="w-14 px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs text-center text-slate-900">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Location</label>
                    <input type="text" name="location" placeholder="e.g. Campus Canteen, Bakery" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900">
                </div>

                <!-- Food → Expense cross-link -->
                <div id="food-expense-box" class="p-3 rounded-xl bg-indigo-50 border border-indigo-100 space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs">
                        <input type="checkbox" name="create_expense" value="1" id="food-create-expense"
                               class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500"
                               onchange="document.getElementById('food-payment-row').style.display = this.checked ? 'block' : 'none'">
                        <span class="font-semibold text-indigo-700">💰 Also log as an Expense</span>
                    </label>
                    <p class="text-[10px] text-indigo-500 leading-relaxed">When checked, a linked expense record is auto-created in your Finance hub.</p>
                    <div id="food-payment-row" style="display:none">
                        <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-900">
                            @foreach($quickAddOptions['paymentMethods'] as $method)
                                <option value="{{ $method }}">{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full mt-2 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold text-sm shadow-md shadow-orange-500/20 transition">Save Food Entry</button>
            </form>

            <!-- 4. Quick Scooter Start Trip Form -->
            <form id="qtab-scooter" action="{{ route('scooter.start') }}" method="POST" class="qtab-pane hidden space-y-3">
                @csrf
                <input type="hidden" name="start_latitude" id="quick-scooter-lat">
                <input type="hidden" name="start_longitude" id="quick-scooter-lon">
                <input type="hidden" name="stops" id="quick-scooter-stops-json" value="[]">

                <div class="p-3 rounded-2xl bg-cyan-50 border border-cyan-200 text-xs text-cyan-800">
                    <p class="font-semibold mb-1">🛵 Start a Scooter Trip</p>
                    <p>Captures GPS coordinates to auto-calculate distance when you finish.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Trip Name</label>
                        <input type="text" name="title" value="Home to College" placeholder="e.g. Home to College" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">From (Label)</label>
                        <input type="text" name="from_label" placeholder="e.g. Home, College" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">GPS Start Location</label>
                    <div class="flex gap-2">
                        <input type="text" name="start_address" id="quick-scooter-addr" value="Current GPS Location"
                               class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900">
                        <button type="button" onclick="window.fetchQuickLocation()" class="px-3 py-2 rounded-xl bg-cyan-100 text-cyan-700 text-xs font-semibold hover:bg-cyan-200 transition">📍 GPS</button>
                    </div>
                    <p id="scooter-gps-preview" class="text-[10px] text-cyan-600 font-mono mt-1 hidden"></p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">To (Label)</label>
                        <input type="text" name="to_label" placeholder="e.g. Office, Market" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer text-xs p-2">
                            <input type="checkbox" name="to_and_fro" value="1" id="scooter-to-and-fro"
                                   class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500">
                            <span class="font-semibold text-slate-700">🔁 To &amp; Fro<br><span class="font-normal text-slate-400">(Round Trip)</span></span>
                        </label>
                    </div>
                </div>

                <!-- Stops section -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Stops (Optional)</label>
                        <button type="button" onclick="window.addScooterStop()" class="text-xs text-cyan-600 font-semibold hover:underline">+ Add Stop</button>
                    </div>
                    <div id="scooter-stops-list" class="space-y-1.5"></div>
                </div>

                <button type="submit" onclick="window.serializeStops()" class="w-full mt-2 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-semibold text-sm shadow-md shadow-cyan-600/20 transition">🛵 START TRIP</button>
            </form>

            <!-- 5. Quick Activity Form -->
            <form id="qtab-activity" action="{{ route('activities.store') }}" method="POST" class="qtab-pane hidden space-y-3">
                @csrf
                <input type="hidden" name="date" value="{{ date('Y-m-d') }}">

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Activity Title</label>
                    <input type="text" name="title" required placeholder="Gym, Study session, Project coding..." class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Start Time</label>
                        <input type="time" name="start_time" value="{{ date('H:i') }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">End Time</label>
                        <input type="time" name="end_time" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Category</label>
                    <select name="category_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                        @foreach($quickAddOptions['activityCategories'] as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full mt-2 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-md transition">Save Activity</button>
            </form>

            <!-- 6. Quick Mistake Form -->
            <form id="qtab-mistake" action="{{ route('mistakes.store') }}" method="POST" class="qtab-pane hidden space-y-3">
                @csrf
                <input type="hidden" name="date" value="{{ date('Y-m-d') }}">
                <input type="hidden" name="time" value="{{ date('H:i') }}">

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Mistake Title</label>
                    <input type="text" name="title" required placeholder="e.g. Forgot lab assignment deadline" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Severity</label>
                        <select name="severity" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                            <option value="Open">Open</option>
                            <option value="Working On It">Working On It</option>
                            <option value="Resolved" selected>Resolved</option>
                            <option value="Learned">Learned</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">What Happened?</label>
                    <textarea name="what_happened" required rows="2" placeholder="Brief description of what happened..." class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Why Did It Happen?</label>
                        <input type="text" name="why_happened" required placeholder="Root cause" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Lesson Learned</label>
                        <input type="text" name="lesson_learned" required placeholder="Key takeaway" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                    </div>
                </div>

                <input type="hidden" name="what_should_have_done" value="Act earlier and plan ahead">
                <input type="hidden" name="prevention_plan" value="Set calendar reminder 24h prior">

                <button type="submit" class="w-full mt-2 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs shadow-md shadow-rose-600/20 transition">Save Mistake & Lesson</button>
            </form>

            <!-- 7. Quick Note Form -->
            <form id="qtab-note" action="{{ route('notes.store') }}" method="POST" class="qtab-pane hidden space-y-3">
                @csrf
                <input type="hidden" name="date" value="{{ date('Y-m-d') }}">

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Title</label>
                    <input type="text" name="title" required placeholder="Note title..." class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Content</label>
                    <textarea name="content" required rows="3" placeholder="Write your thoughts, ideas, checklist..." class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white"></textarea>
                </div>

                <div class="flex items-center justify-between">
                    <input type="text" name="tags" placeholder="tags (e.g. project, idea)" class="w-2/3 px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                    <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 cursor-pointer">
                        <input type="checkbox" name="is_pinned" value="1" class="w-4 h-4 rounded text-indigo-600">
                        <span>Pin</span>
                    </label>
                </div>

                <button type="submit" class="w-full mt-2 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-md transition">Save Note</button>
            </form>
        </div>
    </div>
</div>

<script>
    window.openQuickAdd = function() {
        document.getElementById('quick-add-modal').classList.remove('hidden');
    };
    window.closeQuickAdd = function() {
        document.getElementById('quick-add-modal').classList.add('hidden');
    };
    window.switchQuickTab = function(tabName) {
        document.querySelectorAll('.qtab-pane').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.qtab-btn').forEach(btn => {
            btn.className = 'qtab-btn px-3 py-1.5 rounded-lg font-medium transition bg-slate-100 text-slate-700';
        });
        const targetPane = document.getElementById(`qtab-${tabName}`);
        const targetBtn  = document.getElementById(`qtab-btn-${tabName}`);
        if (targetPane) targetPane.classList.remove('hidden');
        if (targetBtn)  targetBtn.className = 'qtab-btn px-3 py-1.5 rounded-lg font-medium transition bg-indigo-600 text-white';
    };
    window.fetchQuickLocation = async function() {
        try {
            const coords = await window.getCurrentCoordinates();
            document.getElementById('quick-scooter-lat').value   = coords.latitude;
            document.getElementById('quick-scooter-lon').value   = coords.longitude;
            document.getElementById('quick-scooter-addr').value  = `${coords.latitude.toFixed(5)}, ${coords.longitude.toFixed(5)}`;
            const prev = document.getElementById('scooter-gps-preview');
            if (prev) {
                prev.textContent = `✅ GPS: ${coords.latitude.toFixed(6)}, ${coords.longitude.toFixed(6)}`;
                prev.classList.remove('hidden');
            }
            window.showToast('📍 GPS Location Acquired!', 'info');
        } catch (e) {
            window.showToast('GPS: ' + e.message, 'warning');
        }
    };

    // --- Scooter stops management ---
    let _stopCounter = 0;
    window.addScooterStop = function() {
        _stopCounter++;
        const id = `stop-${_stopCounter}`;
        const list = document.getElementById('scooter-stops-list');
        const div = document.createElement('div');
        div.id = id;
        div.className = 'flex gap-1.5 items-center';
        div.innerHTML = `
            <span class="text-yellow-500 text-sm">🟡</span>
            <input type="text" placeholder="Stop name (e.g. Petrol bunk, Market)"
                   class="flex-1 px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900"
                   data-stop-label="true">
            <button type="button" onclick="document.getElementById('${id}').remove();"
                    class="text-rose-400 hover:text-rose-600 text-xs font-bold px-1.5">&times;</button>
        `;
        list.appendChild(div);
    };
    window.serializeStops = function() {
        const rows  = document.querySelectorAll('#scooter-stops-list [data-stop-label]');
        const stops = [];
        rows.forEach(input => {
            const label = input.value.trim();
            if (label) stops.push({ label });
        });
        document.getElementById('quick-scooter-stops-json').value = JSON.stringify(stops);
    };
</script>

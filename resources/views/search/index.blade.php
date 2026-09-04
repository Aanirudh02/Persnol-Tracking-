<x-app-layout title="Global Search">
    <div class="space-y-6">
        <!-- Search Header -->
        <div class="bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 rounded-3xl p-6 sm:p-8 text-white shadow-lg shadow-sky-500/15">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/20 backdrop-blur-md text-white mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Universal Search
                </span>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Search LifeTracker</h1>
                <p class="mt-1 text-sm text-sky-100">Find any expense, income, friend, scooter trip, food item, note, or activity instantly.</p>
                
                <form action="{{ route('search') }}" method="GET" class="mt-5 relative">
                    <div class="relative flex items-center">
                        <span class="absolute left-4 text-sky-600 pointer-events-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input
                            type="text"
                            name="q"
                            value="{{ $q ?? '' }}"
                            placeholder="Type to search anything (e.g., Petrol, Coffee, Aanirudh, College)..."
                            autofocus
                            class="w-full pl-12 pr-28 py-3.5 bg-white text-slate-900 placeholder-slate-400 rounded-2xl shadow-xl focus:outline-none focus:ring-4 focus:ring-sky-300 text-sm font-medium border-0"
                        />
                        <button
                            type="submit"
                            class="absolute right-2 px-5 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold transition shadow-sm cursor-pointer active:scale-95"
                        >
                            Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if(!empty($q))
            <!-- Results Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-sky-100 shadow-sm">
                <div>
                    <h2 class="text-base font-bold text-slate-900">
                        Results for "<span class="text-sky-600">{{ $q }}</span>"
                    </h2>
                    <p class="text-xs text-slate-500">Found {{ $totalMatches ?? 0 }} total match{{ ($totalMatches ?? 0) === 1 ? '' : 'es' }} across your data</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('search') }}" class="text-xs text-slate-500 hover:text-sky-600 px-3 py-1.5 rounded-lg hover:bg-sky-50 font-medium transition">
                        Clear Search
                    </a>
                </div>
            </div>

            @if(($totalMatches ?? 0) === 0)
                <!-- Empty State -->
                <div class="bg-white rounded-3xl p-12 text-center border border-sky-100 shadow-sm">
                    <div class="w-16 h-16 rounded-2xl bg-sky-50 text-sky-500 flex items-center justify-center mx-auto mb-4 text-2xl">
                        🔍
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">No matching records found</h3>
                    <p class="text-sm text-slate-500 max-w-md mx-auto mt-1">We couldn't find anything matching "<span class="font-medium text-slate-700">{{ $q }}</span>". Try searching with a different term or keyword.</p>
                </div>
            @else
                <!-- Results Sections -->
                <div class="space-y-6">

                    <!-- Expenses -->
                    @if(!empty($results['expenses']) && count($results['expenses']) > 0)
                        <div class="bg-white rounded-2xl border border-sky-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm font-bold">💳</span>
                                    <h3 class="font-bold text-sm text-slate-900">Expenses</h3>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">{{ count($results['expenses']) }}</span>
                                </div>
                                <a href="{{ route('expenses.index') }}" class="text-xs text-sky-600 hover:text-sky-700 font-semibold">View all &rarr;</a>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($results['expenses'] as $item)
                                    <div class="p-3.5 rounded-xl bg-sky-50/40 hover:bg-sky-50/80 border border-sky-100/80 transition">
                                        <div class="flex items-start justify-between">
                                            <span class="font-semibold text-sm text-slate-900 line-clamp-1">{{ $item->description }}</span>
                                            <span class="font-bold text-sm text-rose-600">₹{{ number_format($item->amount, 2) }}</span>
                                        </div>
                                        <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                                            <span>{{ $item->date ? \Carbon\Carbon::parse($item->date)->format('M d, Y') : '' }}</span>
                                            <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-medium">{{ $item->payment_method ?? 'Cash' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Income -->
                    @if(!empty($results['income']) && count($results['income']) > 0)
                        <div class="bg-white rounded-2xl border border-sky-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">💵</span>
                                    <h3 class="font-bold text-sm text-slate-900">Money Received</h3>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">{{ count($results['income']) }}</span>
                                </div>
                                <a href="{{ route('income.index') }}" class="text-xs text-sky-600 hover:text-sky-700 font-semibold">View all &rarr;</a>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($results['income'] as $item)
                                    <div class="p-3.5 rounded-xl bg-emerald-50/40 hover:bg-emerald-50/80 border border-emerald-100/80 transition">
                                        <div class="flex items-start justify-between">
                                            <span class="font-semibold text-sm text-slate-900 line-clamp-1">{{ $item->source }}</span>
                                            <span class="font-bold text-sm text-emerald-600">+₹{{ number_format($item->amount, 2) }}</span>
                                        </div>
                                        <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                                            <span>{{ $item->date ? \Carbon\Carbon::parse($item->date)->format('M d, Y') : '' }}</span>
                                            <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-medium">{{ $item->payment_method ?? 'UPI' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Food & Snacks -->
                    @if(!empty($results['food']) && count($results['food']) > 0)
                        <div class="bg-white rounded-2xl border border-sky-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">🍔</span>
                                    <h3 class="font-bold text-sm text-slate-900">Food & Snacks</h3>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">{{ count($results['food']) }}</span>
                                </div>
                                <a href="{{ route('food.index') }}" class="text-xs text-sky-600 hover:text-sky-700 font-semibold">View all &rarr;</a>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($results['food'] as $item)
                                    <div class="p-3.5 rounded-xl bg-amber-50/30 hover:bg-amber-50/70 border border-amber-100 transition">
                                        <div class="flex items-start justify-between">
                                            <span class="font-semibold text-sm text-slate-900">{{ $item->item_name }}</span>
                                            <span class="font-bold text-sm text-amber-700">₹{{ number_format($item->amount, 2) }}</span>
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $item->location ?? 'No location specified' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Scooter Trips & Petrol -->
                    @if((!empty($results['scooter']) && count($results['scooter']) > 0) || (!empty($results['petrol']) && count($results['petrol']) > 0))
                        <div class="bg-white rounded-2xl border border-sky-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-sm font-bold">🛵</span>
                                    <h3 class="font-bold text-sm text-slate-900">Scooter & Fuel</h3>
                                </div>
                                <a href="{{ route('scooter.index') }}" class="text-xs text-sky-600 hover:text-sky-700 font-semibold">Scooter Hub &rarr;</a>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($results['scooter'] ?? [] as $item)
                                    <div class="p-3.5 rounded-xl bg-cyan-50/30 border border-cyan-100">
                                        <div class="font-semibold text-sm text-slate-900">{{ $item->title ?? 'Scooter Ride' }}</div>
                                        <div class="text-xs text-cyan-700 mt-1">{{ $item->distance_km }} km • {{ $item->duration_minutes }} mins</div>
                                        <div class="text-[11px] text-slate-400 mt-1">{{ $item->start_address }}</div>
                                    </div>
                                @endforeach
                                @foreach($results['petrol'] ?? [] as $item)
                                    <div class="p-3.5 rounded-xl bg-sky-50/50 border border-sky-100">
                                        <div class="flex items-center justify-between">
                                            <span class="font-semibold text-sm text-slate-900">⛽ Fuel Fill</span>
                                            <span class="font-bold text-sm text-sky-700">₹{{ number_format($item->amount, 2) }}</span>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1">{{ $item->litres }} L • {{ $item->petrol_station ?? 'Station' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Activities -->
                    @if(!empty($results['activities']) && count($results['activities']) > 0)
                        <div class="bg-white rounded-2xl border border-sky-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">🎓</span>
                                    <h3 class="font-bold text-sm text-slate-900">Activities</h3>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">{{ count($results['activities']) }}</span>
                                </div>
                                <a href="{{ route('activities.index') }}" class="text-xs text-sky-600 hover:text-sky-700 font-semibold">View all &rarr;</a>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($results['activities'] as $item)
                                    <div class="p-3.5 rounded-xl bg-indigo-50/30 border border-indigo-100">
                                        <div class="font-semibold text-sm text-slate-900">{{ $item->title }}</div>
                                        <div class="text-xs text-slate-500 mt-1">{{ $item->location ?? 'General' }} • {{ $item->duration_minutes ? $item->duration_minutes . 'm' : '' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Notes & Mistakes -->
                    @if((!empty($results['notes']) && count($results['notes']) > 0) || (!empty($results['mistakes']) && count($results['mistakes']) > 0))
                        <div class="bg-white rounded-2xl border border-sky-100 p-5 shadow-sm">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center text-sm font-bold">📝</span>
                                    <h3 class="font-bold text-sm text-slate-900">Notes & Lessons</h3>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($results['notes'] ?? [] as $item)
                                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                                        <div class="font-semibold text-sm text-slate-900">{{ $item->title }}</div>
                                        <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $item->content }}</p>
                                    </div>
                                @endforeach
                                @foreach($results['mistakes'] ?? [] as $item)
                                    <div class="p-3.5 rounded-xl bg-amber-50/40 border border-amber-200">
                                        <div class="font-semibold text-sm text-amber-900">⚠️ {{ $item->title }}</div>
                                        <p class="text-xs text-slate-600 mt-1 line-clamp-2">{{ $item->lesson_learned ?? $item->what_happened }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
            @endif
        @else
            <!-- Search Prompt / Suggestions -->
            <div class="bg-white rounded-3xl p-8 border border-sky-100 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 mb-2">Quick Search Tips</h3>
                <p class="text-xs text-slate-500 mb-6">Type any keyword above to search through your entire personal database.</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <a href="{{ route('search', ['q' => 'Petrol']) }}" class="p-4 rounded-2xl bg-sky-50/60 hover:bg-sky-100/80 border border-sky-100 text-center transition">
                        <span class="text-2xl mb-1 block">⛽</span>
                        <span class="text-xs font-semibold text-slate-800">Petrol</span>
                    </a>
                    <a href="{{ route('search', ['q' => 'Food']) }}" class="p-4 rounded-2xl bg-sky-50/60 hover:bg-sky-100/80 border border-sky-100 text-center transition">
                        <span class="text-2xl mb-1 block">🍔</span>
                        <span class="text-xs font-semibold text-slate-800">Food</span>
                    </a>
                    <a href="{{ route('search', ['q' => 'Salary']) }}" class="p-4 rounded-2xl bg-sky-50/60 hover:bg-sky-100/80 border border-sky-100 text-center transition">
                        <span class="text-2xl mb-1 block">💵</span>
                        <span class="text-xs font-semibold text-slate-800">Salary / Income</span>
                    </a>
                    <a href="{{ route('search', ['q' => 'Trip']) }}" class="p-4 rounded-2xl bg-sky-50/60 hover:bg-sky-100/80 border border-sky-100 text-center transition">
                        <span class="text-2xl mb-1 block">🛵</span>
                        <span class="text-xs font-semibold text-slate-800">Scooter Rides</span>
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>

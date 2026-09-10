@props(['title' => 'Dashboard'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - {{ config('app.name', 'LifeTracker') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas text-slate-900 antialiased font-sans flex flex-col md:flex-row overflow-x-hidden selection:bg-slate-900 selection:text-white">

    <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 z-30 bg-white border-r border-slate-200">
        <div class="h-16 flex items-center px-6 gap-3 border-b border-slate-200">
            <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white font-bold">LT</div>
            <div>
                <span class="font-bold text-base tracking-tight text-slate-900">LifeTracker</span>
                <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Personal OS</span>
            </div>
        </div>

        <div class="flex-1 flex flex-col overflow-y-auto px-4 py-4 space-y-1">
            <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-nav-link>
            <x-nav-link href="{{ route('calendar') }}" :active="request()->routeIs('calendar') || request()->routeIs('timeline')" icon="calendar">Calendar & Timeline</x-nav-link>

            <div class="pt-2 pb-1"><p class="px-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Finance</p></div>
            <x-nav-link href="{{ route('finance.index') }}" :active="request()->routeIs('finance.index')" icon="credit-card">Finance Hub</x-nav-link>
            <x-nav-link href="{{ route('expenses.index') }}" :active="request()->routeIs('expenses.*')" icon="trending-down">Expenses</x-nav-link>
            <x-nav-link href="{{ route('income.index') }}" :active="request()->routeIs('income.*')" icon="trending-up">Money Received</x-nav-link>
            <x-nav-link href="{{ route('payments.index') }}" :active="request()->routeIs('payments.*')" icon="check-square">Payments</x-nav-link>
            <x-nav-link href="{{ route('friends.index') }}" :active="request()->routeIs('friends.*')" icon="users">Friends & Splits</x-nav-link>
            <x-nav-link href="{{ route('credits.index', ['type' => 'credit']) }}" :active="request()->routeIs('credits.*') && request('type', 'credit') === 'credit'" icon="users">Credits (I owe)</x-nav-link>
            <x-nav-link href="{{ route('credits.index', ['type' => 'debt']) }}" :active="request()->routeIs('credits.*') && request('type') === 'debt'" icon="users">Debts (owe me)</x-nav-link>

            <div class="pt-2 pb-1"><p class="px-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Life & Habits</p></div>
            <x-nav-link href="{{ route('food.index') }}" :active="request()->routeIs('food.*') && !request()->boolean('is_snack')" icon="coffee">Food</x-nav-link>
            <x-nav-link href="{{ route('food.index', ['is_snack' => 1]) }}" :active="request()->routeIs('food.*') && request()->boolean('is_snack')" icon="coffee">Snacks</x-nav-link>
            <x-nav-link href="{{ route('sleep.index') }}" :active="request()->routeIs('sleep.*')" icon="activity">Sleep & Wake</x-nav-link>
            <x-nav-link href="{{ route('activities.index') }}" :active="request()->routeIs('activities.*')" icon="activity">Activities</x-nav-link>
            <x-nav-link href="{{ route('scooter.index') }}" :active="request()->routeIs('scooter.index') || request()->routeIs('scooter.start') || request()->routeIs('scooter.end')" icon="navigation">Scooter Trips</x-nav-link>
            <x-nav-link href="{{ route('scooter.plan') }}" :active="request()->routeIs('scooter.plan*')" icon="navigation">Trip Planner</x-nav-link>
            <x-nav-link href="{{ route('vehicles.index') }}" :active="request()->routeIs('vehicles.*')" icon="navigation">Vehicles</x-nav-link>
            <x-nav-link href="{{ route('petrol.index') }}" :active="request()->routeIs('petrol.*')" icon="fuel">Petrol / Fuel</x-nav-link>
            <x-nav-link href="{{ route('mistakes.index') }}" :active="request()->routeIs('mistakes.*')" icon="alert-triangle">Mistakes</x-nav-link>
            <x-nav-link href="{{ route('notes.index') }}" :active="request()->routeIs('notes.*')" icon="file-text">Notes</x-nav-link>

            <div class="pt-2 pb-1"><p class="px-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Insights</p></div>
            <x-nav-link href="{{ route('analytics.index') }}" :active="request()->routeIs('analytics.*')" icon="pie-chart">Analytics</x-nav-link>
            <x-nav-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.*')" icon="bar-chart-2">Reports</x-nav-link>
            <x-nav-link href="{{ route('settings.index') }}" :active="request()->routeIs('settings.*')" icon="settings">Settings</x-nav-link>
        </div>

        <div class="p-4 border-t border-slate-200">
            <button type="button" onclick="window.openQuickAdd()" class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm flex items-center justify-center gap-2 cursor-pointer transition active:scale-95">
                <span class="text-lg leading-none font-bold">+</span>
                <span>Quick Record</span>
            </button>
        </div>
    </aside>

    <div class="md:pl-64 flex flex-col flex-1 min-h-screen min-w-0">
        <header class="sticky top-0 z-20 h-16 bg-white/95 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-4 sm:px-6">
            <div class="flex items-center gap-3 md:w-80">
                <div class="md:hidden flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center text-white font-bold text-xs">LT</div>
                    <span class="font-bold text-sm text-slate-900">LifeTracker</span>
                </div>
                <form action="{{ route('search') }}" method="GET" class="hidden md:flex items-center w-full relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search expenses, friends, notes, trips..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 transition" />
                </form>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('search') }}" class="md:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </a>
                <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                    <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white font-bold text-xs">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <span class="block text-sm font-semibold text-slate-800 leading-tight">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-slate-400 capitalize">{{ auth()->user()->roles->first()?->name ?? 'User' }}</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" title="Sign Out" class="p-2 ml-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 pb-24 md:pb-12 px-3 sm:px-6 lg:px-8 pt-6 max-w-7xl mx-auto w-full">
            @if(!empty($globalPrompts ?? []))
                <div class="space-y-3 mb-6">
                    @foreach($globalPrompts as $prompt)
                        <div class="p-4 sm:p-5 rounded-2xl bg-slate-900 text-white border border-slate-800 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <span class="text-2xl">{{ $prompt['icon'] }}</span>
                                    <div>
                                        <h3 class="font-bold text-base">{{ $prompt['title'] }}</h3>
                                        <p class="text-sm text-slate-300 mt-0.5">{{ $prompt['message'] }}</p>
                                    </div>
                                </div>
                                @if($prompt['type'] === 'wakeup')
                                    <form action="{{ route('daily.wakeup') }}" method="POST" class="flex items-center gap-2 flex-wrap">
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $prompt['date'] ?? now()->toDateString() }}">
                                        <input type="time" name="wake_up_time" value="{{ $prompt['currentTime'] }}" class="px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-white text-sm font-semibold">
                                        <button type="submit" class="px-4 py-2 rounded-xl bg-white text-slate-900 font-bold text-sm">Save</button>
                                        @if(isset($prompt['dismissUrl']))
                                            <a href="{{ $prompt['dismissUrl'] }}" class="p-2 text-white/60 hover:text-white text-sm">&times;</a>
                                        @endif
                                    </form>
                                @elseif($prompt['type'] === 'sleep')
                                    <form action="{{ route('daily.sleep') }}" method="POST" class="flex items-center gap-2 flex-wrap">
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $prompt['date'] ?? now()->toDateString() }}">
                                        <input type="time" name="sleep_time" value="{{ $prompt['currentTime'] }}" class="px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-white text-sm font-semibold">
                                        <select name="day_rating" class="px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-white text-sm">
                                            <option value="5" class="text-slate-900">Excellent</option>
                                            <option value="4" class="text-slate-900" selected>Good</option>
                                            <option value="3" class="text-slate-900">Average</option>
                                            <option value="2" class="text-slate-900">Poor</option>
                                            <option value="1" class="text-slate-900">Rough</option>
                                        </select>
                                        <button type="submit" class="px-4 py-2 rounded-xl bg-white text-slate-900 font-bold text-sm">Save</button>
                                        @if(isset($prompt['dismissUrl']))
                                            <a href="{{ $prompt['dismissUrl'] }}" class="p-2 text-white/60 hover:text-white text-sm">&times;</a>
                                        @endif
                                    </form>
                                @elseif($prompt['type'] === 'petrol')
                                    <a href="{{ route('petrol.index') }}" class="px-4 py-2 rounded-xl bg-white text-slate-900 font-bold text-sm">Record Petrol</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-lg border-t border-slate-200 flex items-center justify-around h-16 px-2 shadow-lg">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-sm {{ request()->routeIs('dashboard') ? 'text-slate-900 font-bold' : 'text-slate-500' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Home</span>
        </a>
        <a href="{{ route('calendar') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-sm {{ request()->routeIs('calendar') || request()->routeIs('timeline') ? 'text-slate-900 font-bold' : 'text-slate-500' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Calendar</span>
        </a>
        <div class="relative -top-5 flex-1 flex justify-center">
            <button type="button" onclick="window.openQuickAdd()" class="w-13 h-13 rounded-full bg-slate-900 text-white shadow-xl flex items-center justify-center text-2xl font-bold active:scale-95 transition-transform cursor-pointer" title="Quick Add">+</button>
        </div>
        <a href="{{ route('finance.index') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-sm {{ request()->routeIs('finance.*') || request()->routeIs('expenses.*') || request()->routeIs('income.*') ? 'text-slate-900 font-bold' : 'text-slate-500' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Finance</span>
        </a>
        <button type="button" onclick="document.getElementById('mobile-more-menu').classList.toggle('hidden')" class="flex flex-col items-center justify-center flex-1 py-1 text-sm text-slate-500 cursor-pointer">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
            <span>More</span>
        </button>
    </nav>

    <div id="mobile-more-menu" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex flex-col justify-end" onclick="if(event.target === this) this.classList.add('hidden')">
        <div class="bg-white rounded-t-3xl p-6 space-y-4 max-h-[80vh] overflow-y-auto border-t border-slate-200 shadow-2xl">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <h3 class="font-bold text-base text-slate-900">All Modules</h3>
                <button onclick="document.getElementById('mobile-more-menu').classList.add('hidden')" class="text-slate-400 text-xl font-bold">&times;</button>
            </div>
            <div class="grid grid-cols-3 gap-3 text-center">
                @foreach([
                    ['food.index', 'Food', []],
                    ['food.index', 'Snacks', ['is_snack' => 1]],
                    ['sleep.index', 'Sleep', []],
                    ['scooter.index', 'Trips', []],
                    ['scooter.plan', 'Plan Trip', []],
                    ['vehicles.index', 'Vehicles', []],
                    ['petrol.index', 'Petrol', []],
                    ['credits.index', 'Credits', ['type' => 'credit']],
                    ['credits.index', 'Debts', ['type' => 'debt']],
                    ['settings.index', 'Settings', []],
                ] as [$route, $label, $params])
                    <a href="{{ route($route, $params) }}" class="p-3 rounded-2xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-sm font-medium text-slate-700 transition">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </div>

    <x-quick-add-modal />

    <div id="toast-container" class="fixed bottom-20 md:bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

    @if (session('success'))
        <script>document.addEventListener('DOMContentLoaded', () => window.showToast(@json(session('success')), 'success'));</script>
    @endif
    @if (session('error'))
        <script>document.addEventListener('DOMContentLoaded', () => window.showToast(@json(session('error')), 'error'));</script>
    @endif
    @if (session('info'))
        <script>document.addEventListener('DOMContentLoaded', () => window.showToast(@json(session('info')), 'info'));</script>
    @endif
</body>
</html>

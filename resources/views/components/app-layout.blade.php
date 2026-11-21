@props(['title' => 'Dashboard'])
@php
    $authUser = auth()->user();
    $profilePhotoUrl = $authUser?->profilePhotoUrl();
    $appIconUrl = $authUser?->appIconUrl(192);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - {{ config('app.name', 'LifeTracker') }}</title>
    @if($appIconUrl)
        <link rel="icon" type="image/png" sizes="192x192" href="{{ $appIconUrl }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ $authUser->appIconUrl(180) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="app-body min-h-screen bg-canvas text-slate-900 antialiased font-sans overflow-x-hidden selection:bg-slate-900 selection:text-white">

    @php
        $creditType = request('type', 'credit');
        $desktopNavGroups = [
            'Money' => [
                'icon' => 'credit-card',
                'active' => request()->routeIs('income.*', 'expenses.*', 'expense-groups.*', 'personal-expenses.*', 'personal-expense-groups.*', 'all-expenses.*', 'bulk-entry.*', 'classification.*'),
                'sections' => [
                    'Income & expenses' => [
                        ['income.index', [], 'Income', request()->routeIs('income.*'), 'trending-up'],
                        ['expenses.index', [], 'Expenses', request()->routeIs('expenses.*', 'expense-groups.*'), 'trending-down'],
                        ['personal-expenses.index', [], 'Personal Expenses', request()->routeIs('personal-expenses.*', 'personal-expense-groups.*'), 'user'],
                    ],
                    'Tools' => [
                        ['bulk-entry.create', [], 'Bulk add', request()->routeIs('bulk-entry.*'), 'plus'],
                        ['all-expenses.index', [], 'All expenses', request()->routeIs('all-expenses.*'), 'layers'],
                        ['classification.index', [], 'Classifications', request()->routeIs('classification.*'), 'tag'],
                    ],
                ],
            ],
            'Ledger' => [
                'icon' => 'layers',
                'active' => request()->routeIs('finance.index', 'daily-balances.*', 'statements.*', 'savings.*', 'finance.gpay-auditor.*'),
                'sections' => [
                    'Balances' => [
                        ['finance.index', [], 'Finance Hub', request()->routeIs('finance.index'), 'credit-card'],
                        ['daily-balances.index', [], 'Daily Cash Register', request()->routeIs('daily-balances.*'), 'layers'],
                        ['savings.index', [], 'Savings & Funds', request()->routeIs('savings.*'), 'trending-up'],
                    ],
                    'Statements & checks' => [
                        ['statements.index', [], 'Statements', request()->routeIs('statements.index', 'statements.show'), 'file-text'],
                        ['statements.create', [], 'Take statement', request()->routeIs('statements.create'), 'file-plus'],
                        ['finance.gpay-auditor.index', [], 'GPay Auditor', request()->routeIs('finance.gpay-auditor.*'), 'smartphone'],
                    ],
                ],
            ],
            'People' => [
                'icon' => 'users',
                'active' => request()->routeIs('friends.*', 'friend-splits.*', 'credits.*', 'family-members.*'),
                'sections' => [
                    'People' => [
                        ['friends.index', [], 'Friends & Splits', request()->routeIs('friends.*', 'friend-splits.*'), 'users'],
                        ['credits.index', ['type' => 'credit'], 'Credits (I owe)', request()->routeIs('credits.*') && $creditType === 'credit', 'users'],
                        ['credits.index', ['type' => 'debt'], 'Debts (owed to me)', request()->routeIs('credits.*') && $creditType === 'debt', 'users'],
                        ['family-members.index', [], 'Family Members', request()->routeIs('family-members.*'), 'users'],
                    ],
                ],
            ],
            'Life' => [
                'icon' => 'activity',
                'active' => request()->routeIs('food.*', 'sleep.*', 'activities.*', 'scooter.*', 'vehicles.*', 'petrol.*', 'odometer.*', 'mistakes.*', 'lessons.*', 'notes.*'),
                'sections' => [
                    'Daily life' => [
                        ['food.index', [], 'Food', request()->routeIs('food.*') && ! request()->boolean('is_snack'), 'coffee'],
                        ['food.index', ['is_snack' => 1], 'Snacks', request()->routeIs('food.*') && request()->boolean('is_snack'), 'coffee'],
                        ['sleep.index', [], 'Sleep & Wake', request()->routeIs('sleep.*'), 'activity'],
                        ['activities.index', [], 'Activities', request()->routeIs('activities.*'), 'activity'],
                        ['mistakes.index', [], 'Mistakes & Lessons', request()->routeIs('mistakes.*', 'lessons.*'), 'alert-triangle'],
                        ['notes.index', [], 'Notes', request()->routeIs('notes.*'), 'file-text'],
                    ],
                    'Travel' => [
                        ['scooter.index', [], 'Scooter Trips', request()->routeIs('scooter.index', 'scooter.start', 'scooter.end', 'scooter.edit'), 'navigation'],
                        ['scooter.plan', [], 'Trip Planner', request()->routeIs('scooter.plan*'), 'navigation'],
                        ['vehicles.index', [], 'Vehicles', request()->routeIs('vehicles.*'), 'navigation'],
                        ['petrol.index', [], 'Petrol / Fuel', request()->routeIs('petrol.*'), 'fuel'],
                        ['odometer.index', [], 'Odometer & Mileage', request()->routeIs('odometer.*'), 'navigation'],
                    ],
                ],
            ],
            'Insights' => [
                'icon' => 'pie-chart',
                'active' => request()->routeIs('analytics.*', 'reports.*', 'settings.*'),
                'sections' => [
                    'Insights' => [
                        ['analytics.index', [], 'Analytics', request()->routeIs('analytics.*'), 'pie-chart'],
                        ['reports.index', [], 'Reports & Export', request()->routeIs('reports.*'), 'bar-chart-2'],
                        ['settings.index', [], 'Settings', request()->routeIs('settings.*'), 'settings'],
                    ],
                ],
            ],
        ];
    @endphp

    <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 z-30 bg-white border-r border-slate-200">
        <a href="{{ route('dashboard') }}" class="h-16 shrink-0 flex items-center px-6 gap-3 border-b border-slate-200">
            <span class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white font-bold">LT</span>
            <span>
                <span class="block font-bold text-base tracking-tight text-slate-900">LifeTracker</span>
                <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Personal OS</span>
            </span>
        </a>

        <nav aria-label="Main" data-sidebar-nav class="flex-1 flex flex-col overflow-y-auto px-4 py-4 space-y-1">
            <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-nav-link>
            <x-nav-link href="{{ route('calendar') }}" :active="request()->routeIs('calendar', 'timeline')" icon="calendar">Calendar & Timeline</x-nav-link>

            @foreach($desktopNavGroups as $groupLabel => $group)
                <details data-nav-group="{{ $groupLabel }}" @if($group['active']) data-nav-group-active @endif open class="group/nav pt-2">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-bold uppercase tracking-wider transition hover:bg-slate-50 [&::-webkit-details-marker]:hidden {{ $group['active'] ? 'text-slate-900' : 'text-slate-400 hover:text-slate-600' }}">
                        <span class="flex h-4 w-4 items-center justify-center [&>svg]:h-4 [&>svg]:w-4"><x-nav-icon :name="$group['icon']" /></span>
                        <span class="flex-1">{{ $groupLabel }}</span>
                        <svg class="h-3.5 w-3.5 transition-transform group-open/nav:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="mt-1 space-y-1">
                        @foreach($group['sections'] as $sectionLabel => $links)
                            @if(count($group['sections']) > 1)
                                <p class="px-3.5 pt-1.5 pb-0.5 text-[11px] font-semibold text-slate-400">{{ $sectionLabel }}</p>
                            @endif
                            @foreach($links as [$linkRoute, $linkParams, $linkLabel, $linkActive, $linkIcon])
                                <x-nav-link href="{{ route($linkRoute, $linkParams) }}" :active="$linkActive" :icon="$linkIcon" :aria-current="$linkActive ? 'page' : null">{{ $linkLabel }}</x-nav-link>
                            @endforeach
                        @endforeach
                    </div>
                </details>
            @endforeach
        </nav>

        <div class="p-4 border-t border-slate-200">
            <button type="button" onclick="window.openQuickAdd()" class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm flex items-center justify-center gap-2 cursor-pointer transition active:scale-95">
                <span class="text-lg leading-none font-bold">+</span>
                <span>Quick Record</span>
            </button>
        </div>
    </aside>

    <script>
        (function () {
            let collapsedGroups = [];
            try {
                collapsedGroups = JSON.parse(localStorage.getItem('sidebarCollapsedGroups') || '[]');
            } catch (error) {
                collapsedGroups = [];
            }
            document.querySelectorAll('[data-nav-group]').forEach(function (groupEl) {
                if (collapsedGroups.includes(groupEl.dataset.navGroup) && !groupEl.hasAttribute('data-nav-group-active')) {
                    groupEl.removeAttribute('open');
                }
                groupEl.addEventListener('toggle', function () {
                    const closed = Array.from(document.querySelectorAll('[data-nav-group]:not([open])')).map((el) => el.dataset.navGroup);
                    try {
                        localStorage.setItem('sidebarCollapsedGroups', JSON.stringify(closed));
                    } catch (error) {}
                });
            });
            document.querySelector('[data-sidebar-nav] [aria-current="page"]')?.scrollIntoView({ block: 'nearest' });
        })();
    </script>

    <div class="app-main md:pl-64 min-h-screen min-w-0">
        <header class="sticky top-0 z-20 h-16 bg-white/95 backdrop-blur-md border-b border-slate-200 flex items-center justify-between gap-3 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-3 md:w-80">
                <a href="{{ route('dashboard') }}" class="md:hidden flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center text-white font-bold text-xs">LT</span>
                    <span class="font-bold text-sm text-slate-900">LifeTracker</span>
                </a>
                <form action="{{ route('search') }}" method="GET" class="hidden md:flex items-center w-full relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search expenses, friends, notes, trips..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 transition" />
                </form>
            </div>

            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <a href="{{ route('search') }}" title="Search" class="md:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </a>
                <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                    @if($profilePhotoUrl)
                        <button type="button" data-profile-photo onclick="window.openImageModal(@js($profilePhotoUrl), @js($authUser->name), 'Profile photo')" title="View profile photo" class="shrink-0 rounded-full ring-2 ring-white shadow-md outline outline-1 outline-slate-200 transition hover:scale-105 cursor-pointer">
                            <img src="{{ $profilePhotoUrl }}" alt="{{ $authUser->name }}" class="w-10 h-10 rounded-full object-cover">
                        </button>
                    @else
                        <a href="{{ route('settings.index') }}#profile" title="Add a profile photo" class="w-10 h-10 shrink-0 rounded-full bg-slate-900 flex items-center justify-center text-white font-bold text-sm ring-2 ring-white shadow-md">
                            {{ strtoupper(substr($authUser->name ?? 'U', 0, 1)) }}
                        </a>
                    @endif
                    <div class="hidden sm:block text-left">
                        <span class="block text-sm font-semibold text-slate-800 leading-tight">{{ $authUser->name }}</span>
                        <span class="block text-xs text-slate-400 capitalize">{{ $authUser->roles->first()?->name ?? 'User' }}</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" title="Sign Out" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer">
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
                                            <button type="submit" formaction="{{ $prompt['dismissUrl'] }}" formnovalidate class="p-2 text-white/60 hover:text-white text-sm cursor-pointer" title="Dismiss">&times;</button>
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
                                            <button type="submit" formaction="{{ $prompt['dismissUrl'] }}" formnovalidate class="p-2 text-white/60 hover:text-white text-sm cursor-pointer" title="Dismiss">&times;</button>
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
        @php
            $financeActive = request()->routeIs('finance.*', 'expenses.*', 'expense-groups.*', 'personal-expenses.*', 'all-expenses.*', 'bulk-entry.*', 'income.*', 'statements.*', 'classification.*', 'savings.*', 'daily-balances.*', 'friends.*', 'credits.*', 'family-members.*');
        @endphp
        <button type="button" onclick="window.toggleMobileDrawer('mobile-finance-menu')" aria-haspopup="dialog" aria-controls="mobile-finance-menu" class="flex flex-col items-center justify-center flex-1 py-1 text-sm cursor-pointer {{ $financeActive ? 'text-slate-900 font-bold' : 'text-slate-500' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Finance</span>
        </button>
        <button type="button" onclick="window.toggleMobileDrawer('mobile-more-menu')" aria-haspopup="dialog" aria-controls="mobile-more-menu" class="flex flex-col items-center justify-center flex-1 py-1 text-sm text-slate-500 cursor-pointer">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
            <span>More</span>
        </button>
    </nav>

    @php
        $financeDrawerSections = [
            'Income & Expenses' => [
                ['income.index', [], 'Income', 'bg-emerald-100 text-emerald-700', 'M12 5v14m0-14l-6 6m6-6l6 6'],
                ['expenses.index', [], 'Expenses', 'bg-rose-100 text-rose-700', 'M12 19V5m0 14l-6-6m6 6l6-6'],
                ['personal-expenses.index', [], 'Personal Expenses', 'bg-pink-100 text-pink-700', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['bulk-entry.create', [], 'Bulk Add', 'bg-slate-900 text-white', 'M12 4v16m8-8H4'],
                ['all-expenses.index', [], 'All Expenses', 'bg-sky-100 text-sky-700', 'M4 6h16M4 12h16M4 18h10'],
                ['classification.index', [], 'Classifications', 'bg-violet-100 text-violet-700', 'M7 7h.01M7 3h5l8 8-9 9-8-8V7a4 4 0 014-4z'],
            ],
            'Ledger' => [
                ['finance.index', [], 'Finance Hub', 'bg-slate-900 text-white', 'M3 3v18h18M7 15l4-4 3 3 5-6'],
                ['daily-balances.index', [], 'Daily Cash Register', 'bg-amber-100 text-amber-700', 'M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2zm2 8h4'],
                ['savings.index', [], 'Savings & Funds', 'bg-teal-100 text-teal-700', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 9v1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ],
            'Statements & Checks' => [
                ['statements.index', [], 'Statements', 'bg-indigo-100 text-indigo-700', 'M9 12h6m-6 4h6M7 4h7l5 5v11a1 1 0 01-1 1H7a1 1 0 01-1-1V5a1 1 0 011-1z'],
                ['statements.create', [], 'Take Statement', 'bg-indigo-100 text-indigo-700', 'M12 11v6m-3-3h6M7 4h7l5 5v11a1 1 0 01-1 1H7a1 1 0 01-1-1V5a1 1 0 011-1z'],
                ['finance.gpay-auditor.index', [], 'GPay Auditor', 'bg-emerald-100 text-emerald-700', 'M8 3h8a1 1 0 011 1v16a1 1 0 01-1 1H8a1 1 0 01-1-1V4a1 1 0 011-1zm3 15h2'],
            ],
            'People' => [
                ['friends.index', [], 'Friends & Splits', 'bg-blue-100 text-blue-700', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['credits.index', ['type' => 'credit'], 'Credits (I owe)', 'bg-orange-100 text-orange-700', 'M17 8l4 4m0 0l-4 4m4-4H3'],
                ['credits.index', ['type' => 'debt'], 'Debts (owed to me)', 'bg-lime-100 text-lime-700', 'M7 16l-4-4m0 0l4-4m-4 4h18'],
                ['family-members.index', [], 'Family Members', 'bg-fuchsia-100 text-fuchsia-700', 'M3 12l9-9 9 9M5 10v10h14V10'],
            ],
        ];
        $moreDrawerItems = [
            ['food.index', [], 'Food'],
            ['food.index', ['is_snack' => 1], 'Snacks'],
            ['sleep.index', [], 'Sleep'],
            ['activities.index', [], 'Activities'],
            ['scooter.index', [], 'Trips'],
            ['scooter.plan', [], 'Plan Trip'],
            ['vehicles.index', [], 'Vehicles'],
            ['petrol.index', [], 'Petrol'],
            ['odometer.index', [], 'Odometer'],
            ['mistakes.index', [], 'Mistakes & Lessons'],
            ['notes.index', [], 'Notes'],
            ['analytics.index', [], 'Analytics'],
            ['reports.index', [], 'Reports'],
            ['settings.index', [], 'Settings'],
        ];
    @endphp

    <div id="mobile-more-menu" role="dialog" aria-modal="true" aria-labelledby="mobile-more-title" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex flex-col justify-end" onclick="if(event.target === this) this.classList.add('hidden')">
        <div class="bg-white rounded-t-3xl p-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] space-y-4 max-h-[80vh] overflow-y-auto border-t border-slate-200 shadow-2xl">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <h3 id="mobile-more-title" class="font-bold text-base text-slate-900">All Modules</h3>
                <button type="button" onclick="document.getElementById('mobile-more-menu').classList.add('hidden')" aria-label="Close modules" class="text-slate-400 text-xl font-bold">&times;</button>
            </div>
            <div class="grid grid-cols-3 gap-3 text-center">
                @foreach($moreDrawerItems as [$route, $params, $label])
                    <a href="{{ route($route, $params) }}" class="p-3 rounded-2xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-sm font-medium text-slate-700 transition">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </div>

    <div id="mobile-finance-menu" role="dialog" aria-modal="true" aria-labelledby="mobile-finance-title" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex flex-col justify-end" onclick="if(event.target === this) this.classList.add('hidden')">
        <div class="bg-white rounded-t-3xl p-5 pb-[max(1.5rem,env(safe-area-inset-bottom))] sm:p-6 space-y-5 max-h-[85vh] overflow-y-auto border-t border-slate-200 shadow-2xl">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <h3 id="mobile-finance-title" class="font-bold text-base text-slate-900">Finance</h3>
                <button type="button" onclick="document.getElementById('mobile-finance-menu').classList.add('hidden')" aria-label="Close finance modules" class="text-slate-400 text-xl font-bold">&times;</button>
            </div>
            @foreach($financeDrawerSections as $sectionTitle => $sectionItems)
                <section>
                    <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $sectionTitle }}</p>
                    <div class="grid grid-cols-3 gap-2.5">
                        @foreach($sectionItems as [$route, $params, $label, $chipClasses, $iconPath])
                            <a href="{{ route($route, $params) }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-slate-200 bg-slate-50 px-2 py-3 text-center text-xs font-semibold leading-tight text-slate-700 transition hover:bg-slate-100 active:scale-95">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $chipClasses }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/></svg>
                                </span>
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
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
    <!-- GLOBAL IMAGE LIGHTBOX DIALOG MODAL -->
    <div id="global-image-modal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-3 sm:p-6" onclick="if(event.target === this) window.closeImageModal()">
        <div class="relative max-w-4xl w-full max-h-[92vh] bg-slate-900 border border-slate-700/60 rounded-3xl shadow-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200" onclick="event.stopPropagation()">
            <!-- Header -->
            <div class="px-5 py-3.5 border-b border-slate-800 flex items-center justify-between bg-slate-900/90">
                <div class="min-w-0 pr-4">
                    <h4 id="global-image-modal-title" class="text-sm font-bold text-white truncate">Image Preview</h4>
                    <p id="global-image-modal-subtitle" class="text-xs text-slate-400 truncate"></p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a id="global-image-modal-external" href="#" target="_blank" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-medium transition flex items-center gap-1.5" title="Open full original image in new tab">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        <span class="hidden sm:inline">Open Original</span>
                    </a>
                    <button type="button" onclick="window.closeImageModal()" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition cursor-pointer" title="Close">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <!-- Image Area -->
            <div class="p-3 sm:p-5 bg-black/60 flex items-center justify-center overflow-auto max-h-[calc(92vh-75px)]">
                <img id="global-image-modal-img" src="" alt="Preview" class="max-w-full max-h-[75vh] w-auto h-auto object-contain rounded-xl shadow-lg border border-slate-800">
            </div>
        </div>
    </div>

    <script>
        window.openImageModal = function(url, title = 'Image Preview', subtitle = '') {
            const modal = document.getElementById('global-image-modal');
            const img = document.getElementById('global-image-modal-img');
            const titleEl = document.getElementById('global-image-modal-title');
            const subEl = document.getElementById('global-image-modal-subtitle');
            const extEl = document.getElementById('global-image-modal-external');

            if (!modal || !img) return;

            img.src = url;
            if (extEl) extEl.href = url;
            if (titleEl) titleEl.textContent = title;
            if (subEl) subEl.textContent = subtitle;

            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        };

        window.closeImageModal = function() {
            const modal = document.getElementById('global-image-modal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        };

        window.toggleMobileDrawer = function(id) {
            ['mobile-more-menu', 'mobile-finance-menu'].forEach(function(drawerId) {
                const drawer = document.getElementById(drawerId);
                if (!drawer) return;
                if (drawerId === id) {
                    drawer.classList.toggle('hidden');
                } else {
                    drawer.classList.add('hidden');
                }
            });
        };

        window.openPhotoModal = window.openImageModal;
        window.closePhotoModal = window.closeImageModal;

        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('[data-lightbox-img]');
            if (trigger) {
                e.preventDefault();
                const url = trigger.getAttribute('data-lightbox-img') || trigger.getAttribute('href');
                const title = trigger.getAttribute('data-lightbox-title') || 'Image Preview';
                const sub = trigger.getAttribute('data-lightbox-sub') || '';
                window.openImageModal(url, title, sub);
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.closeImageModal();
                ['mobile-more-menu', 'mobile-finance-menu'].forEach(function(drawerId) {
                    document.getElementById(drawerId)?.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>

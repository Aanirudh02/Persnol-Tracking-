<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} - {{ config('app.name', 'LifeTracker') }}</title>

    <!-- Theme detector: Light UI by default -->
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[#f4f8fc] text-slate-900 dark:bg-slate-950 dark:text-slate-100 antialiased font-sans flex flex-col md:flex-row overflow-x-hidden selection:bg-sky-500 selection:text-white">

    <!-- DESKTOP SIDEBAR -->
    <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 z-30 bg-white dark:bg-slate-900 border-r border-sky-100 dark:border-slate-800 transition-colors duration-200">
        <!-- Logo -->
        <div class="h-16 flex items-center px-6 gap-3 border-b border-sky-100/80 dark:border-slate-800">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-blue-600 flex items-center justify-center text-white shadow-md shadow-sky-500/25 font-bold">
                LT
            </div>
            <div>
                <span class="font-bold text-base tracking-tight text-slate-900 dark:text-white">LifeTracker</span>
                <span class="block text-[10px] font-semibold text-sky-600 dark:text-sky-400 uppercase tracking-wider">Personal OS</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 flex flex-col overflow-y-auto px-4 py-4 space-y-1">
            <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')" icon="home">
                Dashboard
            </x-nav-link>

            <x-nav-link href="{{ route('calendar') }}" :active="request()->routeIs('calendar') || request()->routeIs('timeline')" icon="calendar">
                Calendar & Timeline
            </x-nav-link>

            <div class="pt-2 pb-1">
                <p class="px-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Finance</p>
            </div>

            <x-nav-link href="{{ route('finance.index') }}" :active="request()->routeIs('finance.index')" icon="credit-card">
                Finance Hub
            </x-nav-link>
            <x-nav-link href="{{ route('expenses.index') }}" :active="request()->routeIs('expenses.*')" icon="trending-down">
                Expenses
            </x-nav-link>
            <x-nav-link href="{{ route('income.index') }}" :active="request()->routeIs('income.*')" icon="trending-up">
                Money Received
            </x-nav-link>
            <x-nav-link href="{{ route('payments.index') }}" :active="request()->routeIs('payments.*')" icon="check-square">
                Payments & Reconcile
            </x-nav-link>
            <x-nav-link href="{{ route('friends.index') }}" :active="request()->routeIs('friends.*')" icon="users">
                Friends & Splits
            </x-nav-link>

            <div class="pt-2 pb-1">
                <p class="px-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Life & Habits</p>
            </div>

            <x-nav-link href="{{ route('food.index') }}" :active="request()->routeIs('food.*')" icon="coffee">
                Food & Snacks
            </x-nav-link>
            <x-nav-link href="{{ route('activities.index') }}" :active="request()->routeIs('activities.*')" icon="activity">
                Activities
            </x-nav-link>
            <x-nav-link href="{{ route('scooter.index') }}" :active="request()->routeIs('scooter.*')" icon="navigation">
                Scooter Trips
            </x-nav-link>
            <x-nav-link href="{{ route('petrol.index') }}" :active="request()->routeIs('petrol.*')" icon="fuel">
                Petrol / Fuel
            </x-nav-link>
            <x-nav-link href="{{ route('mistakes.index') }}" :active="request()->routeIs('mistakes.*')" icon="alert-triangle">
                Mistakes & Lessons
            </x-nav-link>
            <x-nav-link href="{{ route('notes.index') }}" :active="request()->routeIs('notes.*')" icon="file-text">
                Notes
            </x-nav-link>

            <div class="pt-2 pb-1">
                <p class="px-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Insights</p>
            </div>

            <x-nav-link href="{{ route('analytics.index') }}" :active="request()->routeIs('analytics.*')" icon="pie-chart">
                Analytics
            </x-nav-link>
            <x-nav-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.*')" icon="bar-chart-2">
                Reports & Export
            </x-nav-link>
            <x-nav-link href="{{ route('settings.index') }}" :active="request()->routeIs('settings.*')" icon="settings">
                Settings
            </x-nav-link>
        </div>

        <!-- Quick Add in Sidebar -->
        <div class="p-4 border-t border-sky-100 dark:border-slate-800">
            <button
                type="button"
                onclick="window.openQuickAdd()"
                class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 text-white font-semibold text-sm shadow-md shadow-sky-500/20 flex items-center justify-center gap-2 cursor-pointer transition active:scale-95"
            >
                <span class="text-lg leading-none font-bold">+</span>
                <span>Quick Record</span>
            </button>
        </div>
    </aside>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="md:pl-64 flex flex-col flex-1 min-h-screen">
        <!-- TOP APP BAR -->
        <header class="sticky top-0 z-20 h-16 bg-white/90 dark:bg-slate-900/80 backdrop-blur-md border-b border-sky-100 dark:border-slate-800 flex items-center justify-between px-4 sm:px-6">
            <!-- Mobile Brand & Title -->
            <div class="flex items-center gap-3 md:w-80">
                <div class="md:hidden flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-sky-500 to-blue-600 flex items-center justify-center text-white font-bold text-xs shadow-sm">
                        LT
                    </div>
                    <span class="font-bold text-sm text-slate-900 dark:text-white">LifeTracker</span>
                </div>

                <!-- Global Search Input (Desktop) -->
                <form action="{{ route('search') }}" method="GET" class="hidden md:flex items-center w-full relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-sky-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search expenses, friends, notes, trips..."
                        class="w-full pl-9 pr-4 py-1.5 bg-sky-50/60 border border-sky-100 dark:bg-slate-800 dark:border-none rounded-xl text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-400 transition"
                    />
                </form>
            </div>

            <!-- Header Right: Theme, Search Icon on Mobile, User Info -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Mobile Search Button -->
                <a href="{{ route('search') }}" class="md:hidden p-2 rounded-xl text-slate-500 hover:bg-sky-50 dark:hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </a>

                <!-- Theme Toggle -->
                <button
                    type="button"
                    onclick="window.toggleTheme()"
                    class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-sky-50 dark:hover:bg-slate-800 transition cursor-pointer"
                    title="Toggle Dark/Light Mode"
                >
                    <span class="text-base dark:hidden">🌙</span>
                    <span class="text-base hidden dark:inline">☀️</span>
                </button>

                <!-- Current User Avatar / Dropdown -->
                <div class="flex items-center gap-2 pl-2 border-l border-sky-100 dark:border-slate-800">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-sky-500 to-blue-600 flex items-center justify-center text-white font-bold text-xs shadow-sm">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <span class="block text-xs font-semibold text-slate-800 dark:text-slate-200 leading-tight">{{ auth()->user()->name }}</span>
                        <span class="block text-[10px] text-slate-400 capitalize">{{ auth()->user()->roles->first()?->name ?? 'User' }}</span>
                    </div>

                    <!-- Logout Button -->
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button
                            type="submit"
                            title="Sign Out"
                            class="p-2 ml-1 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- PAGE BODY CONTENT -->
        <main class="flex-1 pb-24 md:pb-12 px-3 sm:px-6 lg:px-8 pt-6 max-w-7xl mx-auto w-full">
            {{ $slot }}
        </main>
    </div>

    <!-- MOBILE BOTTOM NAVIGATION BAR -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-lg border-t border-sky-100 dark:border-slate-800 flex items-center justify-around h-16 px-2 shadow-lg">
        <!-- Home -->
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-xs {{ request()->routeIs('dashboard') ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Home</span>
        </a>

        <!-- Calendar -->
        <a href="{{ route('calendar') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-xs {{ request()->routeIs('calendar') || request()->routeIs('timeline') ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Calendar</span>
        </a>

        <!-- Universal Floating Quick Add (+) -->
        <div class="relative -top-5 flex-1 flex justify-center">
            <button
                type="button"
                onclick="window.openQuickAdd()"
                class="w-13 h-13 rounded-full bg-gradient-to-tr from-sky-500 to-blue-600 text-white shadow-xl shadow-sky-500/35 flex items-center justify-center text-2xl font-bold active:scale-95 transition-transform cursor-pointer"
                title="Quick Add"
            >
                +
            </button>
        </div>

        <!-- Finance -->
        <a href="{{ route('finance.index') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-xs {{ request()->routeIs('finance.*') || request()->routeIs('expenses.*') || request()->routeIs('income.*') ? 'text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Finance</span>
        </a>

        <!-- More Menu Toggle -->
        <button
            type="button"
            onclick="document.getElementById('mobile-more-menu').classList.toggle('hidden')"
            class="flex flex-col items-center justify-center flex-1 py-1 text-xs text-slate-500 dark:text-slate-400 cursor-pointer"
        >
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
            <span>More</span>
        </button>
    </nav>

    <!-- MOBILE "MORE" DRAWER MODAL -->
    <div id="mobile-more-menu" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex flex-col justify-end" onclick="if(event.target === this) this.classList.add('hidden')">
        <div class="bg-white dark:bg-slate-900 rounded-t-3xl p-6 space-y-4 max-h-[80vh] overflow-y-auto border-t border-sky-100 dark:border-slate-800 shadow-2xl">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-base text-slate-900 dark:text-white">All Modules</h3>
                <button onclick="document.getElementById('mobile-more-menu').classList.add('hidden')" class="text-slate-400 text-xl font-bold">&times;</button>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <a href="{{ route('food.index') }}" class="p-3 rounded-2xl bg-sky-50/70 hover:bg-sky-100 border border-sky-100/80 text-xs font-medium text-slate-700 dark:text-slate-300 flex flex-col items-center gap-1.5 transition">
                    <span class="text-xl">🍔</span> Food & Snacks
                </a>
                <a href="{{ route('activities.index') }}" class="p-3 rounded-2xl bg-sky-50/70 hover:bg-sky-100 border border-sky-100/80 text-xs font-medium text-slate-700 dark:text-slate-300 flex flex-col items-center gap-1.5 transition">
                    <span class="text-xl">🎓</span> Activities
                </a>
                <a href="{{ route('scooter.index') }}" class="p-3 rounded-2xl bg-sky-50/70 hover:bg-sky-100 border border-sky-100/80 text-xs font-medium text-slate-700 dark:text-slate-300 flex flex-col items-center gap-1.5 transition">
                    <span class="text-xl">🛵</span> Scooter Trips
                </a>
                <a href="{{ route('petrol.index') }}" class="p-3 rounded-2xl bg-sky-50/70 hover:bg-sky-100 border border-sky-100/80 text-xs font-medium text-slate-700 dark:text-slate-300 flex flex-col items-center gap-1.5 transition">
                    <span class="text-xl">⛽</span> Petrol / Fuel
                </a>
                <a href="{{ route('mistakes.index') }}" class="p-3 rounded-2xl bg-sky-50/70 hover:bg-sky-100 border border-sky-100/80 text-xs font-medium text-slate-700 dark:text-slate-300 flex flex-col items-center gap-1.5 transition">
                    <span class="text-xl">⚠️</span> Mistakes
                </a>
                <a href="{{ route('notes.index') }}" class="p-3 rounded-2xl bg-sky-50/70 hover:bg-sky-100 border border-sky-100/80 text-xs font-medium text-slate-700 dark:text-slate-300 flex flex-col items-center gap-1.5 transition">
                    <span class="text-xl">📝</span> Notes
                </a>
                <a href="{{ route('analytics.index') }}" class="p-3 rounded-2xl bg-sky-50/70 hover:bg-sky-100 border border-sky-100/80 text-xs font-medium text-slate-700 dark:text-slate-300 flex flex-col items-center gap-1.5 transition">
                    <span class="text-xl">📊</span> Analytics
                </a>
                <a href="{{ route('reports.index') }}" class="p-3 rounded-2xl bg-sky-50/70 hover:bg-sky-100 border border-sky-100/80 text-xs font-medium text-slate-700 dark:text-slate-300 flex flex-col items-center gap-1.5 transition">
                    <span class="text-xl">📑</span> Reports
                </a>
                <a href="{{ route('settings.index') }}" class="p-3 rounded-2xl bg-sky-50/70 hover:bg-sky-100 border border-sky-100/80 text-xs font-medium text-slate-700 dark:text-slate-300 flex flex-col items-center gap-1.5 transition">
                    <span class="text-xl">⚙️</span> Settings
                </a>
            </div>
        </div>
    </div>

    <!-- UNIVERSAL QUICK-ADD MODAL COMPONENT -->
    <x-quick-add-modal />

    <!-- TOAST CONTAINER -->
    <div id="toast-container" class="fixed bottom-20 md:bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', () => window.showToast("{{ session('success') }}", 'success'));
        </script>
    @endif
    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', () => window.showToast("{{ session('error') }}", 'error'));
        </script>
    @endif
    @if (session('info'))
        <script>
            document.addEventListener('DOMContentLoaded', () => window.showToast("{{ session('info') }}", 'info'));
        </script>
    @endif

</body>
</html>

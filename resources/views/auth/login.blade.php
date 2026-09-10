<x-guest-layout title="Sign In">
    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4">
        <!-- Logo & Brand Header -->
        <div class="flex items-center justify-center gap-3 mb-2">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-500 via-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-xl shadow-sky-500/25">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div class="text-left">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">LifeTracker</h1>
                <p class="text-xs font-semibold text-sky-600">Personal OS & Tracker</p>
            </div>
        </div>

        <h2 class="mt-4 text-center text-xl font-semibold text-slate-800">
            Welcome back
        </h2>
        <p class="text-center text-xs text-slate-500 mt-1">
            Private system. Authorized personal access only.
        </p>

        <!-- Main Card -->
        <div class="mt-6 bg-white py-8 px-6 sm:px-8 shadow-xl shadow-sky-100/60 border border-sky-100 rounded-3xl">
            
            @if ($errors->any())
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs leading-relaxed">
                    <div class="flex items-center gap-2 font-semibold text-rose-800 mb-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Authentication Error
                    </div>
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if (session('success'))
                <div class="mb-5 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Email / Phone input -->
                <div>
                    <label for="login" class="block text-xs font-medium text-slate-700 mb-1.5">
                        Email or Phone Number
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-sky-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input
                            id="login"
                            name="login"
                            type="text"
                            autocomplete="username"
                            required
                            value="{{ old('login') }}"
                            placeholder="aanirudhch@gmail.com or 7010186524"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 text-slate-900 transition placeholder-slate-400"
                        />
                    </div>
                </div>

                <!-- Password input -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-medium text-slate-700">
                            Password
                        </label>
                        <a href="{{ route('password.request') }}" class="text-xs font-medium text-sky-600 hover:text-sky-700 transition">
                            Forgot Password?
                        </a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-sky-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                            class="w-full pl-10 pr-11 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 text-slate-900 transition placeholder-slate-400"
                        />
                        <button
                            type="button"
                            id="toggle-password"
                            aria-label="Show password"
                            aria-pressed="false"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-sky-600 transition"
                        >
                            <svg id="password-eye" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            name="remember"
                            id="remember"
                            value="1"
                            @checked(old('remember'))
                            class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500 border-slate-300 bg-slate-50"
                        />
                        <span class="text-xs text-slate-600">Remember Me</span>
                    </label>

                    <button type="button" onclick="window.toggleTheme()" class="text-xs text-slate-500 hover:text-slate-700 transition flex items-center gap-1">
                        <span>🌓</span> Mode
                    </button>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 hover:from-sky-600 hover:to-blue-700 text-white font-semibold text-sm shadow-lg shadow-sky-500/25 hover:shadow-sky-500/35 transition-all duration-200 active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span>Sign In to LifeTracker</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </form>

            <!-- Quick Auto-Fill for Owner in local dev -->
            @if(app()->environment('local'))
                <div class="mt-6 pt-4 border-t border-slate-100 text-center">
                    <button
                        type="button"
                        onclick="document.getElementById('login').value='aanirudhch@gmail.com'; document.getElementById('password').value='Raja_Raja02';"
                        class="text-xs text-sky-600 hover:text-sky-700 font-medium transition underline cursor-pointer"
                    >
                        ⚡ Fill Admin Credentials (Aanirudh)
                    </button>
                </div>
            @endif
        </div>

        <p class="text-center text-[11px] text-slate-400 mt-8">
            LifeTracker &copy; {{ date('Y') }} &bull; Private & Encrypted
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const password = document.getElementById('password');
            const toggle = document.getElementById('toggle-password');

            toggle?.addEventListener('click', () => {
                const showing = password.type === 'text';
                password.type = showing ? 'password' : 'text';
                toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
                toggle.setAttribute('aria-pressed', String(!showing));
            });
        });
    </script>
</x-guest-layout>

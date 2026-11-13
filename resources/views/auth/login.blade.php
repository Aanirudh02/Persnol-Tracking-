<x-guest-layout title="Sign In">
    <div class="fixed inset-0 z-10 overflow-y-auto overscroll-contain bg-[#f3f7ff]">
        <div class="grid min-h-dvh grid-cols-1 lg:grid-cols-2">
            <aside class="relative isolate flex items-center px-5 pb-20 pt-[max(1.75rem,env(safe-area-inset-top))] text-white sm:px-10 sm:pb-24 lg:min-h-dvh lg:px-12 lg:py-10 xl:px-16">
                <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden rounded-b-[2rem] bg-[radial-gradient(ellipse_at_85%_90%,rgba(0,146,255,.82),transparent_38%),radial-gradient(ellipse_at_95%_5%,rgba(14,111,226,.78),transparent_38%),linear-gradient(135deg,#071b52,#063a91_72%,#0757bd)] lg:rounded-none" aria-hidden="true">
                    <div class="absolute -right-52 -top-40 h-[32rem] w-[32rem] rounded-full border border-blue-200/20 shadow-[0_0_0_38px_rgba(173,211,255,.05),0_0_0_90px_rgba(173,211,255,.04)]"></div>
                    <div class="absolute inset-x-0 bottom-0 h-1/3 bg-[radial-gradient(rgba(146,201,255,.55)_1px,transparent_1.5px)] bg-[size:19px_19px] opacity-30 [mask-image:linear-gradient(90deg,#000,transparent_45%)]"></div>
                </div>

                <div class="mx-auto w-full max-w-lg">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-3 text-white no-underline">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 via-blue-600 to-violet-600 shadow-lg shadow-blue-950/30 sm:h-12 sm:w-12" aria-hidden="true">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 8-9M15 6h6v6" />
                            </svg>
                        </span>
                        <span class="flex flex-col leading-tight">
                            <strong class="text-xl font-bold tracking-tight sm:text-2xl">LifeTracker</strong>
                            <small class="mt-0.5 text-xs text-blue-200 sm:text-sm">Personal OS &amp; Tracker</small>
                        </span>
                    </a>

                    <div class="mt-5 sm:mt-8 lg:mt-10">
                        <h1 class="text-[clamp(1.5rem,6vw,2.4rem)] font-bold leading-tight tracking-tight">
                            Track. Organize. <span class="text-cyan-300">Achieve.</span>
                        </h1>
                        <p class="mt-2 max-w-md text-sm leading-relaxed text-blue-100 sm:text-base">
                            Your personal companion for a smarter, more productive life.
                        </p>
                    </div>
                    <ul class="mt-5 hidden space-y-3 sm:block lg:mt-6">
                        <li class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-400/20 text-blue-100" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M18 17V9m-5 8V5m-5 12v-4" /></svg>
                            </span>
                            <span><strong class="block text-sm font-medium">Track your goals</strong><small class="text-xs text-blue-200">Stay on top of what matters</small></span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-400/20 text-blue-100" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </span>
                            <span><strong class="block text-sm font-medium">Manage your tasks</strong><small class="text-xs text-blue-200">Get things done, effortlessly</small></span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-400/20 text-blue-100" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l8 4v5c0 5-3.4 8-8 9-4.6-1-8-4-8-9V7l8-4zm-3 9 2 2 4-4" /></svg>
                            </span>
                            <span><strong class="block text-sm font-medium">Secure &amp; private</strong><small class="text-xs text-blue-200">Your data, your control</small></span>
                        </li>
                    </ul>

                    <svg class="mx-auto mt-3 hidden w-full max-w-[330px] lg:block" viewBox="0 0 520 340" role="img" aria-label="Task dashboard illustration">
                        <defs>
                            <linearGradient id="login-panel" x1="0" x2="1" y1="0" y2="1">
                                <stop offset="0" stop-color="#809bff" stop-opacity=".85" />
                                <stop offset="1" stop-color="#007bff" stop-opacity=".48" />
                            </linearGradient>
                            <linearGradient id="login-base" x1="0" x2="1" y1="0" y2="1">
                                <stop offset="0" stop-color="#6e8dff" />
                                <stop offset="1" stop-color="#00baff" />
                            </linearGradient>
                        </defs>
                        <ellipse cx="260" cy="285" rx="235" ry="30" fill="#087cff" opacity=".22" />
                        <path d="M92 246 302 173l144 52-215 83z" fill="url(#login-base)" stroke="#59c7ff" stroke-width="3" />
                        <path d="m231 308 215-83v14l-215 83z" fill="#1644c7" />
                        <path d="m92 246 139 62v14L92 260z" fill="#2b5fe0" />
                        <g transform="translate(145 50) skewY(-16)">
                            <rect width="255" height="170" rx="12" fill="url(#login-panel)" stroke="#92bbff" stroke-width="3" />
                            <rect x="17" y="21" width="93" height="127" rx="7" fill="#e1eaff" opacity=".72" />
                            <rect x="28" y="33" width="70" height="9" rx="4" fill="#6486ef" />
                            <path d="M28 61h15m10 0h15m10 0h15M28 83h15m10 0h15m10 0h15M28 105h15m10 0h15m10 0h15M28 127h15m10 0h15m10 0h15" stroke="#6988df" stroke-width="7" stroke-linecap="round" />
                            <rect x="126" y="22" width="111" height="15" rx="7" fill="#00c8ed" />
                            <rect x="126" y="52" width="111" height="52" rx="7" fill="#184dbf" opacity=".45" />
                            <path d="M143 91V80m19 11V69m19 22V61m19 30V74" stroke="#16d3f0" stroke-width="12" stroke-linecap="round" />
                            <rect x="126" y="117" width="111" height="31" rx="7" fill="#1745a4" opacity=".43" />
                            <path d="m140 134 14 8 15-14 14 7 19-17" fill="none" stroke="#8beaff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                        </g>
                        <circle cx="337" cy="217" r="37" fill="#062c86" opacity=".8" stroke="#12cafa" stroke-width="5" />
                        <path d="m319 216 12 12 25-28" fill="none" stroke="#13d5ff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
            </aside>

            <main class="relative flex min-w-0 justify-center px-4 pb-[max(2rem,env(safe-area-inset-bottom))] sm:px-6 lg:items-center lg:px-8 lg:py-10">
                <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                    <div class="absolute -right-56 top-[18%] h-[32rem] w-[32rem] rounded-full border-[38px] border-blue-100/30 bg-blue-100/20"></div>
                    <div class="absolute -bottom-[24rem] -right-28 h-[40rem] w-[40rem] rounded-full border-[34px] border-white/60 bg-blue-100/25"></div>
                </div>
                <div class="relative z-10 -mt-14 h-fit w-full max-w-[460px] rounded-[1.5rem] border border-white/90 bg-white/95 px-5 py-7 shadow-[0_18px_50px_rgba(23,64,139,.14)] sm:-mt-16 sm:px-10 sm:py-9 lg:mt-0 lg:max-w-[510px]">
                    <div class="hidden justify-center lg:flex">
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-3 text-slate-900 no-underline">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 via-blue-600 to-violet-600 text-lg text-white shadow-md shadow-blue-500/20" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 8-9M15 6h6v6" />
                                </svg>
                            </span>
                            <span class="flex flex-col leading-tight">
                                <strong class="text-xl font-bold tracking-tight">LifeTracker</strong>
                                <small class="mt-1 text-xs font-medium text-sky-600">Personal OS &amp; Tracker</small>
                            </span>
                        </a>
                    </div>

                    <div class="mb-5 flex items-center gap-2 text-sky-600 lg:my-6" aria-hidden="true">
                        <span class="h-px flex-1 bg-gradient-to-r from-transparent to-indigo-200"></span>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12h5l3-8 4 16 3-8h3" /></svg>
                        <span class="h-px flex-1 bg-gradient-to-l from-transparent to-indigo-200"></span>
                    </div>

                    <h2 class="text-center text-2xl font-bold text-slate-900">Welcome back</h2>
                    <p class="mt-1 text-center text-sm text-slate-500">Sign in to continue to your account</p>

                    @if ($errors->any())
                        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs leading-relaxed text-rose-700" role="alert">
                            <div class="mb-1 flex items-center gap-2 font-semibold text-rose-800">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Authentication Error
                            </div>
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-700" role="status">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('login.submit') }}" method="POST" class="mt-6 space-y-4 sm:mt-7">
                        @csrf

                        <div>
                            <label for="login" class="mb-1.5 block text-sm font-semibold text-slate-800 sm:text-xs">
                                Email or Phone Number
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sky-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    placeholder="Email or phone number"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3.5 pl-10 pr-4 text-base text-slate-900 sm:text-sm transition placeholder:text-slate-400 focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20"
                                />
                            </div>
                        </div>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label for="password" class="block text-sm font-semibold text-slate-800 sm:text-xs">
                                    Password
                                </label>
                                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-sky-600 transition hover:text-sky-700">
                                    Forgot Password?
                                </a>
                            </div>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sky-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    required
                                    placeholder="Password"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3.5 pl-10 pr-11 text-base text-slate-900 sm:text-sm transition placeholder:text-slate-400 focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20"
                                />
                                <button
                                    type="button"
                                    id="toggle-password"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition hover:text-sky-600"
                                >
                                    <svg id="password-eye" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center pt-0.5">
                            <label for="remember" class="flex cursor-pointer select-none items-center gap-2">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    id="remember"
                                    value="1"
                                    @checked(old('remember'))
                                    class="h-4 w-4 rounded border-slate-300 bg-slate-50 text-sky-600 focus:ring-sky-500"
                                />
                                <span class="text-sm text-slate-700 sm:text-xs">Remember Me</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-sky-500/20 transition-all duration-200 hover:from-sky-600 hover:to-blue-700 hover:shadow-sky-500/30 active:scale-[0.99]"
                        >
                            <span>Sign In to LifeTracker</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </form>

                    <p class="mt-4 text-center text-[10px] text-slate-400">
                        &copy; {{ date('Y') }} LifeTracker. All rights reserved.
                    </p>
                </div>
            </main>
        </div>
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

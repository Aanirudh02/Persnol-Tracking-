<x-guest-layout title="Reset Password">
    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="flex items-center justify-center gap-3 mb-2">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-500 flex items-center justify-center text-white shadow-xl shadow-indigo-500/25">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
        </div>

        <h2 class="mt-4 text-center text-xl font-semibold text-slate-800 dark:text-slate-200">
            Forgot Password
        </h2>
        <p class="text-center text-xs text-slate-500 dark:text-slate-400 mt-1">
            Enter your account email to receive a password reset link.
        </p>

        <div class="mt-6 bg-white dark:bg-slate-900 py-8 px-6 sm:px-8 shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-200/80 dark:border-slate-800 rounded-3xl">
            @if (session('status'))
                <div class="mb-5 p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-xs">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('dev_reset_url'))
                <div class="mb-5 p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50 text-amber-800 dark:text-amber-300 text-xs break-all">
                    <p class="font-semibold mb-1">Local Testing Direct Link:</p>
                    <a href="{{ session('dev_reset_url') }}" class="underline hover:text-amber-900">{{ session('dev_reset_url') }}</a>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 text-rose-700 dark:text-rose-300 text-xs">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        Account Email Address
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        required
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white"
                    />
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 transition"
                    >
                        Send Reset Link
                    </button>
                </div>

                <div class="text-center pt-2">
                    <a href="{{ route('login') }}" class="text-xs font-medium text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400">
                        &larr; Return to Sign In
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>

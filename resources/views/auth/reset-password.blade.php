<x-guest-layout title="Set New Password">
    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4">
        <h2 class="mt-4 text-center text-xl font-semibold text-slate-800 dark:text-slate-200">
            Set New Password
        </h2>
        <p class="text-center text-xs text-slate-500 dark:text-slate-400 mt-1">
            Choose a strong password to protect your private data.
        </p>

        <div class="mt-6 bg-white dark:bg-slate-900 py-8 px-6 sm:px-8 shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-200/80 dark:border-slate-800 rounded-3xl">
            @if ($errors->any())
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 text-rose-700 dark:text-rose-300 text-xs">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        Email Address
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        required
                        value="{{ old('email', $email) }}"
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white"
                    />
                </div>

                <div>
                    <label for="password" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        New Password
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white"
                    />
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        Confirm New Password
                    </label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white"
                    />
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 transition"
                    >
                        Update Password & Log In
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>

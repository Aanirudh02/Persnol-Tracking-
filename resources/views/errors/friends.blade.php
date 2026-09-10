<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Friends page unavailable - {{ config('app.name', 'LifeTracker') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 px-4 py-12 text-slate-900">
    <main class="mx-auto max-w-xl rounded-3xl border border-rose-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-xs font-bold uppercase tracking-wider text-rose-600">Friends & Splits</p>
        <h1 class="mt-2 text-2xl font-bold">We could not load this page</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">
            The request failed while loading your friends data. Your records were not changed.
        </p>

        <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">Common causes</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Supabase connection or temporary database timeout.</li>
                <li>A missing table or column after a migration.</li>
                <li>A PostgreSQL query or relationship error in the finance data.</li>
                <li>An individual friend, split, debt, or settlement row with invalid data.</li>
            </ul>
        </div>

        <p class="mt-5 text-xs text-slate-500">
            Give this reference to the developer when checking Render Application Logs:
            <strong class="font-mono text-slate-800">{{ $reference }}</strong>
        </p>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('friends.index') }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Try again</a>
            <a href="{{ route('dashboard') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Go to dashboard</a>
        </div>
    </main>
</body>
</html>
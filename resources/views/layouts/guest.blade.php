<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} - {{ config('app.name', 'LifeTracker') }}</title>

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
<body class="h-full bg-gradient-to-br from-sky-50/60 via-slate-50 to-blue-50/40 text-slate-800 dark:bg-slate-950 dark:text-slate-100 antialiased selection:bg-sky-500 selection:text-white transition-colors duration-200">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>

    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

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
</body>
</html>

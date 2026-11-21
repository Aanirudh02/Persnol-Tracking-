<x-app-layout title="Register Lesson">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a href="{{ route('lessons.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to Lessons</a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-2">Register a Lesson</h1>
            <p class="text-xs text-slate-500">A lesson can stand on its own, or be linked to the mistake it came from.</p>
        </div>

        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <form action="{{ route('lessons.store') }}" method="POST" class="space-y-4 text-xs">
                @include('lessons._form')
                <button type="submit" class="w-full py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-semibold text-sm shadow-md shadow-sky-600/20 transition cursor-pointer">Save Lesson</button>
            </form>
        </div>
    </div>
</x-app-layout>

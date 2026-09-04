<x-app-layout title="Edit Mistake">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a href="{{ route('mistakes.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to Mistakes</a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-2">Edit Mistake & Lesson</h1>
        </div>

        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <form action="{{ route('mistakes.update', $mistake) }}" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Title *</label>
                    <input type="text" name="title" required value="{{ old('title', $mistake->title) }}" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Date *</label>
                        <input type="date" name="date" required value="{{ old('date', $mistake->date->toDateString()) }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                        <select name="category_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $mistake->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Severity *</label>
                        <select name="severity" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                            @foreach(['Low', 'Medium', 'High', 'Critical'] as $sev)
                                <option value="{{ $sev }}" {{ $mistake->severity == $sev ? 'selected' : '' }}>{{ $sev }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Status *</label>
                        <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                            @foreach(['Open', 'Working On It', 'Resolved', 'Learned'] as $st)
                                <option value="{{ $st }}" {{ $mistake->status == $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">What Happened? *</label>
                    <textarea name="what_happened" required rows="2" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">{{ old('what_happened', $mistake->what_happened) }}</textarea>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Why Did It Happen? *</label>
                    <textarea name="why_happened" required rows="2" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">{{ old('why_happened', $mistake->why_happened) }}</textarea>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Lesson Learned *</label>
                    <textarea name="lesson_learned" required rows="2" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">{{ old('lesson_learned', $mistake->lesson_learned) }}</textarea>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Prevention Plan *</label>
                    <textarea name="prevention_plan" required rows="2" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">{{ old('prevention_plan', $mistake->prevention_plan) }}</textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-md transition">
                        Update Mistake Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

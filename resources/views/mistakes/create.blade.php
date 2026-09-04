<x-app-layout title="Record Mistake & Lesson">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a href="{{ route('mistakes.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to Mistakes</a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-2">Record Mistake & Lesson Learned</h1>
            <p class="text-xs text-slate-500">Reflect on what happened, understand root causes, and write down actionable prevention steps.</p>
        </div>

        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <form action="{{ route('mistakes.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Title *</label>
                    <input type="text" name="title" required value="{{ old('title') }}" placeholder="e.g. Forgot to submit assignment on time" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white text-sm font-semibold">
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Date *</label>
                        <input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                        <select name="category_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Severity *</label>
                        <select name="severity" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Status *</label>
                        <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                            <option value="Open">Open</option>
                            <option value="Working On It">Working On It</option>
                            <option value="Resolved" selected>Resolved</option>
                            <option value="Learned">Learned</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">What Happened? *</label>
                    <textarea name="what_happened" required rows="2" placeholder="Describe the incident accurately..." class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">{{ old('what_happened') }}</textarea>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Why Did It Happen? (Root Cause) *</label>
                    <textarea name="why_happened" required rows="2" placeholder="Why did this occur? What was missing?" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">{{ old('why_happened') }}</textarea>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">What Should I Have Done? *</label>
                    <textarea name="what_should_have_done" required rows="2" placeholder="The right course of action would have been..." class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">{{ old('what_should_have_done') }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Lesson Learned *</label>
                        <textarea name="lesson_learned" required rows="2" placeholder="Key philosophy / lesson taken away..." class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">{{ old('lesson_learned') }}</textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">How Will I Avoid It Next Time? (Prevention) *</label>
                        <textarea name="prevention_plan" required rows="2" placeholder="Concrete prevention rules / alarms / checklists..." class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">{{ old('prevention_plan') }}</textarea>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tags</label>
                    <input type="text" name="tags" value="{{ old('tags') }}" placeholder="college, discipline, finance, schedule" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-sm shadow-md shadow-rose-600/20 transition">
                        Save Mistake & Lesson Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

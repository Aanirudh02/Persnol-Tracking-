<x-app-layout title="Money Received (Income)">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Money Received</h1>
                <p class="text-xs text-slate-500">Total recorded: <span class="font-bold text-emerald-600">₹{{ number_format($totalAmount, 2) }}</span></p>
            </div>
            <a href="{{ route('income.create') }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs shadow-md shadow-emerald-600/20 flex items-center gap-1.5 self-start">
                <span>+</span> Record Income
            </a>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            @if($incomes->isEmpty())
                <div class="text-center py-16 text-slate-400 text-xs">
                    <span class="text-3xl block mb-2">💵</span>
                    No income records found.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="py-3.5 px-4">Date</th>
                                <th class="py-3.5 px-4">Source</th>
                                <th class="py-3.5 px-4">Description</th>
                                <th class="py-3.5 px-4">Amount</th>
                                <th class="py-3.5 px-4">Method</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($incomes as $inc)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ $inc->date->format('d M Y') }}</td>
                                    <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">{{ $inc->source }}</td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $inc->description ?? '--' }}</td>
                                    <td class="py-3 px-4 font-bold text-emerald-600 dark:text-emerald-400 text-sm whitespace-nowrap">₹{{ number_format($inc->amount, 2) }}</td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $inc->payment_method }}</td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <form action="{{ route('income.destroy', $inc) }}" method="POST" class="inline" onsubmit="return confirm('Delete this income record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-500 font-semibold cursor-pointer">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $incomes->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

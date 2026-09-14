<x-app-layout title="{{ $statement->title }}">
    <style>
        @media print {
            body { background: white !important; color: black !important; }
            aside, header, nav, .no-print { display: none !important; }
            main { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
            .print-card { border: 1px solid #e2e8f0 !important; box-shadow: none !important; }
            strong, .statement-friend-paid { font-weight: 700 !important; color: #000000 !important; }
        }
    </style>

    <div class="space-y-6">
        <!-- Top Toolbar -->
        <div class="flex items-center justify-between no-print">
            <a href="{{ route('statements.index', ['tab' => $statement->type]) }}" class="px-3.5 py-2 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-xl shadow-xs transition flex items-center gap-1.5">
                &larr; Back to Statements
            </a>
            <div class="flex items-center gap-2">
                @if($allowStatementDeletion)
                    <form action="{{ route('statements.destroy', $statement) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this statement? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3.5 py-2 border border-rose-200 bg-white hover:bg-rose-50 text-rose-600 font-semibold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <span>🗑️</span> Delete Statement
                        </button>
                    </form>
                @endif
                <button type="button" onclick="window.print()" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-sm transition flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print Statement
                </button>
            </div>
        </div>

        <!-- Statement Document Card -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-8 print-card">
            <!-- Statement Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-100 gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase {{ $statement->isPersonal() ? 'bg-pink-50 text-pink-700 border border-pink-200' : 'bg-slate-100 text-slate-700' }}">
                            {{ ucfirst($statement->type) }} Statement
                        </span>
                        <span class="text-xs text-slate-400">Period: <strong class="text-slate-700 font-semibold uppercase">{{ $statement->period_type }}</strong></span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 mt-2">{{ $statement->title }}</h1>
                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                        <span>🕒</span>
                        <span>Statement Taken: <strong class="text-slate-800 font-bold">{{ $statement->created_at->format('l, F j, Y \a\t h:i A') }}</strong></span>
                    </p>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 sm:text-right">
                    <span class="text-[10px] uppercase font-semibold text-slate-400 block">Total Statement Value</span>
                    <span class="text-2xl font-bold {{ $statement->isPersonal() ? 'text-pink-600' : 'text-slate-900' }}">
                        ₹{{ number_format($statement->total_amount, 2) }}
                    </span>
                </div>
            </div>

            <!-- Metrics Summary Row -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] uppercase font-semibold text-slate-400 block">Line Items</span>
                    <span class="text-lg font-bold text-slate-900 mt-0.5 block">{{ $items->count() }} {{ Str::plural('transaction', $items->count()) }}</span>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] uppercase font-semibold text-slate-400 block">Categories</span>
                    <span class="text-lg font-bold text-slate-900 mt-0.5 block">{{ $categoryBreakdown->count() }} {{ Str::plural('category', $categoryBreakdown->count()) }}</span>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] uppercase font-semibold text-slate-400 block">Date Range</span>
                    <span class="text-xs font-bold text-slate-900 mt-1 block">
                        @if($statement->start_date && $statement->end_date)
                            {{ $statement->start_date->format('M j, Y') }} - {{ $statement->end_date->format('M j, Y') }}
                        @else
                            All Time Records
                        @endif
                    </span>
                </div>
            </div>

            <!-- Category Breakdown Summary -->
            @if($categoryBreakdown->isNotEmpty())
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Category Breakdown</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach($categoryBreakdown as $catName => $catData)
                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                                <span class="text-xs font-semibold text-slate-700 truncate block">{{ $catName }}</span>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-[10px] text-slate-400">{{ $catData['count'] }} {{ Str::plural('item', $catData['count']) }}</span>
                                    <span class="text-xs font-bold text-slate-900">₹{{ number_format($catData['total'], 2) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Line Items Table -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Statement Line Items</h3>
                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">#</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Description</th>
                                <th class="py-3 px-4">Category</th>
                                <th class="py-3 px-4">Method</th>
                                <th class="py-3 px-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($items as $index => $item)
                                @php
                                    $itemAmount = $statement->isNormal() ? $item->totalAmount() : (float) $item->amount;
                                    $itemDate = $statement->isNormal() ? $item->date : ($item->expense_date ?? $item->created_at);
                                @endphp
                                <tr class="hover:bg-slate-50/70">
                                    <td class="py-3 px-4 text-slate-400 font-mono text-[10px]">{{ $index + 1 }}</td>
                                    <td class="py-3 px-4 font-mono text-slate-600 whitespace-nowrap">{{ $itemDate ? \Carbon\Carbon::parse($itemDate)->format('d M Y') : '-' }}</td>
                                    <td class="py-3 px-4">
                                        <span class="font-semibold text-slate-900">{{ $item->description ?: ($item->title ?? ($item->category?->name ?? 'Expense Item')) }}</span>
                                        @if(!empty($item->notes))
                                            @php
                                                $rawNotes = $item->notes;
                                                $formattedNotes = e($rawNotes);

                                                $friendContributions = [];
                                                if ($statement->isNormal()) {
                                                    if ($item->relationLoaded('friendSplits') && $item->friendSplits->isNotEmpty()) {
                                                        foreach ($item->friendSplits as $s) {
                                                            if ((float) $s->paid_by_friend_amount > 0 && $s->friend?->name) {
                                                                $friendContributions[$s->friend->name] = (float) $s->paid_by_friend_amount;
                                                            }
                                                        }
                                                    }
                                                    if (empty($friendContributions) && $item->relationLoaded('friendSplit') && $item->friendSplit?->friend?->name) {
                                                        if ((float) $item->friendSplit->paid_by_friend_amount > 0) {
                                                            $friendContributions[$item->friendSplit->friend->name] = (float) $item->friendSplit->paid_by_friend_amount;
                                                        }
                                                    }
                                                    if (empty($friendContributions) && $item->relationLoaded('paidByFriend') && $item->paidByFriend?->name) {
                                                        $friendContributions[$item->paidByFriend->name] = (float) $item->totalAmount();
                                                    }
                                                    if (empty($friendContributions) && !empty($item->paid_by) && $item->paid_by !== 'Me') {
                                                        $friendContributions[$item->paid_by] = (float) $item->totalAmount();
                                                    }
                                                }

                                                if (preg_match('/Friend\(s\)\s*paid:\s*(?:₹|Rs\.?)?\s*([0-9.,]+)/i', $rawNotes, $matches)) {
                                                    $amountVal = $matches[1];
                                                    if (!empty($friendContributions)) {
                                                        $friendStr = collect($friendContributions)
                                                            ->map(fn($amt, $name) => '<strong class="font-bold text-slate-900 statement-friend-paid">' . e($name) . ':</strong> ₹' . number_format($amt, 2))
                                                            ->implode(', ');
                                                    } else {
                                                        $fallbackName = (!empty($item->paid_by) && $item->paid_by !== 'Me') ? $item->paid_by : 'Friend';
                                                        $friendStr = '<strong class="font-bold text-slate-900 statement-friend-paid">' . e($fallbackName) . ':</strong> ₹' . $amountVal;
                                                    }
                                                    $formattedNotes = preg_replace('/Friend\(s\)\s*paid:\s*(?:₹|Rs\.?)?\s*[0-9.,]+/i', $friendStr, $formattedNotes);
                                                } else {
                                                    $formattedNotes = preg_replace_callback('/([A-Za-z0-9\s]+):\s*(₹\s*[0-9.,]+)/', function($m) {
                                                        $label = trim($m[1]);
                                                        if (strcasecmp($label, 'Total bill') === 0 || strcasecmp($label, 'Payment breakdown') === 0) {
                                                            return $m[0];
                                                        }
                                                        if (strcasecmp($label, 'You paid') === 0) {
                                                            return '<span class="text-slate-600">' . $label . ':</span> ' . $m[2];
                                                        }
                                                        return '<strong class="font-bold text-slate-900 statement-friend-paid">' . e($label) . ':</strong> ' . $m[2];
                                                    }, $formattedNotes);
                                                }
                                            @endphp
                                            <span class="block text-[11px] text-slate-500 italic mt-0.5">{!! $formattedNotes !!}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                            {{ $item->category?->name ?? 'Uncategorized' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 font-medium whitespace-nowrap">{{ $item->payment_method ?: 'Cash' }}</td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900 whitespace-nowrap">
                                        ₹{{ number_format($itemAmount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-slate-400">No items found matching this statement criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                            <tr>
                                <td colspan="5" class="py-3 px-4 text-right text-xs uppercase tracking-wider text-slate-500">Statement Total:</td>
                                <td class="py-3 px-4 text-right text-base font-black {{ $statement->isPersonal() ? 'text-pink-600' : 'text-slate-900' }}">
                                    ₹{{ number_format($statement->total_amount, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

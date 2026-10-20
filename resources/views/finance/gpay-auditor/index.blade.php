<x-app-layout title="GPay Screenshot Auditor">
    <div class="space-y-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('payments.index') }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 transition">&larr; Back to Payments</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">GPay Auditor</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">GPay Screenshot Auditor</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Audit your real Google Pay transaction screenshots against website expenses &amp; incomes to uncover fake, unbacked, or missing items.
                </p>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl px-4 py-2.5 shadow-sm flex items-center gap-3">
                <span class="text-2xl">💳</span>
                <div>
                    <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Website UPI Balance</div>
                    <div class="text-lg font-bold {{ $upiBalance >= 0 ? 'text-slate-900 dark:text-white' : 'text-rose-600' }}">
                        ₹{{ number_format($upiBalance, 2) }}
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-medium flex items-center justify-between shadow-sm">
                <span>{{ session('success') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 font-bold ml-4">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200/80 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-medium flex items-center justify-between shadow-sm">
                <span>{{ session('error') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800 font-bold ml-4">&times;</button>
            </div>
        @endif

        <!-- Main Layout: Upload & Form (Left) + Questions & Audit Results (Right) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Left Panel: Input & OCR Scanner -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>📸</span> Upload Screenshot or Paste Text
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Select your GPay screenshot to extract text, or paste transactions directly.
                    </p>

                    <form action="{{ route('finance.gpay-auditor.audit') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4" id="auditForm">
                        @csrf

                        <!-- Drag & Drop Zone -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                GPay Screenshot Image
                            </label>
                            <div id="dropZone" class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-emerald-500 rounded-2xl p-5 text-center transition cursor-pointer bg-slate-50/50 dark:bg-slate-800/30">
                                <input type="file" name="screenshot" id="screenshotInput" accept="image/*" class="hidden">
                                <div id="dropPrompt">
                                    <span class="text-3xl block mb-1">🖼️</span>
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 block">Click or Drag &amp; Drop Screenshot</span>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">PNG, JPG, WebP up to 10MB</span>
                                </div>
                                <div id="previewContainer" class="hidden">
                                    <img id="imagePreview" src="" alt="Screenshot Preview" class="max-h-48 mx-auto rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                                    <span id="fileName" class="text-[11px] font-semibold text-slate-600 dark:text-slate-300 mt-2 block"></span>
                                    <button type="button" id="removeImageBtn" class="text-[10px] font-bold text-rose-500 hover:underline mt-1">Remove Image</button>
                                </div>
                            </div>
                            
                            <!-- In-browser OCR Scanning Status -->
                            <div id="ocrStatusContainer" class="hidden mt-2 p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-700 dark:text-indigo-300 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span id="ocrStatusText">Scanning screenshot text via OCR...</span>
                                </div>
                                <span id="ocrProgress" class="font-bold font-mono">0%</span>
                            </div>
                        </div>

                        <!-- Target Month -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="month_year" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Period Month
                                </label>
                                <input type="month" name="month_year" id="month_year" value="{{ old('month_year', $audit?->month_year ?: now()->format('Y-m')) }}" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                            </div>
                            <div>
                                <label for="gpay_balance" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Actual GPay Balance (₹)
                                </label>
                                <input type="number" step="0.01" name="gpay_balance" id="gpay_balance" placeholder="e.g. 824.00" value="{{ old('gpay_balance', $audit?->gpay_balance) }}" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2">
                            </div>
                        </div>

                        <!-- Raw Text Paste / Extracted Preview -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="raw_text" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Extracted / Pasted Transactions
                                </label>
                                <button type="button" id="loadSampleBtn" class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">
                                    Fill Today's Screenshot Text
                                </button>
                            </div>
                            <textarea name="raw_text" id="raw_text" rows="10" placeholder="Paste or verify screenshot text here:&#10;&#10;SRI MAHALAKSHMI BAKERY 13 October ₹10&#10;COFFEE MEET VETRIVEL AGENCY 13 October ₹15&#10;deepaviyash 12 October ₹120&#10;Smart Coimbatore TAK1 6 October ₹89.30&#10;Preethi Nathan 14 October + ₹70&#10;Airtel Prepaid 17 October ₹350.90" class="w-full text-xs font-mono rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-3 leading-relaxed">{{ old('raw_text', $audit?->raw_text) }}</textarea>
                            <span class="text-[10px] text-slate-400 block mt-1">
                                Formats accepted: Line-by-line or single-line <code>Merchant Date Amount</code> (e.g. <code>+ ₹70</code> for green credits).
                            </span>
                        </div>

                        <button type="submit" class="w-full py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-2 cursor-pointer">
                            <span>🔍</span> Audit Against My Tracker
                        </button>
                    </form>
                </div>

                <!-- Past Audit History -->
                @if($history->isNotEmpty())
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Past Audit Snapshots</h3>
                        <div class="space-y-2">
                            @foreach($history as $item)
                                <a href="{{ route('finance.gpay-auditor.index', ['audit_id' => $item->id]) }}" class="p-3 rounded-xl border {{ $audit?->id === $item->id ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20' : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40' }} flex items-center justify-between text-xs transition block">
                                    <div>
                                        <span class="font-bold text-slate-900 dark:text-white">{{ $item->created_at->format('d M Y, h:i A') }}</span>
                                        <span class="block text-[10px] text-slate-400">{{ count($item->extracted_items ?? []) }} transactions parsed</span>
                                    </div>
                                    @if($item->gpay_balance)
                                        <span class="font-mono font-bold text-slate-700 dark:text-slate-300">₹{{ number_format($item->gpay_balance, 2) }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Right Panel: Questions & Analysis Results -->
            <div class="lg:col-span-7 space-y-6">
                @if($audit && !empty($audit->audit_results))
                    @php
                        $results = $audit->audit_results;
                        $summary = $results['summary'] ?? [];
                        $fakeExpenses = $results['fake_expenses'] ?? [];
                        $missingIncomes = $results['missing_incomes'] ?? [];
                        $missingExpenses = $results['missing_expenses'] ?? [];
                        $amountDiffs = $results['amount_diffs'] ?? [];
                        $matched = $results['matched'] ?? [];
                    @endphp

                    <!-- Summary KPI Cards -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900">
                            <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider block">Fake / Unbacked</span>
                            <span class="text-xl font-bold text-rose-700 dark:text-rose-300">{{ count($fakeExpenses) }}</span>
                            <span class="text-[10px] text-rose-500 block">Not on GPay</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900">
                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">Unlogged Income</span>
                            <span class="text-xl font-bold text-emerald-700 dark:text-emerald-300">{{ count($missingIncomes) }}</span>
                            <span class="text-[10px] text-emerald-500 block">Green credits</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900">
                            <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider block">Unlogged Debits</span>
                            <span class="text-xl font-bold text-amber-700 dark:text-amber-300">{{ count($missingExpenses) }}</span>
                            <span class="text-[10px] text-amber-500 block">GPay debits</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Verified Matched</span>
                            <span class="text-xl font-bold text-slate-800 dark:text-slate-200">{{ count($matched) }}</span>
                            <span class="text-[10px] text-slate-500 block">100% matched</span>
                        </div>
                    </div>

                    <!-- Action Questions Section (High Priority) -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>❓</span> Questions &amp; Suggested Fixes
                            </h2>
                            <span class="text-[11px] font-medium text-slate-400">Click any action to fix your balance instantly</span>
                        </div>

                        <!-- 1. Fake / Unbacked UPI Expenses (Like the 15 Oct Coffee ₹50!) -->
                        @foreach($fakeExpenses as $item)
                            <div class="p-5 rounded-3xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 shadow-sm space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-200 text-rose-800 dark:bg-rose-900 dark:text-rose-200 uppercase">
                                            Unbacked UPI Expense
                                        </span>
                                        <span class="text-xs text-slate-500">{{ $item['website']['date_display'] }}</span>
                                    </div>
                                    <span class="font-mono font-bold text-base text-rose-600">
                                        ₹{{ number_format($item['website']['amount'], 2) }}
                                    </span>
                                </div>
                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $item['question'] }}
                                </p>
                                <div class="text-[11px] text-slate-500">
                                    Item: <strong class="text-slate-800 dark:text-slate-200">{{ $item['website']['title'] }}</strong> &bull; Category: {{ $item['website']['category'] }}
                                </div>
                                <div class="flex items-center gap-2 pt-1">
                                    <form action="{{ route('finance.gpay-auditor.action') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="audit_id" value="{{ $audit->id }}">
                                        <input type="hidden" name="action" value="convert_to_cash">
                                        <input type="hidden" name="expense_id" value="{{ $item['website']['id'] }}">
                                        <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] shadow-sm transition cursor-pointer">
                                            ✓ Yes, Paid in Cash (Switch to Cash)
                                        </button>
                                    </form>
                                    <form action="{{ route('finance.gpay-auditor.action') }}" method="POST" onsubmit="return confirm('Delete this fake/unbacked expense?')">
                                        @csrf
                                        <input type="hidden" name="audit_id" value="{{ $audit->id }}">
                                        <input type="hidden" name="action" value="delete_expense">
                                        <input type="hidden" name="expense_id" value="{{ $item['website']['id'] }}">
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-rose-600 hover:bg-rose-50 text-[11px] font-semibold transition cursor-pointer">
                                            🗑️ Delete Expense
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach

                        <!-- 2. Missing Incomes (Green Credits on GPay) -->
                        @foreach($missingIncomes as $item)
                            <div class="p-5 rounded-3xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900 shadow-sm space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-200 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 uppercase">
                                            Green Credit on GPay
                                        </span>
                                        <span class="text-xs text-slate-500">{{ $item['gpay']['date_display'] }}</span>
                                    </div>
                                    <span class="font-mono font-bold text-base text-emerald-600">
                                        + ₹{{ number_format($item['gpay']['amount'], 2) }}
                                    </span>
                                </div>
                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $item['question'] }}
                                </p>
                                <div class="flex items-center gap-2 pt-1">
                                    <form action="{{ route('finance.gpay-auditor.action') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="audit_id" value="{{ $audit->id }}">
                                        <input type="hidden" name="action" value="add_income">
                                        <input type="hidden" name="merchant" value="{{ $item['gpay']['merchant'] }}">
                                        <input type="hidden" name="amount" value="{{ $item['gpay']['amount'] }}">
                                        <input type="hidden" name="date" value="{{ $item['gpay']['date'] }}">
                                        <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] shadow-sm transition cursor-pointer">
                                            + Yes, Log as Income
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach

                        <!-- 3. Missing GPay Debits -->
                        @foreach($missingExpenses as $item)
                            <div class="p-5 rounded-3xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 shadow-sm space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-200 text-amber-800 dark:bg-amber-900 dark:text-amber-200 uppercase">
                                            GPay Debit Missing on Website
                                        </span>
                                        <span class="text-xs text-slate-500">{{ $item['gpay']['date_display'] }}</span>
                                    </div>
                                    <span class="font-mono font-bold text-base text-amber-600">
                                        ₹{{ number_format($item['gpay']['amount'], 2) }}
                                    </span>
                                </div>
                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $item['question'] }}
                                </p>
                                <div class="flex items-center gap-2 pt-1">
                                    <form action="{{ route('finance.gpay-auditor.action') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="audit_id" value="{{ $audit->id }}">
                                        <input type="hidden" name="action" value="add_expense">
                                        <input type="hidden" name="merchant" value="{{ $item['gpay']['merchant'] }}">
                                        <input type="hidden" name="amount" value="{{ $item['gpay']['amount'] }}">
                                        <input type="hidden" name="date" value="{{ $item['gpay']['date'] }}">
                                        <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] shadow-sm transition cursor-pointer">
                                            + Yes, Add to Expenses
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach

                        <!-- 4. Amount / Decimal Differences -->
                        @foreach($amountDiffs as $item)
                            <div class="p-5 rounded-3xl bg-sky-50/70 dark:bg-sky-950/30 border border-sky-200 dark:border-sky-900 shadow-sm space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-200 text-sky-800 dark:bg-sky-900 dark:text-sky-200 uppercase">
                                        Amount Difference
                                    </span>
                                    <span class="font-mono font-bold text-sm text-sky-700">
                                        Diff: {{ $item['diff'] > 0 ? '+' : '' }}₹{{ number_format($item['diff'], 2) }}
                                    </span>
                                </div>
                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $item['question'] }}
                                </p>
                                <div class="flex items-center gap-2 pt-1">
                                    <form action="{{ route('finance.gpay-auditor.action') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="audit_id" value="{{ $audit->id }}">
                                        <input type="hidden" name="action" value="sync_amount">
                                        <input type="hidden" name="expense_id" value="{{ $item['website']['id'] }}">
                                        <input type="hidden" name="amount" value="{{ $item['gpay']['amount'] }}">
                                        <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-[11px] shadow-sm transition cursor-pointer">
                                            ✓ Sync to ₹{{ number_format($item['gpay']['amount'], 2) }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach

                        @if(empty($fakeExpenses) && empty($missingIncomes) && empty($missingExpenses) && empty($amountDiffs))
                            <div class="text-center py-8 rounded-3xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 p-6">
                                <span class="text-4xl block mb-2">🎉</span>
                                <h3 class="text-sm font-bold text-emerald-800 dark:text-emerald-300">All Transactions Match 100%!</h3>
                                <p class="text-xs text-slate-500 mt-1">There are no missing, fake, or mismatched UPI expenses between your GPay screenshot and My Tracker.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Confirmed Matched Transactions Table -->
                    @if(!empty($matched))
                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center justify-between">
                                <span>Verified Matches ({{ count($matched) }})</span>
                                <span class="text-[10px] text-emerald-600 font-semibold">✓ Verified with GPay</span>
                            </h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="text-slate-400 uppercase font-semibold border-b border-slate-100 dark:border-slate-800 text-[10px]">
                                        <tr>
                                            <th class="py-2 px-3">Date</th>
                                            <th class="py-2 px-3">GPay Merchant</th>
                                            <th class="py-2 px-3">Website Item</th>
                                            <th class="py-2 px-3 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                                        @foreach($matched as $m)
                                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                                <td class="py-2 px-3 font-mono text-slate-500">{{ $m['gpay']['date_display'] }}</td>
                                                <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">{{ $m['gpay']['merchant'] }}</td>
                                                <td class="py-2 px-3 text-slate-600 dark:text-slate-400">{{ $m['website']['title'] }}</td>
                                                <td class="py-2 px-3 text-right font-mono font-bold {{ $m['type'] === 'income' ? 'text-emerald-600' : 'text-slate-900 dark:text-white' }}">
                                                    {{ $m['type'] === 'income' ? '+' : '' }}₹{{ number_format($m['gpay']['amount'], 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @else
                    <!-- Empty State / Welcome Guide -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-8 text-center shadow-sm space-y-4">
                        <span class="text-5xl block">📱</span>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Audit Google Pay Against My Tracker</h2>
                        <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                            Upload a screenshot from your Google Pay transaction history, or paste the text on the left. The auditor will compare every entry, detect cash expenses mistakenly logged as UPI, find missing incomes, and ask you questions with 1-click fixes!
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-left pt-4">
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60">
                                <span class="font-bold text-xs text-rose-600 block mb-1">1. Detect Fake UPI</span>
                                <span class="text-[11px] text-slate-500 block">Spots expenses logged as UPI that never actually left your bank.</span>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60">
                                <span class="font-bold text-xs text-emerald-600 block mb-1">2. Green Credits</span>
                                <span class="text-[11px] text-slate-500 block">Identifies rewards, refunds, or friend repayments to add as Income.</span>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60">
                                <span class="font-bold text-xs text-indigo-600 block mb-1">3. 1-Click Sync</span>
                                <span class="text-[11px] text-slate-500 block">Fixes paise discrepancies and switches payment methods instantly.</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Client-side Tesseract.js OCR integration -->
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('screenshotInput');
            const dropPrompt = document.getElementById('dropPrompt');
            const previewContainer = document.getElementById('previewContainer');
            const imagePreview = document.getElementById('imagePreview');
            const fileName = document.getElementById('fileName');
            const removeBtn = document.getElementById('removeImageBtn');
            const rawText = document.getElementById('raw_text');
            const statusContainer = document.getElementById('ocrStatusContainer');
            const statusText = document.getElementById('ocrStatusText');
            const progressText = document.getElementById('ocrProgress');
            const loadSampleBtn = document.getElementById('loadSampleBtn');

            // Sample text helper
            if (loadSampleBtn) {
                loadSampleBtn.addEventListener('click', () => {
                    rawText.value = `SRI MAHALAKSHMI BAKERY 13 October ₹10\nCOFFEE MEET VETRIVEL AGENCY 13 October ₹15\ndeepaviyash 12 October ₹120\nBOOMINATHAN R 12 October ₹20\nSmart Coimbatore TAK1 6 October ₹89.30\nRUCKMANI ARUMUGAM 5 October ₹10\nSNEHA HOSPITAL 2 October ₹400\nPURUSHOTHAMAN 2 October + ₹1,000\nGoogle Pay rewards 17 October + ₹7\nAirtel Prepaid 17 October ₹350.90\nSPOTIFY INDIA PVT LTD 15 October ₹69\nCOFFEE MEET VETRIVEL AGENCY 14 October ₹60\nPreethi Nathan 14 October + ₹70\nFriends Xerox 14 October ₹235\nPURUSHOTHAMAN 14 October + ₹500`;
                });
            }

            dropZone.addEventListener('click', (e) => {
                if (e.target !== removeBtn) {
                    fileInput.click();
                }
            });

            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('border-emerald-500', 'bg-emerald-50/20');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('border-emerald-500', 'bg-emerald-50/20');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-emerald-500', 'bg-emerald-50/20');
                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    fileInput.files = e.dataTransfer.files;
                    handleFileSelect(e.dataTransfer.files[0]);
                }
            });

            fileInput.addEventListener('change', () => {
                if (fileInput.files && fileInput.files[0]) {
                    handleFileSelect(fileInput.files[0]);
                }
            });

            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.value = '';
                previewContainer.classList.add('hidden');
                dropPrompt.classList.remove('hidden');
                statusContainer.classList.add('hidden');
            });

            function handleFileSelect(file) {
                if (!file.type.startsWith('image/')) return;

                fileName.textContent = file.name;
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    dropPrompt.classList.add('hidden');
                    previewContainer.classList.remove('hidden');
                    runClientOCR(file);
                };
                reader.readAsDataURL(file);
            }

            async function runClientOCR(file) {
                if (typeof Tesseract === 'undefined') return;

                statusContainer.classList.remove('hidden');
                statusText.textContent = 'Initializing OCR engine...';
                progressText.textContent = '0%';

                try {
                    const result = await Tesseract.recognize(file, 'eng', {
                        logger: (m) => {
                            if (m.status === 'recognizing text') {
                                const pct = Math.round(m.progress * 100);
                                statusText.textContent = `Scanning screenshot text (${pct}%)...`;
                                progressText.textContent = `${pct}%`;
                            }
                        }
                    });

                    statusText.textContent = 'Text extracted successfully!';
                    progressText.textContent = '100%';
                    setTimeout(() => statusContainer.classList.add('hidden'), 2500);

                    if (result && result.data && result.data.text) {
                        const existing = rawText.value.trim();
                        rawText.value = existing ? existing + '\n\n' + result.data.text.trim() : result.data.text.trim();
                    }
                } catch (err) {
                    console.error('OCR Error:', err);
                    statusText.textContent = 'OCR scan completed. You can verify text below.';
                    setTimeout(() => statusContainer.classList.add('hidden'), 2000);
                }
            }
        });
    </script>
</x-app-layout>

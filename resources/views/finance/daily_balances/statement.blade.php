<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daily Cash Register {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; font-size: 12px; color: #0f172a; background: #f1f5f9; }
        .action-bar { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 24px; background: #fff; border-bottom: 1px solid #e2e8f0; }
        .action-bar strong { font-size: 14px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 10px; border: 0; font-weight: 700; font-size: 12px; cursor: pointer; text-decoration: none; }
        .btn-back { background: #f1f5f9; color: #334155; }
        .btn-print { background: #0f172a; color: #fff; }
        .page { max-width: 1080px; margin: 24px auto; padding: 32px; background: #fff; border-radius: 16px; box-shadow: 0 2px 10px rgba(15, 23, 42, .06); }
        header.doc { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; padding-bottom: 14px; border-bottom: 2px solid #0f172a; margin-bottom: 20px; }
        h1 { font-size: 20px; letter-spacing: -.02em; }
        h2 { font-size: 14px; margin: 26px 0 8px; }
        h3 { font-size: 13px; margin: 18px 0 6px; }
        .muted { color: #64748b; }
        .small { font-size: 10.5px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px 7px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #475569; }
        td.num, th.num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        tfoot td { font-weight: 800; border-top: 2px solid #0f172a; background: #f8fafc; }
        .in { color: #047857; }
        .out { color: #be123c; }
        .adj { color: #b45309; }
        .tag { display: inline-block; padding: 1px 6px; border-radius: 999px; font-size: 9.5px; font-weight: 700; background: #ede9fe; color: #6d28d9; }
        .tag-closed { background: #e2e8f0; color: #0f172a; }
        .tag-done { background: #d1fae5; color: #065f46; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; margin: 8px 0 4px; }
        .summary div { border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 10px; }
        .summary span { display: block; font-size: 9.5px; text-transform: uppercase; color: #64748b; font-weight: 700; }
        .summary b { font-size: 14px; }
        .tally-lines td { border-bottom: 0; padding-top: 2px; padding-bottom: 2px; font-size: 11px; color: #334155; }
        .tally-lines.last td { border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; }
        .paid-from { line-height: 1.5; }
        tr.has-tallies td { border-bottom: 0; }
        .empty { padding: 10px; color: #94a3b8; }
        @media print {
            body { background: #fff; font-size: 10.5px; }
            .no-print { display: none !important; }
            .page { margin: 0; padding: 0; box-shadow: none; border-radius: 0; max-width: none; }
            tr { break-inside: avoid; }
            section.type-ledger { break-inside: auto; }
        }
        @media (max-width: 640px) {
            .page { margin: 0; padding: 16px; border-radius: 0; }
            .table-wrap { overflow-x: auto; }
        }
    </style>
</head>
<body>
    @php
        $money = fn (float $value): string => '₹'.number_format($value, 2);
        $signed = fn (float $value): string => ($value > 0 ? '+' : ($value < 0 ? '−' : '')).'₹'.number_format(abs($value), 2);
    @endphp

    <div class="action-bar no-print">
        <strong>🧾 Daily Cash Register statement</strong>
        <div style="display:flex;gap:8px">
            <a href="{{ route('daily-balances.index') }}" class="btn btn-back">← Register</a>
            <button type="button" onclick="window.print()" class="btn btn-print">🖨️ Print / Save as PDF</button>
        </div>
    </div>

    <div class="page">
        <header class="doc">
            <div>
                <h1>Daily Cash Register Statement</h1>
                <p class="muted">{{ $from->format('d M Y') }} to {{ $to->format('d M Y') }} · {{ implode(', ', $types) }}</p>
            </div>
            <p class="muted small">Generated {{ now()->format('d M Y, H:i') }} · not stored</p>
        </header>

        {{-- 1. Each type with its own before / after balance (never combined) --}}
        @foreach($types as $type)
            @php
                $typeDays = collect($days)->map(fn (array $day) => $day['categories'][$type] ?? null)->filter();
                $firstDay = $typeDays->first();
                $lastDay = $typeDays->last();
                $sumIn = (float) $typeDays->sum('inflow');
                $sumOut = (float) $typeDays->sum('outflow');
                $sumAdj = (float) $typeDays->sum('adjustment');
                $sumSeparate = (float) $typeDays->sum('separate_inflow');
            @endphp
            <section class="type-ledger">
                <h2>{{ $type }} — balance before &amp; after</h2>
                <div class="summary">
                    <div><span>Before ({{ $from->format('d M') }})</span><b>{{ $money((float) ($firstDay['opening'] ?? 0)) }}</b></div>
                    <div><span>Income</span><b class="in">+{{ $money($sumIn) }}</b></div>
                    <div><span>Expense</span><b class="out">−{{ $money($sumOut) }}</b></div>
                    <div><span>Adjustments</span><b class="adj">{{ $signed($sumAdj) }}</b></div>
                    <div><span>After ({{ $to->format('d M') }})</span><b>{{ $money((float) ($lastDay['closing'] ?? 0)) }}</b></div>
                </div>
                @if($sumSeparate > 0)
                    <p class="small muted">Includes {{ $money($sumSeparate) }} separate income, shown on its own day and not carried to the next day.</p>
                @endif
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="num">Before</th>
                                <th class="num">Income (+)</th>
                                <th class="num">Expense (−)</th>
                                <th class="num">Adjustment</th>
                                <th class="num">After</th>
                                <th>Adjustment note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($days as $dateStr => $day)
                                @php $cat = $day['categories'][$type] ?? null; @endphp
                                @continue(! $cat)
                                <tr>
                                    <td style="white-space:nowrap">{{ $day['date']->format('d M Y, D') }}</td>
                                    <td class="num">{{ $money((float) $cat['opening']) }}</td>
                                    <td class="num in">
                                        {{ $cat['inflow'] > 0 ? $money((float) $cat['inflow']) : '—' }}
                                        @if(($cat['separate_inflow'] ?? 0) > 0)
                                            <div class="small">incl. {{ $money((float) $cat['separate_inflow']) }} separate</div>
                                        @endif
                                    </td>
                                    <td class="num out">{{ $cat['outflow'] > 0 ? $money((float) $cat['outflow']) : '—' }}</td>
                                    <td class="num adj">{{ (float) $cat['adjustment'] !== 0.0 ? $signed((float) $cat['adjustment']) : '—' }}</td>
                                    <td class="num">
                                        <b>{{ $money((float) $cat['closing']) }}</b>
                                        @if(abs((float) ($cat['carry_forward'] ?? $cat['closing']) - (float) $cat['closing']) >= 0.01)
                                            <div class="small muted">carried {{ $money((float) $cat['carry_forward']) }}</div>
                                        @endif
                                    </td>
                                    <td class="small">{{ $cat['adjustment_note'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total</td>
                                <td class="num">{{ $money((float) ($firstDay['opening'] ?? 0)) }}</td>
                                <td class="num in">{{ $money($sumIn) }}</td>
                                <td class="num out">{{ $money($sumOut) }}</td>
                                <td class="num adj">{{ $signed($sumAdj) }}</td>
                                <td class="num">{{ $money((float) ($lastDay['closing'] ?? 0)) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        @endforeach

        {{-- 2. Income tally: which income covered which expense --}}
        @php
            $typeIncomes = $incomes->filter(fn ($income) => in_array($income->payment_method ?: 'Cash', $types, true));
        @endphp
        <h2>Income &amp; tally — which income covered which expense</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Income</th>
                        <th>Type</th>
                        <th class="num">Amount</th>
                        <th class="num">Tallied</th>
                        <th class="num">Not tallied</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($typeIncomes as $income)
                        @php $tallied = (float) $income->tallies->sum('allocated_amount'); @endphp
                        <tr @class(['has-tallies' => $income->tallies->isNotEmpty()])>
                            <td style="white-space:nowrap">{{ $income->date->format('d M Y') }}</td>
                            <td>
                                <b>{{ $income->source ?: 'Income' }}</b>
                                @if($income->description)<span class="muted"> · {{ $income->description }}</span>@endif
                                @if($income->keep_separate) <span class="tag">Separate</span>@endif
                            </td>
                            <td>{{ $income->payment_method ?: 'Cash' }}</td>
                            <td class="num in">{{ $money((float) $income->amount) }}</td>
                            <td class="num">{{ $money($tallied) }}</td>
                            <td class="num">{{ $money(max(0, (float) $income->amount - $tallied)) }}</td>
                        </tr>
                        @foreach($income->tallies as $tally)
                            <tr @class(['tally-lines', 'last' => $loop->last])>
                                <td></td>
                                <td colspan="3">↳ {{ $tally->expense?->date?->format('d M Y') }} · {{ $tally->expense?->description ?? 'Deleted expense' }}@if($tally->notes) <span class="muted">({{ $tally->notes }})</span>@endif</td>
                                <td class="num out">{{ $money((float) $tally->allocated_amount) }}</td>
                                <td></td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="6" class="empty">No income in this range for {{ implode(', ', $types) }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 3. Expenses with the income that paid for them --}}
        @php
            $typeExpenses = $expenses->filter(fn ($expense) => array_intersect(array_keys($expense->paymentBreakdownAmounts()), $types) !== []);
        @endphp
        <h2>Expenses &amp; the income they were tallied to</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Expense</th>
                        <th>Type</th>
                        <th class="num">Amount</th>
                        <th>Paid from income</th>
                        <th class="num">Income left</th>
                        <th class="num">Expense remaining</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($typeExpenses as $expense)
                        @php
                            $breakdown = collect($expense->paymentBreakdownAmounts())->only($types);
                            $tallied = (float) $expense->incomeTallies->sum('allocated_amount');
                            $remaining = round($expense->untalliedAmount(), 2);
                        @endphp
                        <tr>
                            <td style="white-space:nowrap">{{ $expense->date->format('d M Y') }}</td>
                            <td>
                                {{ $expense->description }}
                                <span class="muted small">· {{ $expense->category?->name ?? 'General' }}</span>
                                @if($expense->is_closed) <span class="tag tag-closed">Closed</span>@endif
                            </td>
                            <td>{{ $breakdown->map(fn ($amount, $method) => $method.' '.$money((float) $amount))->implode(', ') }}</td>
                            <td class="num out">{{ $money((float) $breakdown->sum()) }}</td>
                            <td class="small">
                                @forelse($expense->incomeTallies as $tally)
                                    <div class="paid-from">
                                        <b>{{ $tally->income?->source ?? 'Income' }}</b>
                                        <span class="muted">· {{ $tally->income?->date?->format('d M') }}@if($tally->income) · of {{ $money((float) $tally->income->amount) }}@endif</span>
                                        @if($expense->incomeTallies->count() > 1 || $remaining > 0)
                                            <span class="tag">{{ $money((float) $tally->allocated_amount) }} tallied</span>
                                        @endif
                                    </div>
                                @empty
                                    <span class="muted">—</span>
                                @endforelse
                            </td>
                            <td class="num">
                                @forelse($expense->incomeTallies as $tally)
                                    <div class="paid-from">{{ $money($tally->income ? max(0, (float) $tally->income->amount - (float) $tally->income->tallies->sum('allocated_amount')) : 0) }}</div>
                                @empty
                                    <span class="muted">—</span>
                                @endforelse
                            </td>
                            <td class="num">
                                @if($remaining <= 0)
                                    <span class="tag tag-done">✓ Nil</span>
                                @else
                                    <b class="adj">{{ $money($remaining) }}</b>
                                    @if($tallied > 0)
                                        <div class="small muted">{{ $money($tallied) }} tallied</div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No expenses in this range for {{ implode(', ', $types) }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 4. Adjustment notes --}}
        <h2>Adjustments &amp; notes</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th class="num">Adjustment</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adjustment)
                        <tr>
                            <td style="white-space:nowrap">{{ $adjustment['date']->format('d M Y') }}</td>
                            <td>{{ $adjustment['type'] }}</td>
                            <td class="num adj">{{ $signed($adjustment['amount']) }}</td>
                            <td>{{ $adjustment['note'] ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No adjustments in this range.</td></tr>
                    @endforelse
                    @foreach($dayNotes as $dayNote)
                        <tr>
                            <td style="white-space:nowrap">{{ $dayNote['date']->format('d M Y') }}</td>
                            <td>Day note</td>
                            <td class="num">—</td>
                            <td>{{ $dayNote['note'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 600));
    </script>
</body>
</html>

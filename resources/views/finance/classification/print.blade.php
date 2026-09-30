<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Classification Report Â· {{ now()->format('d M Y') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #6d28d9;
            --primary-light: #ede9fe;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-700: #334155;
            --slate-900: #0f172a;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 13px;
            color: var(--slate-900);
            background: #f0f4ff;
            min-height: 100vh;
        }

        @media print {
            body { background: #fff; font-size: 11px; }
            .no-print { display: none !important; }
            .page { box-shadow: none !important; border-radius: 0 !important; margin: 0 !important; padding: 24px !important; }
            .section-card { break-inside: avoid; }
            tr { break-inside: avoid; }
        }

        /* â”€â”€ Floating action bar â”€â”€ */
        .action-bar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 32px;
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px;
        }
        .action-bar .logo { font-weight: 800; font-size: 15px; color: var(--primary); letter-spacing: -0.5px; }
        .action-bar .logo span { color: var(--slate-400); font-weight: 400; font-size: 12px; margin-left: 6px; }
        .btn-group { display: flex; gap: 8px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 10px; font-weight: 700; font-size: 12px; cursor: pointer; border: none; text-decoration: none; transition: all 0.15s; }
        .btn:active { transform: scale(0.97); }
        .btn-back { background: var(--slate-100); color: var(--slate-700); }
        .btn-back:hover { background: var(--slate-200); }
        .btn-print { background: linear-gradient(135deg, #7c3aed, #4f46e5); color: white; box-shadow: 0 4px 14px rgba(109,40,217,0.35); }
        .btn-print:hover { box-shadow: 0 6px 20px rgba(109,40,217,0.45); }

        /* â”€â”€ Main page wrapper â”€â”€ */
        .page {
            max-width: 960px;
            margin: 80px auto 40px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.10), 0 4px 16px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        /* â”€â”€ Gradient Header â”€â”€ */
        .report-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%);
            padding: 40px 40px 32px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .report-header::before {
            content: '';
            position: absolute; top: -60px; right: -60px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.07);
            border-radius: 50%;
        }
        .report-header::after {
            content: '';
            position: absolute; bottom: -80px; left: 30%;
            width: 280px; height: 280px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .report-header h1 { font-size: 26px; font-weight: 900; letter-spacing: -0.5px; }
        .report-header .subtitle { font-size: 13px; opacity: 0.75; margin-top: 4px; }
        .header-meta {
            display: flex; flex-wrap: wrap; gap: 24px; margin-top: 24px;
        }
        .meta-chip {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            padding: 8px 14px;
            backdrop-filter: blur(4px);
        }
        .meta-chip .label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.7; }
        .meta-chip .value { font-size: 14px; font-weight: 800; margin-top: 1px; }

        /* â”€â”€ Content area â”€â”€ */
        .content { padding: 32px 40px; }

        /* â”€â”€ Summary grid â”€â”€ */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 32px;
        }
        .summary-card {
            border-radius: 14px;
            padding: 16px;
            border: 1.5px solid;
            position: relative;
            overflow: hidden;
        }
        .summary-card::after {
            content: '';
            position: absolute; bottom: -16px; right: -16px;
            width: 60px; height: 60px;
            border-radius: 50%;
            opacity: 0.1;
            background: currentColor;
        }
        .summary-card .cls-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.7; }
        .summary-card .cls-amount { font-size: 20px; font-weight: 900; margin: 4px 0 2px; }
        .summary-card .cls-count { font-size: 11px; opacity: 0.6; font-weight: 500; }

        /* â”€â”€ Section cards â”€â”€ */
        .section-card {
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .section-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 20px;
            background: white;
            border-bottom: 1px solid var(--slate-200);
        }
        .section-title { font-size: 13px; font-weight: 800; color: var(--slate-900); display: flex; align-items: center; gap: 8px; }
        .section-badge { font-size: 10px; font-weight: 600; color: var(--slate-400); background: var(--slate-100); padding: 2px 8px; border-radius: 999px; }

        /* â”€â”€ Tables â”€â”€ */
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead tr { background: var(--slate-100); }
        th { padding: 10px 14px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--slate-500); white-space: nowrap; }
        td { padding: 10px 14px; border-bottom: 1px solid var(--slate-100); vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:nth-child(even) { background: rgba(248,250,252,0.8); }
        tbody tr:hover { background: #f0f4ff; }

        .td-right { text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; }
        .td-center { text-align: center; color: var(--slate-500); }

        /* â”€â”€ Classification pill â”€â”€ */
        .cls-pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 700; color: white;
            white-space: nowrap;
        }
        .cls-dot { width: 6px; height: 6px; border-radius: 50%; background: rgba(255,255,255,0.7); flex-shrink: 0; }

        /* â”€â”€ Type badge â”€â”€ */
        .type-badge { display: inline-block; padding: 2px 7px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .type-normal { background: #dbeafe; color: #1d4ed8; }
        .type-personal { background: #ede9fe; color: #7c3aed; }

        /* â”€â”€ Total row â”€â”€ */
        .total-row td { font-weight: 800; background: var(--slate-900) !important; color: white; }
        .total-row td:first-child { border-radius: 0 0 0 12px; }
        .total-row td:last-child { border-radius: 0 0 12px 0; }

        /* â”€â”€ Divider â”€â”€ */
        .divider { border: none; border-top: 2px solid var(--slate-100); margin: 8px 0; }

        /* â”€â”€ Footer â”€â”€ */
        .report-footer {
            margin-top: 32px; padding-top: 16px;
            border-top: 1px solid var(--slate-200);
            color: var(--slate-400);
            font-size: 10px;
            text-align: center;
            line-height: 1.8;
        }

        /* â”€â”€ Empty state â”€â”€ */
        .empty-state { text-align: center; padding: 32px 20px; color: var(--slate-400); font-size: 13px; }
    </style>
</head>
<body>

    {{-- â”€â”€ Floating Action Bar â”€â”€ --}}
    <div class="action-bar no-print">
        <div class="logo">
            ðŸ·ï¸ Classification Report
            <span>{{ now()->format('d M Y Â· H:i') }}</span>
        </div>
        <div class="btn-group">
            <a href="javascript:history.back()" class="btn btn-back">â† Back</a>
            <button onclick="window.print()" class="btn btn-print">ðŸ–¨ï¸ Print / Save PDF</button>
        </div>
    </div>

    <div class="page">

        {{-- â”€â”€ Report Header â”€â”€ --}}
        <div class="report-header">
            <h1>ðŸ·ï¸ Expense Classification Report</h1>
            <p class="subtitle">Generated by {{ config('app.name', 'LifeTracker') }} Â· {{ now()->format('D, d M Y Â· H:i') }}</p>
            <div class="header-meta">
                <div class="meta-chip">
                    <div class="label">Period</div>
                    <div class="value">{{ $periodLabel }}</div>
                </div>
                <div class="meta-chip">
                    <div class="label">Scope</div>
                    <div class="value">{{ ucfirst($domain) }} Expenses</div>
                </div>
                <div class="meta-chip">
                    <div class="label">Total Items</div>
                    <div class="value">{{ $allItems->count() }}</div>
                </div>
                <div class="meta-chip">
                    <div class="label">Total Spend</div>
                    <div class="value">â‚¹{{ number_format($totalAmount, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="content">

            @php $classificationColors = $classifications->pluck('color', 'name')->toArray(); @endphp

            {{-- â”€â”€ Summary Cards â”€â”€ --}}
            <div class="summary-grid">
                @foreach($byClassification->sortKeys() as $clsName => $data)
                    @php
                        $clsColor = $classificationColors[$clsName] ?? ($clsName === 'Unclassified' ? '#94a3b8' : '#6b7280');
                        $pct = $totalAmount > 0 ? round(($data['amount'] / $totalAmount) * 100, 1) : 0;
                        // Derive light bg from color
                    @endphp
                    <div class="summary-card" style="border-color: {{ $clsColor }}30; background: {{ $clsColor }}08; color: {{ $clsColor }};">
                        <div class="cls-label">{{ $clsName }}</div>
                        <div class="cls-amount">â‚¹{{ number_format($data['amount'], 2) }}</div>
                        <div class="cls-count">{{ $data['count'] }} item{{ $data['count'] !== 1 ? 's' : '' }} Â· {{ $pct }}%</div>
                        <div style="margin-top:8px; height:4px; background: {{ $clsColor }}20; border-radius:2px; overflow:hidden;">
                            <div style="height:100%; width:{{ $pct }}%; background: {{ $clsColor }}; border-radius:2px;"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
            {{-- LEVEL 1: By Classification --}}
            {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">ðŸ“Š By Classification <span class="section-badge">Level 1</span></div>
                    <div style="font-size:11px; color:var(--slate-400);">{{ $allItems->count() }} total Â· â‚¹{{ number_format($totalAmount,2) }}</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Classification</th>
                            <th class="td-center">Items</th>
                            <th class="td-right">Amount</th>
                            <th class="td-right">% of Total</th>
                            <th>Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byClassification->sortKeys() as $clsName => $data)
                            @php $clsColor = $classificationColors[$clsName] ?? ($clsName === 'Unclassified' ? '#94a3b8' : '#6b7280'); $pct = $totalAmount > 0 ? round(($data['amount'] / $totalAmount)*100,1) : 0; @endphp
                            <tr>
                                <td>
                                    <span class="cls-pill" style="background:{{ $clsColor }}">
                                        <span class="cls-dot"></span>{{ $clsName }}
                                    </span>
                                </td>
                                <td class="td-center">{{ $data['count'] }}</td>
                                <td class="td-right">â‚¹{{ number_format($data['amount'],2) }}</td>
                                <td class="td-right" style="color:{{ $clsColor }}; font-weight:700;">{{ $pct }}%</td>
                                <td style="width:120px;">
                                    <div style="height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden;">
                                        <div style="height:100%; width:{{ $pct }}%; background:{{ $clsColor }}; border-radius:3px;"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td>Grand Total</td>
                            <td class="td-center">{{ $allItems->count() }}</td>
                            <td class="td-right">â‚¹{{ number_format($totalAmount,2) }}</td>
                            <td class="td-right">100%</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
            {{-- LEVEL 2: By Classification + Category --}}
            {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">ðŸ“‚ By Classification + Category <span class="section-badge">Level 2</span></div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Classification</th>
                            <th>Category</th>
                            <th class="td-center">Items</th>
                            <th class="td-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $lastCls2 = null; @endphp
                        @foreach($byClassificationCategory->sortBy(fn($r) => $r['classification'].'-'.$r['category']) as $row)
                            @php $clsColor = $classificationColors[$row['classification']] ?? ($row['classification'] === 'Unclassified' ? '#94a3b8' : '#6b7280'); @endphp
                            <tr>
                                <td>
                                    @if($lastCls2 !== $row['classification'])
                                        <span class="cls-pill" style="background:{{ $clsColor }}">
                                            <span class="cls-dot"></span>{{ $row['classification'] }}
                                        </span>
                                        @php $lastCls2 = $row['classification']; @endphp
                                    @endif
                                </td>
                                <td style="font-weight:600; color:var(--slate-700);">{{ $row['category'] }}</td>
                                <td class="td-center">{{ $row['count'] }}</td>
                                <td class="td-right">â‚¹{{ number_format($row['amount'],2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
            {{-- LEVEL 3: Classification + Category + Payment --}}
            {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">ðŸ’³ By Classification + Category + Payment <span class="section-badge">Level 3</span></div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Classification</th>
                            <th>Category</th>
                            <th>Payment Method</th>
                            <th class="td-center">Items</th>
                            <th class="td-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $lastCls3 = null; $lastCat3 = null; @endphp
                        @foreach($byClassificationCategoryPayment->sortBy(fn($r) => $r['classification'].'-'.$r['category'].'-'.$r['payment_method']) as $row)
                            @php $clsColor = $classificationColors[$row['classification']] ?? ($row['classification'] === 'Unclassified' ? '#94a3b8' : '#6b7280'); @endphp
                            <tr>
                                <td>
                                    @if($lastCls3 !== $row['classification'])
                                        <span class="cls-pill" style="background:{{ $clsColor }}">
                                            <span class="cls-dot"></span>{{ $row['classification'] }}
                                        </span>
                                        @php $lastCls3 = $row['classification']; @endphp
                                    @endif
                                </td>
                                <td style="color:var(--slate-700);">
                                    @if($lastCat3 !== $row['classification'].'-'.$row['category'])
                                        <span style="font-weight:600;">{{ $row['category'] }}</span>
                                        @php $lastCat3 = $row['classification'].'-'.$row['category']; @endphp
                                    @endif
                                </td>
                                <td>
                                    <span style="display:inline-flex; align-items:center; gap:5px; background:#f1f5f9; color:#334155; border-radius:6px; padding:2px 8px; font-size:11px; font-weight:700;">
                                        ðŸ’³ {{ $row['payment_method'] }}
                                    </span>
                                </td>
                                <td class="td-center">{{ $row['count'] }}</td>
                                <td class="td-right">â‚¹{{ number_format($row['amount'],2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
            {{-- ITEMIZED LIST --}}
            {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">ðŸ“‹ Itemized Expense List <span class="section-badge">{{ $allItems->count() }} items</span></div>
                    <div style="font-size:11px; color:var(--slate-400);">Grand Total: <strong style="color:var(--slate-900);">â‚¹{{ number_format($totalAmount,2) }}</strong></div>
                </div>
                @if($allItems->isEmpty())
                    <div class="empty-state">No expenses found for this period and scope.</div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Payment</th>
                                <th>Type</th>
                                <th>Classification</th>
                                <th class="td-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allItems as $i => $item)
                                @php $clsColor = $classificationColors[$item['classification']] ?? ($item['classification'] === 'Unclassified' ? '#94a3b8' : '#6b7280'); @endphp
                                <tr>
                                    <td style="color:var(--slate-400); font-size:11px; width:32px;">{{ $i+1 }}</td>
                                    <td style="white-space:nowrap; font-weight:600; color:var(--slate-700);">{{ \Carbon\Carbon::parse($item['date'])->format('d M Y') }}</td>
                                    <td style="max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $item['description'] }}">{{ $item['description'] ?: 'â€”' }}</td>
                                    <td style="color:var(--slate-600);">{{ $item['category'] }}</td>
                                    <td style="font-weight:600; color:var(--slate-600);">{{ $item['payment_method'] }}</td>
                                    <td>
                                        <span class="type-badge {{ $item['type'] === 'normal' ? 'type-normal' : 'type-personal' }}">
                                            {{ $item['type'] === 'normal' ? 'Normal' : 'Personal' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="cls-pill" style="background:{{ $clsColor }}; font-size:10px; padding:2px 8px;">
                                            <span class="cls-dot"></span>{{ $item['classification'] }}
                                        </span>
                                    </td>
                                    <td class="td-right" style="color:{{ $item['classification'] === 'Unclassified' ? 'var(--slate-500)' : 'var(--slate-900)' }}">
                                        â‚¹{{ number_format($item['amount'],2) }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td colspan="7">Grand Total â€” {{ $allItems->count() }} Expenses</td>
                                <td class="td-right">â‚¹{{ number_format($totalAmount,2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Footer --}}
            <div class="report-footer">
                <div>Generated by <strong>{{ config('app.name', 'LifeTracker') }}</strong> Â· {{ now()->format('d M Y, H:i') }}</div>
                <div>This report was generated on-the-fly and is <strong>not stored in the database</strong>. Zero extra storage used.</div>
            </div>

        </div>{{-- /content --}}
    </div>{{-- /page --}}

    <script>
        window.onload = () => setTimeout(() => window.print(), 700);
    </script>
</body>
</html>

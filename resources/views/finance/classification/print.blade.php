<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expense Classification Report</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #1e293b;
            background: #ffffff;
            padding: 32px;
            line-height: 1.5;
        }

        /* Print Styles */
        @media print {
            body { padding: 20px; font-size: 11px; }
            .no-print { display: none !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
            .section { page-break-inside: avoid; }
        }

        /* Layout */
        .header { margin-bottom: 28px; border-bottom: 3px solid #0ea5e9; padding-bottom: 16px; }
        .header h1 { font-size: 22px; font-weight: 800; color: #0f172a; }
        .header p { color: #64748b; font-size: 12px; margin-top: 4px; }
        .meta { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 12px; }
        .meta-item { }
        .meta-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
        .meta-value { font-size: 13px; font-weight: 600; color: #1e293b; }

        /* Summary Cards */
        .summary-cards { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 28px; }
        .summary-card { flex: 1; min-width: 120px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; }
        .summary-card .amount { font-size: 18px; font-weight: 800; }
        .summary-card .label { font-size: 10px; color: #64748b; font-weight: 600; text-transform: uppercase; margin-top: 2px; }
        .summary-card .count { font-size: 11px; color: #94a3b8; }

        /* Section */
        .section { margin-bottom: 32px; }
        .section-title {
            font-size: 14px; font-weight: 700; color: #0f172a;
            margin-bottom: 10px; padding-bottom: 6px;
            border-bottom: 2px solid #e2e8f0;
            display: flex; align-items: center; gap-8px;
        }
        .section-title span { font-size: 11px; color: #94a3b8; font-weight: 500; margin-left: 8px; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead tr { background: #f1f5f9; }
        th { padding: 8px 10px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; }
        td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }

        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 999px;
            font-size: 10px; font-weight: 700; text-transform: uppercase;
        }
        .badge-normal { background: #dbeafe; color: #1d4ed8; }
        .badge-personal { background: #ede9fe; color: #7c3aed; }
        .badge-unclassified { background: #f1f5f9; color: #64748b; }

        .amount-col { text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; }
        .count-col { text-align: center; color: #64748b; }

        .cls-pill {
            display: inline-block; padding: 2px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 700; color: white;
        }

        .total-row td { font-weight: 700; border-top: 2px solid #e2e8f0; background: #f8fafc; }
        .grand-total { font-size: 14px; font-weight: 800; color: #0f172a; }

        .no-print-btn {
            position: fixed; top: 20px; right: 20px;
            display: flex; gap: 10px;
        }
        .btn {
            padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 13px;
            cursor: pointer; border: none; text-decoration: none;
        }
        .btn-print { background: #0ea5e9; color: white; }
        .btn-close { background: #f1f5f9; color: #334155; }
    </style>
</head>
<body>

    {{-- Print / Close Buttons (no-print) --}}
    <div class="no-print no-print-btn">
        <a href="javascript:history.back()" class="btn btn-close">← Back</a>
        <button onclick="window.print()" class="btn btn-print">🖨️ Print / Save PDF</button>
    </div>

    {{-- Report Header --}}
    <div class="header">
        <h1>🏷️ Expense Classification Report</h1>
        <p>Generated on {{ now()->format('D, d M Y • H:i') }} &nbsp;|&nbsp; {{ config('app.name', 'LifeTracker') }}</p>
        <div class="meta">
            <div class="meta-item">
                <div class="meta-label">Period</div>
                <div class="meta-value">{{ $periodLabel }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Scope</div>
                <div class="meta-value">{{ ucfirst($domain) }} Expenses</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Total Items</div>
                <div class="meta-value">{{ $allItems->count() }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Total Spend</div>
                <div class="meta-value">₹{{ number_format($totalAmount, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- Summary Cards: By Classification --}}
    @php
        $classificationColors = $classifications->pluck('color', 'name')->toArray();
    @endphp
    <div class="summary-cards">
        @foreach($byClassification->sortKeys() as $clsName => $data)
            @php $clsColor = $classificationColors[$clsName] ?? ($clsName === 'Unclassified' ? '#94a3b8' : '#6b7280'); @endphp
            <div class="summary-card" style="border-left: 4px solid {{ $clsColor }};">
                <div class="amount" style="color: {{ $clsColor }};">₹{{ number_format($data['amount'], 2) }}</div>
                <div class="label">{{ $clsName }}</div>
                <div class="count">{{ $data['count'] }} item{{ $data['count'] !== 1 ? 's' : '' }}</div>
            </div>
        @endforeach
    </div>

    {{-- ================================================================ --}}
    {{-- BREAKDOWN 1: By Classification --}}
    {{-- ================================================================ --}}
    <div class="section">
        <div class="section-title">📊 Breakdown by Classification <span>(Level 1)</span></div>
        <table>
            <thead>
                <tr>
                    <th>Classification</th>
                    <th class="count-col">Items</th>
                    <th class="amount-col">Amount</th>
                    <th class="amount-col">% of Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byClassification->sortKeys() as $clsName => $data)
                    @php $clsColor = $classificationColors[$clsName] ?? ($clsName === 'Unclassified' ? '#94a3b8' : '#6b7280'); @endphp
                    <tr>
                        <td>
                            <span class="cls-pill" style="background-color: {{ $clsColor }}">{{ $clsName }}</span>
                        </td>
                        <td class="count-col">{{ $data['count'] }}</td>
                        <td class="amount-col">₹{{ number_format($data['amount'], 2) }}</td>
                        <td class="amount-col">{{ $totalAmount > 0 ? number_format(($data['amount'] / $totalAmount) * 100, 1) : 0 }}%</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td class="grand-total">Total</td>
                    <td class="count-col grand-total">{{ $allItems->count() }}</td>
                    <td class="amount-col grand-total">₹{{ number_format($totalAmount, 2) }}</td>
                    <td class="amount-col">100%</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ================================================================ --}}
    {{-- BREAKDOWN 2: By Classification + Category --}}
    {{-- ================================================================ --}}
    <div class="section">
        <div class="section-title">📂 Breakdown by Classification + Category <span>(Level 2)</span></div>
        <table>
            <thead>
                <tr>
                    <th>Classification</th>
                    <th>Category</th>
                    <th class="count-col">Items</th>
                    <th class="amount-col">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $lastCls2 = null; @endphp
                @foreach($byClassificationCategory->sortBy(fn($r) => $r['classification'].'-'.$r['category']) as $row)
                    @php $clsColor = $classificationColors[$row['classification']] ?? ($row['classification'] === 'Unclassified' ? '#94a3b8' : '#6b7280'); @endphp
                    <tr>
                        <td>
                            @if($lastCls2 !== $row['classification'])
                                <span class="cls-pill" style="background-color: {{ $clsColor }}">{{ $row['classification'] }}</span>
                                @php $lastCls2 = $row['classification']; @endphp
                            @endif
                        </td>
                        <td>{{ $row['category'] }}</td>
                        <td class="count-col">{{ $row['count'] }}</td>
                        <td class="amount-col">₹{{ number_format($row['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ================================================================ --}}
    {{-- BREAKDOWN 3: By Classification + Category + Payment --}}
    {{-- ================================================================ --}}
    <div class="section">
        <div class="section-title">💳 Breakdown by Classification + Category + Payment <span>(Level 3)</span></div>
        <table>
            <thead>
                <tr>
                    <th>Classification</th>
                    <th>Category</th>
                    <th>Payment Method</th>
                    <th class="count-col">Items</th>
                    <th class="amount-col">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $lastCls3 = null; $lastCat3 = null; @endphp
                @foreach($byClassificationCategoryPayment->sortBy(fn($r) => $r['classification'].'-'.$r['category'].'-'.$r['payment_method']) as $row)
                    @php $clsColor = $classificationColors[$row['classification']] ?? ($row['classification'] === 'Unclassified' ? '#94a3b8' : '#6b7280'); @endphp
                    <tr>
                        <td>
                            @if($lastCls3 !== $row['classification'])
                                <span class="cls-pill" style="background-color: {{ $clsColor }}">{{ $row['classification'] }}</span>
                                @php $lastCls3 = $row['classification']; @endphp
                            @endif
                        </td>
                        <td>
                            @if($lastCat3 !== $row['classification'].'-'.$row['category'])
                                {{ $row['category'] }}
                                @php $lastCat3 = $row['classification'].'-'.$row['category']; @endphp
                            @endif
                        </td>
                        <td><strong>{{ $row['payment_method'] }}</strong></td>
                        <td class="count-col">{{ $row['count'] }}</td>
                        <td class="amount-col">₹{{ number_format($row['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ================================================================ --}}
    {{-- ITEMIZED LIST --}}
    {{-- ================================================================ --}}
    <div class="section">
        <div class="section-title">📋 Itemized Expense List <span>(All {{ $allItems->count() }} items)</span></div>
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
                    <th class="amount-col">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allItems as $i => $item)
                    @php $clsColor = $classificationColors[$item['classification']] ?? ($item['classification'] === 'Unclassified' ? '#94a3b8' : '#6b7280'); @endphp
                    <tr>
                        <td style="color:#94a3b8;">{{ $i + 1 }}</td>
                        <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($item['date'])->format('d M Y') }}</td>
                        <td>{{ $item['description'] ?: '—' }}</td>
                        <td>{{ $item['category'] }}</td>
                        <td>{{ $item['payment_method'] }}</td>
                        <td>
                            <span class="badge {{ $item['type'] === 'normal' ? 'badge-normal' : 'badge-personal' }}">
                                {{ $item['type'] === 'normal' ? 'Normal' : 'Personal' }}
                            </span>
                        </td>
                        <td>
                            <span class="cls-pill" style="background-color: {{ $clsColor }}; font-size: 10px; padding: 1px 7px;">
                                {{ $item['classification'] }}
                            </span>
                        </td>
                        <td class="amount-col">₹{{ number_format($item['amount'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="7" class="grand-total">Grand Total</td>
                    <td class="amount-col grand-total">₹{{ number_format($totalAmount, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top:32px; padding-top:16px; border-top:1px solid #e2e8f0; color:#94a3b8; font-size:10px; text-align:center;">
        Generated by {{ config('app.name', 'LifeTracker') }} &bull; {{ now()->format('d M Y H:i') }} &bull; This report was not stored in the database.
    </div>

    <script>
        window.onload = function () {
            // Auto-open print dialog (only if no no-save param in URL would block this)
            setTimeout(() => window.print(), 600);
        };
    </script>
</body>
</html>


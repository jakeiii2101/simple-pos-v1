<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $dailyClosing->reading_number }}</title>
    <style>
        :root {
            --paper-width: {{ (int) $printerSetting->paper_width_mm }}mm;
            --paper-padding: {{ (int) $printerSetting->content_padding_mm }}mm;
            --base-font-size: {{ (int) $printerSetting->font_size_px }}px;
        }

        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #111; margin: 24px; font-size: var(--base-font-size); }
        .sheet { width: var(--paper-width); max-width: 100%; margin: auto; padding: var(--paper-padding); overflow-wrap: anywhere; }
        .center { text-align: center; }
        .row { display: flex; justify-content: space-between; gap: 8px; padding: 5px 0; }
        .row span:last-child { text-align: right; }
        .section { border-top: 1px dashed #555; margin-top: 16px; padding-top: 12px; }
        .label { font-size: .8em; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
        .total { font-size: 1.35em; font-weight: 800; }
        .muted { color: #555; font-size: .9em; }
        .actions { text-align: center; margin-bottom: 18px; }
        .actions button { padding: 9px 14px; cursor: pointer; }
        @page { margin: 0; }

        @media print {
            html, body { width: var(--paper-width); }
            body { margin: 0; }
            .actions { display: none; }
            .sheet { width: var(--paper-width); max-width: var(--paper-width); margin: 0; padding: var(--paper-padding); }
        }
    </style>
</head>
<body>
    @php($snapshot = $dailyClosing->snapshot)

    <div class="actions">
        <button onclick="window.print()">Print Z-Reading</button>
    </div>

    <main class="sheet">
        <div class="center">
            <h1 style="margin-bottom:4px">{{ $snapshot['seller']['trade_name'] ?? $snapshot['seller']['registered_name'] ?? 'SniperPOS' }}</h1>
            <div class="muted">{{ $snapshot['seller']['registered_address'] ?? '' }}</div>
            <div class="muted">TIN: {{ $snapshot['seller']['tin'] ?? 'Not configured' }}</div>
            <h2>Z-READING</h2>
            <strong>{{ $dailyClosing->reading_number }}</strong>
        </div>

        <div class="section">
            <div class="row"><span>Business Date</span><strong>{{ $dailyClosing->business_date->format('Y-m-d') }}</strong></div>
            <div class="row"><span>Closed At</span><span>{{ $dailyClosing->closed_at->format('Y-m-d H:i:s') }}</span></div>
            <div class="row"><span>Closed By</span><span>{{ $dailyClosing->closedBy->name }}</span></div>
        </div>

        <div class="section">
            <div class="label">Invoice Accountability</div>
            <div class="row"><span>Beginning Invoice</span><span>{{ $snapshot['invoice_range']['first'] ?? '—' }}</span></div>
            <div class="row"><span>Ending Invoice</span><span>{{ $snapshot['invoice_range']['last'] ?? '—' }}</span></div>
            <div class="row"><span>Invoices Issued</span><span>{{ $snapshot['invoice_range']['issued_count'] }}</span></div>
        </div>

        <div class="section">
            <div class="label">Sales</div>
            <div class="row"><span>Gross Sales</span><span>₱{{ number_format($snapshot['sales']['gross_sales'], 2) }}</span></div>
            <div class="row"><span>Discounts</span><span>₱{{ number_format($snapshot['sales']['discounts'], 2) }}</span></div>
            <div class="row"><span>VAT Exemptions</span><span>₱{{ number_format($snapshot['sales']['vat_exemptions'], 2) }}</span></div>
            <div class="row total"><span>Net Sales</span><span>₱{{ number_format($snapshot['sales']['net_sales'], 2) }}</span></div>
        </div>

        <div class="section">
            <div class="label">Tax Breakdown</div>
            @foreach ([
                'vatable_sales' => 'VATable Sales',
                'vat_amount' => 'VAT Amount',
                'vat_exempt_sales' => 'VAT-Exempt Sales',
                'zero_rated_sales' => 'Zero-Rated Sales',
                'non_vat_sales' => 'Non-VAT Sales',
            ] as $key => $label)
                <div class="row"><span>{{ $label }}</span><span>₱{{ number_format($snapshot['tax'][$key], 2) }}</span></div>
            @endforeach
        </div>

        <div class="section">
            <div class="label">Payment Accountability</div>
            @forelse ($snapshot['payments'] as $method => $payment)
                <div class="row"><span>{{ strtoupper($method) }} ({{ $payment['transaction_count'] }})</span><span>₱{{ number_format($payment['amount'], 2) }}</span></div>
            @empty
                <div class="muted">No payment activity.</div>
            @endforelse
        </div>

        <div class="section">
            <div class="label">Voids and Refunds</div>
            <div class="row"><span>Voids</span><span>₱{{ number_format($snapshot['reversals']['void_amount'], 2) }}</span></div>
            <div class="row"><span>Refunds</span><span>₱{{ number_format($snapshot['reversals']['refund_amount'], 2) }}</span></div>
            <div class="row"><span>Partial Refunds</span><span>{{ $snapshot['reversals']['partial_refund_count'] ?? 0 }} · ₱{{ number_format($snapshot['reversals']['partial_refund_amount'] ?? 0, 2) }}</span></div>
        </div>

        @if ($dailyClosing->notes)
            <div class="section">
                <div class="label">Notes</div>
                <p>{{ $dailyClosing->notes }}</p>
            </div>
        @endif

        <div class="center muted" style="margin-top:24px">Immutable daily closing record · Generated by SniperPOS</div>
    </main>
</body>
</html>

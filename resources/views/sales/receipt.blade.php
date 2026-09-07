<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SniperPOS Receipt {{ $sale->sale_number }}</title>
    <style>
        :root { --navy: #0F2747; --red: #E50914; --slate: #64748B; --line: #CBD5E1; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #0f172a; margin: 0; background: #f1f5f9; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin: 18px auto; padding: 0 12px; }
        .actions a, .actions button { display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border: 1px solid #cbd5e1; background: #fff; color: var(--navy); border-radius: 8px; cursor: pointer; text-decoration: none; font-size: 13px; font-weight: 700; }
        .actions .primary { border-color: var(--red); background: var(--red); color: #fff; }
        .receipt { width: 380px; max-width: calc(100% - 24px); margin: 0 auto 28px; background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 12px 30px rgba(15,39,71,.08); }
        .brand { display: flex; align-items: center; justify-content: center; gap: 10px; }
        .brand img { width: 38px; height: 38px; object-fit: contain; }
        .brand-name { margin: 0; color: var(--navy); font-size: 23px; font-weight: 800; letter-spacing: -.02em; }
        .tagline { margin-top: 3px; color: var(--slate); font-size: 11px; }
        .center { text-align: center; }
        .muted { color: var(--slate); font-size: 12px; }
        .row { display: flex; justify-content: space-between; gap: 12px; margin: 6px 0; }
        .row span:last-child { text-align: right; }
        .section { margin-top: 16px; padding-top: 12px; border-top: 1px dashed #94a3b8; }
        .item { padding: 10px 0; border-bottom: 1px dashed #cbd5e1; }
        .item-name { font-weight: 700; color: var(--navy); }
        .discount { color: #047857; }
        .total { padding-top: 8px; border-top: 1px solid #cbd5e1; font-size: 18px; font-weight: 800; color: var(--navy); }
        .payment-label { text-transform: uppercase; letter-spacing: .08em; font-size: 10px; font-weight: 800; color: var(--slate); }
        .footer { margin-top: 22px; padding-top: 14px; border-top: 1px dashed #94a3b8; text-align: center; }
        .footer strong { color: var(--navy); }
        @media print {
            body { background: #fff; }
            .actions { display: none !important; }
            .receipt { width: 80mm; max-width: 80mm; margin: 0 auto; padding: 6mm 4mm; border-radius: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    @php
        $payment = $sale->payment;
        $paymentMethod = $payment?->method ?? 'cash';
        $paymentLabel = match ($paymentMethod) {
            'gcash' => 'GCash',
            'card' => 'Card',
            'other' => 'Other',
            default => 'Cash',
        };
        $amountTendered = $payment?->amount_tendered ?? $sale->cash_received;
        $paymentChange = $payment?->change_due ?? $sale->change_due;
    @endphp

    <div class="actions">
        @if (auth()->user()?->isAdmin())
            <a href="{{ route('sales.show', ['sale' => $sale->id], false) }}">Sale Details</a>
        @endif
        <button type="button" onclick="window.print()">Print Receipt</button>
        <a href="{{ route('pos', [], false) }}" class="primary">New Sale</a>
    </div>

    <div class="receipt">
        <div class="center">
            <div class="brand">
                <img src="/icons/simple-pos-icon.svg" alt="SniperPOS">
                <h1 class="brand-name">SniperPOS</h1>
            </div>
            <div class="tagline">Precision in Every Sale.</div>
            <div class="muted" style="margin-top:8px;">Official Sales Receipt</div>
        </div>

        <div class="section muted">
            <div class="row"><span>Receipt</span><span>{{ $sale->sale_number }}</span></div>
            <div class="row"><span>Date</span><span>{{ $sale->completed_at->format('Y-m-d H:i') }}</span></div>
            <div class="row"><span>Cashier</span><span>{{ $sale->user->name }}</span></div>
        </div>

        <div class="section">
            @foreach ($sale->items as $item)
                <div class="item">
                    <div class="item-name">{{ $item->product_name }}</div>
                    <div class="row muted">
                        <span>{{ $item->quantity }} × ₱{{ number_format((float) $item->unit_price, 2) }}</span>
                        <span>₱{{ number_format((float) $item->line_total, 2) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="section">
            <div class="row"><span>Subtotal</span><span>₱{{ number_format((float) $sale->subtotal, 2) }}</span></div>
            @if ((float) $sale->discount_amount > 0)
                <div class="row discount">
                    <span>
                        Discount
                        @if ($sale->discount_type === 'percentage')
                            ({{ number_format((float) $sale->discount_value, 2) }}%)
                        @endif
                    </span>
                    <span>− ₱{{ number_format((float) $sale->discount_amount, 2) }}</span>
                </div>
            @endif
            <div class="row total"><span>Total</span><span>₱{{ number_format((float) $sale->total, 2) }}</span></div>
        </div>

        <div class="section">
            <div class="payment-label">Payment</div>
            <div class="row"><span>Method</span><span>{{ $paymentLabel }}</span></div>
            @if ($payment?->reference)
                <div class="row"><span>Reference</span><span>{{ $payment->reference }}</span></div>
            @endif
            <div class="row"><span>Amount</span><span>₱{{ number_format((float) ($payment?->amount ?? $sale->total), 2) }}</span></div>
            @if ($paymentMethod === 'cash')
                <div class="row"><span>Cash Tendered</span><span>₱{{ number_format((float) $amountTendered, 2) }}</span></div>
                <div class="row"><span>Change</span><span>₱{{ number_format((float) $paymentChange, 2) }}</span></div>
            @endif
        </div>

        <div class="footer muted">
            <strong>Thank you for your purchase!</strong>
            <div style="margin-top:4px;">SniperPOS · Precision in Every Sale.</div>
        </div>
    </div>
</body>
</html>

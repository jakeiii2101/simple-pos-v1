<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $sale->sale_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 0; background: #f3f4f6; }
        .receipt { width: 360px; max-width: calc(100% - 24px); margin: 24px auto; background: #fff; padding: 24px; }
        .center { text-align: center; }
        .muted { color: #666; font-size: 12px; }
        .row { display: flex; justify-content: space-between; gap: 12px; margin: 6px 0; }
        .item { padding: 10px 0; border-bottom: 1px dashed #bbb; }
        .total { font-size: 18px; font-weight: 700; }
        .actions { text-align: center; margin: 16px 0 30px; }
        button { padding: 10px 16px; border: 0; background: #111827; color: white; border-radius: 6px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .receipt { width: 80mm; max-width: 80mm; margin: 0 auto; padding: 8mm 4mm; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Print Receipt</button>
    </div>

    <div class="receipt">
        <div class="center">
            <h2 style="margin:0 0 6px;">Simple POS</h2>
            <div class="muted">Sales Receipt</div>
        </div>

        <div style="margin-top:18px;" class="muted">
            <div class="row"><span>Receipt</span><span>{{ $sale->sale_number }}</span></div>
            <div class="row"><span>Date</span><span>{{ $sale->completed_at->format('Y-m-d H:i') }}</span></div>
            <div class="row"><span>Cashier</span><span>{{ $sale->user->name }}</span></div>
        </div>

        <div style="margin-top:16px; border-top:1px dashed #999;">
            @foreach ($sale->items as $item)
                <div class="item">
                    <div style="font-weight:600;">{{ $item->product_name }}</div>
                    <div class="row muted">
                        <span>{{ $item->quantity }} × ₱{{ number_format((float) $item->unit_price, 2) }}</span>
                        <span>₱{{ number_format((float) $item->line_total, 2) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:14px;">
            <div class="row"><span>Subtotal</span><span>₱{{ number_format((float) $sale->subtotal, 2) }}</span></div>
            <div class="row total"><span>Total</span><span>₱{{ number_format((float) $sale->total, 2) }}</span></div>
            <div class="row"><span>Cash</span><span>₱{{ number_format((float) $sale->cash_received, 2) }}</span></div>
            <div class="row"><span>Change</span><span>₱{{ number_format((float) $sale->change_due, 2) }}</span></div>
        </div>

        <div class="center muted" style="margin-top:22px;">Thank you!</div>
    </div>
</body>
</html>

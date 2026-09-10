<x-app-layout>
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

    <div class="sniper-page">
        <div class="sniper-page-header">
            <div>
                <div class="sniper-kicker">Transaction record</div>
                <h1 class="sniper-title mt-1">Sale {{ $sale->sale_number }}</h1>
                <p class="sniper-subtitle">Completed {{ $sale->completed_at->format('M d, Y g:i A') }} by {{ $sale->user->name }}.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('sales', [], false) }}" wire:navigate class="sniper-btn-secondary">Back to Sales</a>
                <a href="{{ route('sales.invoice', ['sale' => $sale->id], false) }}" target="_blank" class="sniper-btn-secondary">Print Sales Invoice</a>
                <a href="{{ route('pos', [], false) }}" wire:navigate class="sniper-btn-primary">New Sale</a>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.35fr_.65fr]">
            <section class="sniper-card overflow-hidden">
                <div class="sniper-section-header">
                    <h2 class="font-heading text-base font-bold text-sniper-navy">Items</h2>
                    <p class="mt-1 text-xs text-sniper-slate">Snapshot captured at the moment of checkout.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="sniper-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="!text-right">Unit Price</th>
                                <th class="!text-right">Qty</th>
                                <th class="!text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sale->items as $item)
                                <tr>
                                    <td class="font-semibold !text-sniper-navy">{{ $item->product_name }}</td>
                                    <td>{{ $item->sku }}</td>
                                    <td class="!text-right whitespace-nowrap">₱{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="!text-right">{{ number_format($item->quantity) }}</td>
                                    <td class="!text-right whitespace-nowrap font-semibold !text-sniper-navy">₱{{ number_format((float) $item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="space-y-6">
                <livewire:sales.sale-adjustment-panel :sale="$sale" />
                <div class="sniper-card p-5">
                    <div class="sniper-kicker">Sale summary</div>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><span class="text-sniper-slate">Subtotal</span><span class="font-semibold text-sniper-navy">₱{{ number_format((float) $sale->subtotal, 2) }}</span></div>
                        @if ((float) $sale->discount_amount > 0)
                            <div class="flex justify-between gap-4 text-emerald-700">
                                <span>
                                    Discount
                                    @if ($sale->discount_type === 'percentage')
                                        ({{ number_format((float) $sale->discount_value, 2) }}%)
                                    @endif
                                </span>
                                <span class="font-semibold">− ₱{{ number_format((float) $sale->discount_amount, 2) }}</span>
                            </div>
                        @endif
                        @if ((float) $sale->vat_exemption_amount > 0)
                            <div class="flex justify-between gap-4 text-sky-700"><span>VAT exemption</span><span class="font-semibold">− ₱{{ number_format((float) $sale->vat_exemption_amount, 2) }}</span></div>
                        @endif
                        <div class="flex items-end justify-between gap-4 border-t border-slate-200 pt-3">
                            <span class="font-heading font-bold text-sniper-navy">Total</span>
                            <span class="font-heading text-2xl font-extrabold text-sniper-navy">₱{{ number_format((float) $sale->total, 2) }}</span>
                        </div>
                    </div>
                </div>

                @if ($sale->buyer_name || in_array($sale->discount_type, ['senior', 'pwd'], true))
                    <div class="sniper-card p-5">
                        <div class="sniper-kicker">Buyer and discount record</div>
                        <div class="mt-4 space-y-2 text-sm text-sniper-navy">
                            @if($sale->buyer_name)<div><span class="text-sniper-slate">Buyer:</span> {{ $sale->buyer_name }}</div>@endif
                            @if($sale->buyer_tin)<div><span class="text-sniper-slate">TIN:</span> {{ $sale->buyer_tin }}</div>@endif
                            @if($sale->buyer_address)<div><span class="text-sniper-slate">Address:</span> {{ $sale->buyer_address }}</div>@endif
                            @if($sale->buyer_business_style)<div><span class="text-sniper-slate">Business style:</span> {{ $sale->buyer_business_style }}</div>@endif
                            @if(in_array($sale->discount_type, ['senior', 'pwd'], true))<div class="border-t border-slate-200 pt-2"><span class="font-semibold">{{ $sale->discount_type === 'senior' ? 'Senior Citizen' : 'PWD' }}:</span> {{ $sale->discount_beneficiary_name }} · ID {{ $sale->discount_id_number }}</div>@endif
                        </div>
                    </div>
                @endif

                <div class="sniper-card p-5">
                    <div class="sniper-kicker">Payment</div>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><span class="text-sniper-slate">Method</span><span class="font-semibold text-sniper-navy">{{ $paymentLabel }}</span></div>
                        @if ($payment?->reference)
                            <div class="flex justify-between gap-4"><span class="text-sniper-slate">Reference</span><span class="break-all text-right font-semibold text-sniper-navy">{{ $payment->reference }}</span></div>
                        @endif
                        <div class="flex justify-between gap-4"><span class="text-sniper-slate">Amount</span><span class="font-semibold text-sniper-navy">₱{{ number_format((float) ($payment?->amount ?? $sale->total), 2) }}</span></div>
                        @if ($paymentMethod === 'cash')
                            <div class="flex justify-between gap-4"><span class="text-sniper-slate">Cash Tendered</span><span class="font-semibold text-sniper-navy">₱{{ number_format((float) $amountTendered, 2) }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-sniper-slate">Change</span><span class="font-semibold text-sniper-navy">₱{{ number_format((float) $paymentChange, 2) }}</span></div>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900">
                    <div class="font-bold">Protected financial record</div>
                    <p class="mt-1">Completed sales are read-only and cannot be edited or deleted through the application.</p>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>

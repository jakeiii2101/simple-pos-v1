<div class="sniper-page">
    <div class="sniper-page-header">
        <div><div class="sniper-kicker">BIR Operational Control</div><h1 class="sniper-title mt-1">Daily Readings</h1><p class="sniper-subtitle">Preview the live X-reading and permanently close a business date with a Z-reading.</p></div>
        <span class="sniper-badge-navy">X / Z Reading</span>
    </div>

    @if(session('success'))<div class="sniper-alert-success mb-5">{{ session('success') }}</div>@endif

    <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
        <section class="space-y-6">
            <div class="sniper-card p-5 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><div class="sniper-kicker">Live Preview</div><h2 class="mt-1 font-heading text-xl font-bold text-sniper-navy">X-Reading</h2><p class="mt-1 text-xs text-sniper-slate">Recalculates from current transaction data and does not close the day.</p></div><div><x-input-label for="business-date" value="Business Date" /><x-text-input id="business-date" wire:model.live="businessDate" type="date" max="{{ now()->toDateString() }}" class="mt-1.5 block w-full" /><x-input-error :messages="$errors->get('businessDate')" class="mt-2" /></div></div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 p-4"><div class="sniper-stat-label">Invoices</div><div class="mt-1 font-heading text-xl font-bold text-sniper-navy">{{ number_format($snapshot['invoice_range']['issued_count']) }}</div></div>
                    <div class="rounded-xl bg-slate-50 p-4"><div class="sniper-stat-label">Gross Sales</div><div class="mt-1 font-heading text-xl font-bold text-sniper-navy">₱{{ number_format($snapshot['sales']['gross_sales'], 2) }}</div></div>
                    <div class="rounded-xl bg-red-50 p-4"><div class="sniper-stat-label !text-red-600">Discounts</div><div class="mt-1 font-heading text-xl font-bold text-red-700">₱{{ number_format($snapshot['sales']['discounts'], 2) }}</div></div>
                    <div class="rounded-xl bg-emerald-50 p-4"><div class="sniper-stat-label !text-emerald-700">Net Sales</div><div class="mt-1 font-heading text-xl font-bold text-emerald-800">₱{{ number_format($snapshot['sales']['net_sales'], 2) }}</div></div>
                </div>

                <div class="mt-5 grid gap-5 lg:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 p-4"><div class="text-xs font-bold uppercase tracking-wider text-sniper-navy">Invoice Range</div><div class="mt-3 space-y-2 text-sm"><div class="flex justify-between gap-3"><span class="text-sniper-slate">Beginning</span><span class="font-semibold">{{ $snapshot['invoice_range']['first'] ?? '—' }}</span></div><div class="flex justify-between gap-3"><span class="text-sniper-slate">Ending</span><span class="font-semibold">{{ $snapshot['invoice_range']['last'] ?? '—' }}</span></div></div></div>
                    <div class="rounded-xl border border-slate-200 p-4"><div class="text-xs font-bold uppercase tracking-wider text-sniper-navy">Tax Breakdown</div><div class="mt-3 space-y-2 text-xs">@foreach(['vatable_sales'=>'VATable','vat_amount'=>'VAT','vat_exempt_sales'=>'VAT-Exempt','zero_rated_sales'=>'Zero-Rated','non_vat_sales'=>'Non-VAT'] as $key=>$label)<div class="flex justify-between gap-3"><span class="text-sniper-slate">{{ $label }}</span><span class="font-semibold">₱{{ number_format($snapshot['tax'][$key], 2) }}</span></div>@endforeach</div></div>
                    <div class="rounded-xl border border-slate-200 p-4"><div class="text-xs font-bold uppercase tracking-wider text-sniper-navy">Reversals Today</div><div class="mt-3 space-y-2 text-sm"><div class="flex justify-between"><span class="text-sniper-slate">Count</span><span class="font-semibold">{{ $snapshot['reversals']['count'] }}</span></div><div class="flex justify-between"><span class="text-sniper-slate">Voids</span><span class="font-semibold">₱{{ number_format($snapshot['reversals']['void_amount'], 2) }}</span></div><div class="flex justify-between"><span class="text-sniper-slate">Refunds</span><span class="font-semibold">₱{{ number_format($snapshot['reversals']['refund_amount'], 2) }}</span></div><div class="flex justify-between text-xs"><span class="text-sniper-slate">Partial refunds</span><span class="font-semibold">{{ $snapshot['reversals']['partial_refund_count'] }} · ₱{{ number_format($snapshot['reversals']['partial_refund_amount'], 2) }}</span></div></div></div>
                </div>

                <div class="mt-5"><div class="text-xs font-bold uppercase tracking-wider text-sniper-navy">Payment Accountability</div><div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">@forelse($snapshot['payments'] as $method=>$payment)<div class="rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200"><div class="text-xs font-semibold uppercase text-sniper-slate">{{ $method }}</div><div class="mt-1 font-heading text-lg font-bold text-sniper-navy">₱{{ number_format($payment['amount'], 2) }}</div><div class="mt-1 text-[10px] text-sniper-slate">{{ $payment['transaction_count'] }} transaction(s)@if($method==='cash') · Tendered ₱{{ number_format($payment['tendered'],2) }} · Change ₱{{ number_format($payment['change_due'],2) }}@endif</div></div>@empty<div class="text-sm text-sniper-slate">No payments for this date.</div>@endforelse</div></div>
            </div>

            <div class="sniper-section"><div class="sniper-section-header"><h2 class="font-heading text-lg font-bold text-sniper-navy">Recent Z-Readings</h2><p class="mt-1 text-xs text-sniper-slate">Permanent daily snapshots. Up to 31 recent closings are shown.</p></div><div class="overflow-x-auto"><table class="sniper-table"><thead><tr><th>Reading</th><th>Business Date</th><th>Closed By</th><th>Closed At</th><th class="!text-right">Net Sales</th><th class="!text-right">Action</th></tr></thead><tbody>@forelse($closings as $closing)<tr><td class="font-semibold !text-sniper-navy">{{ $closing->reading_number }}</td><td>{{ $closing->business_date->format('Y-m-d') }}</td><td>{{ $closing->closedBy->name }}</td><td>{{ $closing->closed_at->format('Y-m-d H:i') }}</td><td class="!text-right font-bold">₱{{ number_format($closing->snapshot['sales']['net_sales'],2) }}</td><td class="!text-right"><a href="{{ route('daily-readings.print',['dailyClosing'=>$closing->id],false) }}" target="_blank" class="sniper-action-link">Print</a></td></tr>@empty<tr><td colspan="6" class="sniper-empty">No Z-readings created yet.</td></tr>@endforelse</tbody></table></div></div>
        </section>

        <aside class="xl:sticky xl:top-24 xl:self-start">
            <div class="sniper-card p-5">
                <div class="sniper-kicker">Permanent Closing</div><h2 class="mt-1 font-heading text-lg font-bold text-sniper-navy">Z-Reading</h2>
                @if($existingClosing)
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"><div class="font-bold">Date already closed</div><div class="mt-1">{{ $existingClosing->reading_number }}</div><a href="{{ route('daily-readings.print',['dailyClosing'=>$existingClosing->id],false) }}" target="_blank" class="mt-3 inline-block font-semibold underline">Print Z-Reading</a></div>
                @else
                    <p class="mt-2 text-xs leading-5 text-sniper-slate">This freezes the displayed totals into an immutable record. Today’s POS will stop accepting new sales after closing.</p>
                    @error('closing')<div class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</div>@enderror
                    <div class="mt-4 space-y-4"><div><x-input-label for="closing-notes" value="Closing Notes" /><textarea id="closing-notes" wire:model="notes" rows="3" maxlength="500" class="sniper-input mt-1.5" placeholder="Optional remarks"></textarea><x-input-error :messages="$errors->get('notes')" class="mt-2" /></div><div><x-input-label for="closing-password" value="Administrator Password" /><x-text-input id="closing-password" wire:model="authorizationPassword" type="password" class="mt-1.5 block w-full" /><x-input-error :messages="$errors->get('authorizationPassword')" class="mt-2" /></div><label class="flex items-start gap-3"><input wire:model="confirmed" type="checkbox" class="mt-1 rounded border-slate-300 text-sniper-red focus:ring-sniper-red" /><span class="text-xs leading-5 text-sniper-slate">I confirm the totals and understand this Z-reading cannot be changed or deleted.</span></label><x-input-error :messages="$errors->get('confirmed')" class="mt-2" /><button wire:click="createZReading" wire:loading.attr="disabled" wire:confirm="Close this business date permanently?" type="button" class="sniper-btn-primary w-full">Create Z-Reading</button></div>
                @endif
            </div>
        </aside>
    </div>
</div>

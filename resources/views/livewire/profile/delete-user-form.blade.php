<?php

use Livewire\Volt\Component;

new class extends Component
{
    // SniperPOS V1 intentionally does not expose self-service account deletion.
    // User records are part of the audit and financial history. Administrators
    // can disable access through User Management without removing history.
}; ?>

<section class="space-y-4">
    <header>
        <div class="text-[11px] font-bold uppercase tracking-[0.22em] text-sniper-red">Account Protection</div>
        <h2 class="mt-1 font-heading text-xl font-bold text-sniper-navy">Account deletion is disabled</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-sniper-slate">
            SniperPOS keeps user records so completed sales, inventory movements, and audit history remain attributable. If access should be removed, an administrator can set the account to inactive in User Management.
        </p>
    </header>

    <div class="sniper-alert-info">
        This preserves business and financial history while immediately allowing administrators to revoke future access.
    </div>
</section>

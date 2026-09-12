<div class="sniper-page">
    <div class="sniper-page-header"><div><div class="sniper-kicker">Compliance & Recovery</div><h1 class="sniper-title mt-1">System Readiness</h1><p class="sniper-subtitle">Check invoice continuity, BIR configuration, private backups, and audit-export readiness.</p></div><span class="sniper-badge-navy">Administrator only</span></div>
    @if(session('success'))<div class="sniper-alert-success mb-5">{{ session('success') }}</div>@endif

    <section class="mb-6 rounded-2xl border-2 {{ $readiness['release_ready'] ? 'border-emerald-300 bg-emerald-50' : 'border-red-300 bg-red-50' }} p-5 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><div class="text-xs font-bold uppercase tracking-wider {{ $readiness['release_ready'] ? 'text-emerald-700' : 'text-red-700' }}">Final operational preflight</div><h2 class="mt-1 font-heading text-xl font-bold {{ $readiness['release_ready'] ? 'text-emerald-950' : 'text-red-950' }}">{{ $readiness['release_ready'] ? 'Blocking checks passed' : $readiness['blocking_failures'].' blocking check(s) need attention' }}</h2><p class="mt-1 text-xs leading-5 text-sniper-slate">This verifies technical readiness only. It does not represent BIR accreditation, registration, or approval.</p></div><span class="{{ $readiness['release_ready'] ? 'sniper-badge-success' : 'sniper-badge-danger' }}">{{ $readiness['release_ready'] ? 'Preflight ready' : 'Not ready' }}</span></div>
        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach($readiness['checks'] as $check)
                <div class="rounded-xl bg-white p-4 ring-1 {{ $check['passed'] ? 'ring-emerald-200' : ($check['severity'] === 'warning' ? 'ring-amber-200' : 'ring-red-200') }}">
                    <div class="flex items-center justify-between gap-3"><span class="text-sm font-bold text-sniper-navy">{{ $check['label'] }}</span><span class="{{ $check['passed'] ? 'sniper-badge-success' : ($check['severity'] === 'warning' ? 'sniper-badge-warning' : 'sniper-badge-danger') }}">{{ $check['passed'] ? 'Pass' : ucfirst($check['severity']) }}</span></div>
                    @if (! $check['passed'])
                        <p class="mt-2 text-xs leading-5 text-sniper-slate">{{ $check['remediation'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="sniper-stat"><div class="sniper-stat-label">BIR Configuration</div><div class="mt-3"><span class="{{ $readiness['bir_configured'] ? 'sniper-badge-success' : 'sniper-badge-danger' }}">{{ $readiness['bir_configured'] ? 'Ready' : 'Missing' }}</span></div></div>
        <div class="sniper-stat"><div class="sniper-stat-label">Invoice Sequence</div><div class="mt-3"><span class="{{ $readiness['sequence_configured'] && ! $readiness['sequence_behind'] ? 'sniper-badge-success' : 'sniper-badge-danger' }}">{{ $readiness['sequence_configured'] && ! $readiness['sequence_behind'] ? 'Ready' : 'Needs attention' }}</span></div></div>
        <div class="sniper-stat"><div class="sniper-stat-label">Missing Numbers</div><div class="sniper-stat-value">{{ number_format(count($readiness['missing_numbers'])) }}{{ $readiness['missing_truncated'] ? '+' : '' }}</div></div>
        <div class="sniper-stat"><div class="sniper-stat-label">Private Backups</div><div class="sniper-stat-value">{{ number_format($readiness['backup_count']) }}</div></div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="sniper-card p-5 sm:p-6"><div class="sniper-kicker">Invoice Integrity</div><h2 class="mt-1 font-heading text-lg font-bold text-sniper-navy">Sequence Diagnostic</h2>
            @if($readiness['sequence'])<div class="mt-4 grid grid-cols-2 gap-3 text-sm"><div class="rounded-lg bg-slate-50 p-3"><div class="text-xs text-sniper-slate">Prefix</div><div class="font-semibold">{{ $readiness['sequence']->prefix }}</div></div><div class="rounded-lg bg-slate-50 p-3"><div class="text-xs text-sniper-slate">Current Counter</div><div class="font-semibold">{{ number_format($readiness['sequence']->current_number) }}</div></div></div>@endif
            @if($readiness['missing_numbers'])<div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4"><div class="text-sm font-bold text-amber-900">Missing sequence numbers</div><div class="mt-2 break-words text-xs text-amber-800">{{ implode(', ', $readiness['missing_numbers']) }}{{ $readiness['missing_truncated'] ? ' … first 100 shown' : '' }}</div><p class="mt-2 text-xs text-amber-800">Investigate the audit log. Never reuse or renumber an issued invoice.</p></div>@else<div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">No internal gaps detected in issued sequential invoices.</div>@endif
            @if($readiness['malformed_invoices'])<div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4"><div class="text-sm font-bold text-red-900">Invoices outside the active format</div><div class="mt-2 text-xs text-red-800">{{ implode(', ', $readiness['malformed_invoices']) }}</div></div>@endif
        </section>

        <section class="sniper-card p-5 sm:p-6"><div class="sniper-kicker">Disaster Recovery</div><h2 class="mt-1 font-heading text-lg font-bold text-sniper-navy">Database Backup</h2><p class="mt-2 text-xs leading-5 text-sniper-slate">Automatic backups run daily at 2:00 AM when the Laravel scheduler is active. Files older than 30 days are removed.</p>
            @error('backup')<div class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</div>@enderror
            @if($readiness['latest_backup'])<div class="mt-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200"><div class="flex items-center justify-between gap-2"><div class="text-xs text-sniper-slate">Latest private backup</div><span class="{{ $readiness['latest_backup_checksum_valid'] ? 'sniper-badge-success' : 'sniper-badge-danger' }}">{{ $readiness['latest_backup_checksum_valid'] ? 'Checksum valid' : 'Unverified' }}</span></div><div class="mt-1 break-all text-sm font-semibold text-sniper-navy">{{ $readiness['latest_backup']['name'] }}</div><div class="mt-1 text-xs text-sniper-slate">{{ number_format($readiness['latest_backup']['size']/1024,1) }} KB · {{ date('Y-m-d H:i:s',$readiness['latest_backup']['created_at']) }}@if($readiness['latest_backup_age_hours'] !== null) · {{ $readiness['latest_backup_age_hours'] }} hour(s) old@endif</div><a href="{{ route('compliance.backup',['filename'=>$readiness['latest_backup']['name']],false) }}" class="mt-3 inline-block text-sm font-semibold text-sniper-red underline">Download backup</a></div>@else<div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">No private backup has been created yet.</div>@endif
            <div class="mt-4"><x-input-label for="backup-password" value="Administrator Password" /><x-text-input id="backup-password" wire:model="authorizationPassword" type="password" class="mt-1.5 block w-full" /><x-input-error :messages="$errors->get('authorizationPassword')" class="mt-2" /><button type="button" wire:click="createBackup" wire:loading.attr="disabled" class="sniper-btn-primary mt-3 w-full">Create Backup Now</button></div>
        </section>
    </div>

    <section class="sniper-card mt-6 p-5 sm:p-6"><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><div class="sniper-kicker">Audit Retention</div><h2 class="mt-1 font-heading text-lg font-bold text-sniper-navy">Export Audit History</h2><p class="mt-1 text-xs text-sniper-slate">Download the current month’s immutable security and transaction activity.</p></div><a href="{{ route('compliance.audit',['from'=>now()->startOfMonth()->toDateString(),'to'=>now()->endOfMonth()->toDateString()],false) }}" class="sniper-btn-secondary">Download Audit CSV</a></div></section>
</div>

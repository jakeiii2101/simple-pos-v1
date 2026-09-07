<div class="sniper-page">
    <div class="sniper-page-header">
        <div>
            <div class="sniper-kicker">Security & accountability</div>
            <h1 class="sniper-title mt-1">Audit Log</h1>
            <p class="sniper-subtitle">Review critical sales, inventory, and user-access changes recorded by SniperPOS.</p>
        </div>
        <span class="sniper-badge-navy">Read only</span>
    </div>

    <div class="sniper-card p-5 sm:p-6">
        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_260px_auto] md:items-end">
            <div>
                <x-input-label for="audit-search" value="Search" />
                <x-text-input id="audit-search" wire:model.live.debounce.300ms="search" type="text" class="mt-1.5 block w-full" placeholder="Description, action, or user" />
            </div>
            <div>
                <x-input-label for="audit-action" value="Action" />
                <select id="audit-action" wire:model.live="action" class="sniper-input mt-1.5">
                    <option value="">All actions</option>
                    @foreach($actions as $actionName)
                        <option value="{{ $actionName }}">{{ $actionName }}</option>
                    @endforeach
                </select>
            </div>
            <x-secondary-button type="button" wire:click="clearFilters">Clear</x-secondary-button>
        </div>
    </div>

    <div class="sniper-table-wrap mt-6">
        <div class="sniper-section-header">
            <h2 class="font-heading text-base font-bold text-sniper-navy">Recent Security Activity</h2>
            <p class="mt-1 text-xs text-sniper-slate">The latest 200 matching records are shown. Audit records cannot be edited or deleted through the application.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="sniper-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Subject</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr wire:key="audit-{{ $log->id }}">
                            <td class="whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <div class="font-semibold text-sniper-navy">{{ $log->user?->name ?? 'System / deleted user' }}</div>
                                @if($log->user)
                                    <div class="mt-0.5 text-xs text-sniper-slate">{{ $log->user->email }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap"><span class="sniper-badge-neutral">{{ $log->action }}</span></td>
                            <td class="whitespace-nowrap text-xs text-sniper-slate">
                                @if($log->auditable_type)
                                    {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="min-w-[280px]">
                                <div class="text-sm text-sniper-navy">{{ $log->description }}</div>
                                @if($log->metadata)
                                    <details class="mt-2 text-xs text-sniper-slate">
                                        <summary class="cursor-pointer font-semibold">Metadata</summary>
                                        <pre class="mt-2 max-w-lg overflow-x-auto whitespace-pre-wrap break-words rounded-lg bg-slate-50 p-3">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-xs text-sniper-slate">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="sniper-empty">No audit records match the current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

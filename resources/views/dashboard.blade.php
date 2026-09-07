<x-app-layout>
    <div class="sniper-page">
        <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="sniper-kicker">Business overview</div>
                <h1 class="sniper-title mt-2">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
                <p class="sniper-copy mt-2">Let’s keep every sale accurate, every product visible, and every decision clear.</p>
            </div>
            <a href="{{ route('pos', [], false) }}" wire:navigate class="sniper-btn-primary gap-2 self-start sm:self-auto">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l2.4 10.2a2 2 0 0 0 2 1.5h7.8a2 2 0 0 0 1.9-1.4L21 7H6m4 12h.01M18 19h.01"/></svg>
                Open POS
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Sales Today', 'Ready', 'Transactions are tracked from the POS', 'cart'],
                ['Products', 'Manage', 'Keep prices, SKUs and barcodes accurate', 'box'],
                ['Inventory', 'Control', 'Watch stock movement and low-stock items', 'inventory'],
                ['Reports', 'Insights', 'Turn transactions into business decisions', 'chart'],
            ] as [$label, $value, $copy, $icon])
                <div class="sniper-card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-sniper-red ring-1 ring-red-100">
                            @if($icon === 'cart')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l2.4 10.2a2 2 0 0 0 2 1.5h7.8a2 2 0 0 0 1.9-1.4L21 7H6"/></svg>
                            @elseif($icon === 'box')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3 8 4-8 4-8-4 8-4Zm-8 4v10l8 4 8-4V7m-8 4v10"/></svg>
                            @elseif($icon === 'inventory')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16v14H4V6Zm3-3h10v3H7V3Zm2 8h6"/></svg>
                            @else
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 20V10m7 10V4m7 16v-7"/></svg>
                            @endif
                        </div>
                        <span class="sniper-badge-neutral">Live</span>
                    </div>
                    <div class="mt-5 text-xs font-medium uppercase tracking-[0.12em] text-sniper-slate">{{ $label }}</div>
                    <div class="mt-1 font-heading text-2xl font-bold text-sniper-navy">{{ $value }}</div>
                    <p class="mt-2 text-xs leading-5 text-sniper-slate">{{ $copy }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_.65fr]">
            <section class="sniper-card overflow-hidden">
                <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 class="font-heading text-lg font-bold text-sniper-navy">Quick actions</h2>
                        <p class="mt-1 text-sm text-sniper-slate">Jump straight into the tasks that keep your store moving.</p>
                    </div>
                    <span class="text-xs font-medium text-sniper-slate">Precision workflow</span>
                </div>

                <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3 sm:p-6">
                    <a href="{{ route('pos', [], false) }}" wire:navigate class="group rounded-2xl border border-slate-200 bg-white p-4 hover:-translate-y-0.5 hover:border-red-200 hover:shadow-sniper">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sniper-red text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h2l2.4 10.2a2 2 0 0 0 2 1.5h7.8a2 2 0 0 0 1.9-1.4L21 7H6"/></svg></div>
                        <h3 class="mt-4 font-heading text-sm font-bold text-sniper-navy">Start a sale</h3>
                        <p class="mt-1 text-xs leading-5 text-sniper-slate">Search, scan, build the cart, and complete checkout.</p>
                    </a>

                    @if(auth()->user()->isAdmin())
                        @foreach ([
                            ['products', 'Products', 'Manage SKUs, prices and barcodes.'],
                            ['inventory', 'Inventory', 'Review stock and update quantities.'],
                            ['sales', 'Sales History', 'Review completed transactions.'],
                            ['reports', 'Reports', 'Analyze business performance.'],
                            ['users', 'Users', 'Control team access and roles.'],
                        ] as [$route, $title, $copy])
                            <a href="{{ route($route, [], false) }}" wire:navigate class="group rounded-2xl border border-slate-200 bg-white p-4 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-sniper">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-sniper-navy ring-1 ring-slate-200">
                                    <span class="font-heading text-sm font-bold">{{ strtoupper(substr($title, 0, 1)) }}</span>
                                </div>
                                <h3 class="mt-4 font-heading text-sm font-bold text-sniper-navy">{{ $title }}</h3>
                                <p class="mt-1 text-xs leading-5 text-sniper-slate">{{ $copy }}</p>
                            </a>
                        @endforeach
                    @endif
                </div>
            </section>

            <aside class="sniper-card overflow-hidden bg-sniper-navy text-white ring-0">
                <div class="relative p-6">
                    <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full border-[24px] border-white/[0.04]"></div>
                    <div class="relative">
                        <x-sniper-icon class="h-14 w-14 text-white" />
                        <div class="mt-6 text-[10px] font-bold uppercase tracking-[0.22em] text-red-300">SniperPOS Standard</div>
                        <h2 class="mt-2 font-heading text-2xl font-bold leading-tight text-white">Precision in Every Sale.</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-300">A focused workspace for fast checkout, accurate inventory, and dependable reporting.</p>

                        <div class="mt-6 space-y-3 border-t border-white/10 pt-5 text-sm">
                            <div class="flex items-center gap-3"><span class="h-2 w-2 rounded-full bg-sniper-red"></span><span class="text-slate-200">Clean, consistent workflow</span></div>
                            <div class="flex items-center gap-3"><span class="h-2 w-2 rounded-full bg-sniper-red"></span><span class="text-slate-200">Role-aware navigation</span></div>
                            <div class="flex items-center gap-3"><span class="h-2 w-2 rounded-full bg-sniper-red"></span><span class="text-slate-200">Responsive business UI</span></div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>

<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div x-data="{ open: false }">
    <div class="fixed inset-x-0 top-0 z-40 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 lg:hidden">
        <a href="{{ route('dashboard', [], false) }}" wire:navigate class="flex items-center gap-3">
            <x-application-logo class="h-10 w-10" />
            <div>
                <div class="font-heading text-lg font-bold leading-tight text-sniper-navy">SniperPOS</div>
                <div class="text-[10px] font-medium uppercase tracking-[0.18em] text-sniper-slate">Precision in Every Sale</div>
            </div>
        </a>

        <button @click="open = true" class="rounded-lg p-2 text-sniper-navy hover:bg-sniper-light focus:outline-none focus:ring-2 focus:ring-sniper-red/30" aria-label="Open navigation">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <div x-show="open" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="open = false" x-cloak></div>

    <aside :class="open ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-sniper-navy text-white transition-transform duration-200 lg:translate-x-0">
        <div class="flex h-20 items-center justify-between border-b border-white/10 px-5">
            <a href="{{ route('dashboard', [], false) }}" wire:navigate class="flex items-center gap-3">
                <x-application-logo class="h-11 w-11" />
                <div>
                    <div class="font-heading text-xl font-bold leading-tight text-white">SniperPOS</div>
                    <div class="text-[9px] font-semibold uppercase tracking-[0.16em] text-slate-300">Precision in Every Sale</div>
                </div>
            </a>

            <button @click="open = false" class="rounded-lg p-2 text-slate-300 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Close navigation">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
            @php
                $items = [
                    ['route' => 'dashboard', 'label' => 'Dashboard'],
                    ['route' => 'pos', 'label' => 'POS'],
                ];

                if (auth()->user()->isAdmin()) {
                    $items = array_merge($items, [
                        ['route' => 'categories', 'label' => 'Categories'],
                        ['route' => 'products', 'label' => 'Products'],
                        ['route' => 'inventory', 'label' => 'Inventory'],
                        ['route' => 'sales', 'label' => 'Sales'],
                        ['route' => 'reports', 'label' => 'Reports'],
                        ['route' => 'users', 'label' => 'Users'],
                    ]);
                }
            @endphp

            @foreach ($items as $item)
                @php($active = request()->routeIs($item['route']))
                <a href="{{ route($item['route'], [], false) }}" wire:navigate @click="open = false"
                    @class([
                        'flex items-center rounded-lg px-3 py-2.5 text-sm font-semibold transition-all duration-200',
                        'bg-sniper-red text-white shadow-sm' => $active,
                        'text-slate-300 hover:bg-white/10 hover:text-white' => ! $active,
                    ])>
                    <span class="mr-3 h-2 w-2 rounded-full {{ $active ? 'bg-white' : 'bg-slate-500' }}"></span>
                    {{ __($item['label']) }}
                </a>
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="mb-3 rounded-lg bg-white/5 px-3 py-3">
                <div class="truncate text-sm font-semibold text-white" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="mt-0.5 truncate text-xs text-slate-400">{{ auth()->user()->email }}</div>
            </div>

            <a href="{{ route('profile', [], false) }}" wire:navigate class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-300 hover:bg-white/10 hover:text-white">Profile</a>
            <button wire:click="logout" class="mt-1 w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-300 hover:bg-white/10 hover:text-white">Log Out</button>
        </div>
    </aside>

    <div class="h-16 lg:hidden"></div>
</div>

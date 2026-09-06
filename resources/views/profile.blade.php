<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="sniper-kicker">Account</div>
            <h1 class="font-heading text-2xl font-bold tracking-tight text-sniper-navy">Profile & Security</h1>
            <p class="mt-1 text-sm text-sniper-slate">Manage your personal details, password, and account preferences.</p>
        </div>
    </x-slot>

    <div class="sniper-page">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-6">
                <section class="sniper-card p-5 sm:p-7">
                    <livewire:profile.update-profile-information-form />
                </section>
                <section class="sniper-card p-5 sm:p-7">
                    <livewire:profile.update-password-form />
                </section>
                <section class="rounded-2xl border border-red-200 bg-white p-5 shadow-sniper sm:p-7">
                    <livewire:profile.delete-user-form />
                </section>
            </div>

            <aside class="space-y-4">
                <div class="sniper-card p-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-sniper-navy font-heading text-xl font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                    <h2 class="mt-4 font-heading text-lg font-bold text-sniper-navy">{{ auth()->user()->name }}</h2>
                    <p class="mt-1 break-all text-sm text-sniper-slate">{{ auth()->user()->email }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="sniper-badge-navy">{{ ucfirst(auth()->user()->role) }}</span>
                        <span class="{{ auth()->user()->status === 'active' ? 'sniper-badge-success' : 'sniper-badge-neutral' }}">{{ ucfirst(auth()->user()->status) }}</span>
                    </div>
                </div>
                <div class="rounded-2xl bg-sniper-navy p-5 text-white shadow-sniper">
                    <div class="text-xs font-bold uppercase tracking-[0.2em] text-red-300">Security Tip</div>
                    <p class="mt-3 text-sm leading-6 text-slate-200">Use a unique password and keep your account credentials private. SniperPOS access is tied to your assigned role.</p>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>

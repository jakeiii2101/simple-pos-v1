@props(['compact' => false, 'dark' => false])

<a {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }} href="{{ url('/') }}" aria-label="SniperPOS home">
    <x-sniper-icon class="shrink-0 {{ $compact ? 'h-9 w-9' : 'h-11 w-11' }} {{ $dark ? 'text-white' : 'text-sniper-navy' }}" />
    @unless($compact)
        <span class="leading-none">
            <span class="font-heading text-xl font-extrabold tracking-[-0.04em] {{ $dark ? 'text-white' : 'text-sniper-navy' }}">Sniper<span class="text-sniper-red">POS</span></span>
            <span class="mt-1 block text-[8px] font-semibold uppercase tracking-[0.23em] {{ $dark ? 'text-slate-300' : 'text-sniper-slate' }}">Precision in Every Sale.</span>
        </span>
    @endunless
</a>

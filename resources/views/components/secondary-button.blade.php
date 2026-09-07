<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-sniper-navy shadow-sm transition-all duration-200 hover:bg-sniper-light focus:outline-none focus:ring-2 focus:ring-sniper-navy/20 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-40']) }}>
    {{ $slot }}
</button>

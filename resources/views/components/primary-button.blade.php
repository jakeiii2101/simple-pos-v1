<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-xl border border-transparent bg-sniper-red px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_rgba(229,9,20,0.18)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-sniper-red/30 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-50']) }}>
    {{ $slot }}
</button>

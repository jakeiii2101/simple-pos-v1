@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-[10px] border-slate-300 bg-white text-sniper-navy shadow-sm placeholder:text-slate-400 focus:border-sniper-red focus:ring-sniper-red/20 disabled:bg-slate-100 disabled:text-slate-500']) }}>

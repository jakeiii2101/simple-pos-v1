@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-sniper-navy']) }}>
    {{ $value ?? $slot }}
</label>

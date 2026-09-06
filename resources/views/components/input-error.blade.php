@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'space-y-1 text-sm font-medium text-sniper-red']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5"><span aria-hidden="true">•</span><span>{{ $message }}</span></li>
        @endforeach
    </ul>
@endif

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0F2747">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SniperPOS">

    <title>{{ config('app.name', 'SniperPOS') }}</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/icon-192.png" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=montserrat:600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-900">
    <div class="min-h-screen">
        <livewire:layout.navigation />

        <div class="lg:pl-64">
            <header class="sticky top-0 z-30 hidden h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-6 backdrop-blur lg:flex xl:px-8">
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sniper-slate">SniperPOS Workspace</div>
                    <div class="mt-1 font-heading text-lg font-bold text-sniper-navy">
                        {{ isset($header) ? '' : ucwords(str_replace(['.', '-'], ' ', request()->route()?->getName() ?? 'Dashboard')) }}
                        @if(isset($header)){{ $header }}@endif
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-sniper-slate xl:block">
                        <span class="font-medium text-sniper-navy">{{ now()->format('D, M j') }}</span>
                    </div>
                    <button type="button" class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-sniper-navy hover:bg-slate-50" aria-label="Notifications">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 0 1-6 0"/></svg>
                        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-sniper-red ring-2 ring-white"></span>
                    </button>
                    <a href="{{ route('profile', [], false) }}" wire:navigate class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-2.5 py-2 hover:bg-slate-50">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sniper-navy font-heading text-sm font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                        <span class="hidden pr-1 xl:block">
                            <span class="block max-w-36 truncate text-sm font-semibold text-sniper-navy">{{ auth()->user()->name }}</span>
                            <span class="block text-xs capitalize text-sniper-slate">{{ auth()->user()->role ?? 'User' }}</span>
                        </span>
                    </a>
                </div>
            </header>

            <main class="min-h-[calc(100vh-5rem)] bg-slate-50">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>

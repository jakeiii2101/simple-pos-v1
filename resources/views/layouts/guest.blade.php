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
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&family=montserrat:600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased bg-slate-100">
        <div class="min-h-screen lg:grid lg:grid-cols-2">
            <section class="hidden lg:flex bg-sniper-navy px-12 py-16 text-white items-center justify-center">
                <div class="max-w-lg text-center">
                    <x-application-logo class="mx-auto h-40 w-40" />
                    <h1 class="mt-8 font-heading text-4xl font-extrabold tracking-tight text-white">SniperPOS</h1>
                    <p class="mt-3 font-heading text-lg font-semibold text-white">Precision in Every Sale.</p>
                    <p class="mt-6 text-sm leading-6 text-slate-300">Fast, reliable sales and inventory management with a clean precision-focused workflow.</p>
                </div>
            </section>

            <section class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:min-h-0">
                <div class="w-full max-w-md">
                    <div class="mb-8 text-center lg:hidden">
                        <x-application-logo class="mx-auto h-24 w-24" />
                        <h1 class="mt-4 font-heading text-3xl font-bold text-sniper-navy">SniperPOS</h1>
                        <p class="mt-1 text-sm font-medium text-sniper-slate">Precision in Every Sale.</p>
                    </div>

                    <div class="sniper-card px-6 py-7 sm:px-8">
                        {{ $slot }}
                    </div>
                </div>
            </section>
        </div>
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'HireMe') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-950 antialiased">
        <div class="min-h-screen">
            <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
                <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Navigatie publica">
                    <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 text-lg font-bold tracking-tight text-slate-950">
                        <span class="grid h-8 w-8 place-items-center rounded-md bg-emerald-700 text-sm font-black text-white">HM</span>
                        <span>HireMe</span>
                    </a>

                    <div class="flex min-w-0 items-center gap-2 text-sm font-semibold sm:gap-3">
                        <a href="{{ route('jobs.index') }}" class="public-nav-link">Joburi</a>
                        <a href="{{ route('home') }}#companii" class="public-nav-link">Companii</a>

                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-primary">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="public-nav-link">Login</a>
                            <a href="{{ route('register') }}" class="btn-primary">Register</a>
                        @endauth
                    </div>
                </nav>
            </header>

            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>

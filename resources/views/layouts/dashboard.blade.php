@php
    $user = auth()->user();
    $role = $user?->role;

    $navigation = match ($role) {
        \App\Enums\UserRole::Candidate => [
            ['label' => 'Dashboard', 'href' => route('candidate.dashboard'), 'active' => request()->routeIs('candidate.dashboard')],
            ['label' => 'Profil', 'href' => route('candidate.profile.edit'), 'active' => request()->routeIs('candidate.profile.*')],
            ['label' => 'Aplicarile mele', 'href' => route('candidate.applications.index'), 'active' => request()->routeIs('candidate.applications.*')],
            ['label' => 'Mesaje', 'href' => route('conversations.index'), 'active' => request()->routeIs('conversations.*')],
        ],
        \App\Enums\UserRole::Employer => [
            ['label' => 'Dashboard', 'href' => route('employer.dashboard'), 'active' => request()->routeIs('employer.dashboard')],
            ['label' => 'Companii', 'href' => route('employer.companies.index'), 'active' => request()->routeIs('employer.companies.*')],
            ['label' => 'Joburi', 'href' => route('employer.jobs.index'), 'active' => request()->routeIs('employer.jobs.*')],
            ['label' => 'Aplicari', 'href' => route('employer.applications.index'), 'active' => request()->routeIs('employer.applications.*')],
            ['label' => 'Mesaje', 'href' => route('conversations.index'), 'active' => request()->routeIs('conversations.*')],
        ],
        \App\Enums\UserRole::Admin => [
            ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Utilizatori', 'href' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')],
            ['label' => 'Companii', 'href' => route('admin.companies.index'), 'active' => request()->routeIs('admin.companies.*')],
            ['label' => 'Joburi', 'href' => route('admin.jobs.index'), 'active' => request()->routeIs('admin.jobs.*')],
        ],
        default => [
            ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
        ],
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HireMe') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 font-sans text-slate-950 antialiased">
        <div class="min-h-screen">
            <header x-data="{ open: false }" class="border-b border-slate-200 bg-white">
                <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Navigatie dashboard">
                    <div class="flex min-w-0 items-center gap-6">
                        <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2 text-lg font-bold tracking-tight text-slate-950">
                            <span class="grid h-8 w-8 place-items-center rounded-md bg-emerald-700 text-sm font-black text-white">HM</span>
                            <span>HireMe</span>
                        </a>

                        <div class="hidden items-center gap-1 lg:flex">
                            @foreach ($navigation as $item)
                                <a href="{{ $item['href'] }}" @class([
                                    'dashboard-nav-link',
                                    'dashboard-nav-link-active' => $item['active'],
                                ])>{{ $item['label'] }}</a>
                            @endforeach
                        </div>
                    </div>

                    <div class="hidden items-center gap-3 lg:flex">
                        @if ($role === \App\Enums\UserRole::Employer)
                            <a href="{{ route('employer.jobs.create') }}" class="btn-primary">Publica job</a>
                        @endif

                        <div class="text-right">
                            <p class="max-w-44 truncate text-sm font-semibold text-slate-900">{{ $user?->name }}</p>
                            <p class="text-xs capitalize text-slate-500">{{ $role?->value }}</p>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-secondary">Logout</button>
                        </form>
                    </div>

                    <button type="button" @click="open = ! open" class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-slate-300 text-slate-700 lg:hidden" aria-label="Deschide navigatia" :aria-expanded="open.toString()" aria-controls="dashboard-mobile-menu">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path x-show="! open" d="M3 5.5h14M3 10h14M3 14.5h14" stroke-linecap="round" />
                            <path x-show="open" d="M5 5l10 10M15 5 5 15" stroke-linecap="round" />
                        </svg>
                    </button>
                </nav>

                <div id="dashboard-mobile-menu" x-show="open" x-cloak class="border-t border-slate-200 bg-white lg:hidden">
                    <div class="mx-auto max-w-7xl space-y-1 px-4 py-3 sm:px-6">
                        @foreach ($navigation as $item)
                            <a href="{{ $item['href'] }}" @class([
                                'mobile-nav-link',
                                'mobile-nav-link-active' => $item['active'],
                            ])>{{ $item['label'] }}</a>
                        @endforeach

                        @if ($role === \App\Enums\UserRole::Employer)
                            <a href="{{ route('employer.jobs.create') }}" class="mobile-nav-link">Publica job</a>
                        @endif

                        <div class="border-t border-slate-200 pt-3">
                            <p class="text-sm font-semibold text-slate-900">{{ $user?->name }}</p>
                            <p class="text-xs text-slate-500">{{ $user?->email }}</p>
                            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                                @csrf
                                <button type="submit" class="btn-secondary w-full justify-center">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            @isset($header)
                <section class="border-b border-slate-200 bg-white">
                    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </section>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>

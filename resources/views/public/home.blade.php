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
    <body class="bg-slate-50 font-sans text-slate-950 antialiased">
        <div class="min-h-screen">
            <header class="border-b border-slate-200 bg-white">
                <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8" aria-label="Navigatie publica">
                    <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight text-slate-950">HireMe</a>
                    <div class="flex items-center gap-4 text-sm font-medium">
                        <a href="{{ route('jobs.index') }}" class="text-slate-700 hover:text-slate-950">Joburi</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-md bg-slate-950 px-4 py-2 text-white hover:bg-slate-800">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-slate-700 hover:text-slate-950">Login</a>
                            <a href="{{ route('register') }}" class="rounded-md bg-slate-950 px-4 py-2 text-white hover:bg-slate-800">Cont nou</a>
                        @endauth
                    </div>
                </nav>
            </header>

            <main>
                <section class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8 lg:py-20">
                    <div class="flex flex-col justify-center">
                        <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Marketplace de recrutare</p>
                        <h1 class="mt-4 max-w-3xl text-4xl font-bold tracking-tight text-slate-950 sm:text-6xl">Conectam companiile ambitioase cu oamenii potriviti.</h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">Descopera roluri publicate de angajatori verificati sau creeaza un cont pentru a-ti administra recrutarea intr-un singur loc.</p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('jobs.index') }}" class="inline-flex items-center justify-center rounded-md bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Caut un job</a>
                            <a href="{{ route('register', ['role' => 'employer']) }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2">Angajez oameni</a>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-950">Joburi recomandate</h2>
                        <div class="mt-5 space-y-4">
                            @forelse ($featuredJobs as $job)
                                <article class="rounded-md border border-slate-200 p-4">
                                    <p class="text-sm text-slate-500">{{ $job->company->name }}</p>
                                    <h3 class="mt-1 text-lg font-semibold text-slate-950">
                                        <a href="{{ route('jobs.show', $job->slug) }}" class="hover:text-emerald-700">{{ $job->title }}</a>
                                    </h3>
                                    <p class="mt-2 text-sm text-slate-600">{{ $job->location ?: 'Locatie flexibila' }} · {{ str($job->workplace_type->value)->replace('_', ' ')->title() }} · {{ str($job->employment_type->value)->replace('_', ' ')->title() }}</p>
                                </article>
                            @empty
                                <p class="rounded-md border border-dashed border-slate-300 p-4 text-sm text-slate-600">Nu exista joburi publicate momentan.</p>
                            @endforelse
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Joburi - {{ config('app.name', 'HireMe') }}</title>

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
                        <a href="{{ route('jobs.index') }}" class="text-emerald-700">Joburi</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-md bg-slate-950 px-4 py-2 text-white hover:bg-slate-800">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-slate-700 hover:text-slate-950">Login</a>
                            <a href="{{ route('register') }}" class="rounded-md bg-slate-950 px-4 py-2 text-white hover:bg-slate-800">Cont nou</a>
                        @endauth
                    </div>
                </nav>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-950">Joburi publice</h1>
                        <p class="mt-2 text-slate-600">Cauta roluri publicate si filtreaza dupa modul de lucru, locatie sau nivel.</p>
                    </div>
                    <p class="text-sm text-slate-500">{{ $jobs->total() }} rezultate</p>
                </div>

                <form method="GET" action="{{ route('jobs.index') }}" class="mt-8 grid gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-2 lg:grid-cols-6">
                    <div class="lg:col-span-2">
                        <label for="q" class="block text-sm font-medium text-slate-700">Cautare</label>
                        <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" type="search" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Titlu, descriere, companie">
                    </div>
                    <div>
                        <label for="location" class="block text-sm font-medium text-slate-700">Locatie</label>
                        <input id="location" name="location" value="{{ $filters['location'] ?? '' }}" type="search" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Cluj, remote">
                    </div>
                    <div>
                        <label for="workplace_type" class="block text-sm font-medium text-slate-700">Lucru</label>
                        <select id="workplace_type" name="workplace_type" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">Toate</option>
                            @foreach ($workplaceTypes as $type)
                                <option value="{{ $type->value }}" @selected(($filters['workplace_type'] ?? '') === $type->value)>{{ str($type->value)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="employment_type" class="block text-sm font-medium text-slate-700">Contract</label>
                        <select id="employment_type" name="employment_type" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">Toate</option>
                            @foreach ($employmentTypes as $type)
                                <option value="{{ $type->value }}" @selected(($filters['employment_type'] ?? '') === $type->value)>{{ str($type->value)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="experience_level" class="block text-sm font-medium text-slate-700">Nivel</label>
                        <input id="experience_level" name="experience_level" value="{{ $filters['experience_level'] ?? '' }}" type="search" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="mid">
                    </div>
                    <div class="flex items-end gap-3 lg:col-span-6">
                        <button type="submit" class="rounded-md bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Filtreaza</button>
                        <a href="{{ route('jobs.index') }}" class="rounded-md border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-100">Reseteaza</a>
                    </div>
                </form>

                <div class="mt-8 grid gap-4">
                    @forelse ($jobs as $job)
                        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-sm text-slate-500">{{ $job->company->name }}</p>
                                    <h2 class="mt-1 text-xl font-semibold text-slate-950">
                                        <a href="{{ route('jobs.show', [$job->company, $job]) }}" class="hover:text-emerald-700">{{ $job->title }}</a>
                                    </h2>
                                    <p class="mt-2 text-sm text-slate-600">{{ str($job->description)->limit(170) }}</p>
                                </div>
                                <a href="{{ route('jobs.show', [$job->company, $job]) }}" class="inline-flex shrink-0 items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-100">Detalii</a>
                            </div>
                            <dl class="mt-4 flex flex-wrap gap-2 text-sm text-slate-600">
                                <div class="rounded-md bg-slate-100 px-3 py-1">{{ $job->location ?: 'Locatie flexibila' }}</div>
                                <div class="rounded-md bg-slate-100 px-3 py-1">{{ str($job->workplace_type->value)->replace('_', ' ')->title() }}</div>
                                <div class="rounded-md bg-slate-100 px-3 py-1">{{ str($job->employment_type->value)->replace('_', ' ')->title() }}</div>
                                @if ($job->experience_level)
                                    <div class="rounded-md bg-slate-100 px-3 py-1">{{ str($job->experience_level)->title() }}</div>
                                @endif
                            </dl>
                        </article>
                    @empty
                        <p class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">Nu am gasit joburi pentru filtrele alese.</p>
                    @endforelse
                </div>

                <div class="mt-8">
                    {{ $jobs->links() }}
                </div>
            </main>
        </div>
    </body>
</html>

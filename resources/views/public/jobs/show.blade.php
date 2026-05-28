<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $job->title }} - {{ config('app.name', 'HireMe') }}</title>

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

            <main class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8">
                <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <a href="{{ route('jobs.index') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">Inapoi la joburi</a>
                    <p class="mt-6 text-sm text-slate-500">{{ $job->company->name }}</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">{{ $job->title }}</h1>
                    <dl class="mt-5 flex flex-wrap gap-2 text-sm text-slate-600">
                        <div class="rounded-md bg-slate-100 px-3 py-1">{{ $job->location ?: 'Locatie flexibila' }}</div>
                        <div class="rounded-md bg-slate-100 px-3 py-1">{{ str($job->workplace_type->value)->replace('_', ' ')->title() }}</div>
                        <div class="rounded-md bg-slate-100 px-3 py-1">{{ str($job->employment_type->value)->replace('_', ' ')->title() }}</div>
                        @if ($job->experience_level)
                            <div class="rounded-md bg-slate-100 px-3 py-1">{{ str($job->experience_level)->title() }}</div>
                        @endif
                    </dl>

                    @if ($job->salary_min || $job->salary_max)
                        <p class="mt-6 rounded-md bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                            Salariu: {{ $job->salary_min ? number_format($job->salary_min) : 'Nespecificat' }} - {{ $job->salary_max ? number_format($job->salary_max) : 'Nespecificat' }}
                        </p>
                    @endif

                    <div class="prose prose-slate mt-8 max-w-none">
                        {!! nl2br(e($job->description)) !!}
                    </div>
                </article>

                <aside class="h-fit rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-950">Aplica pentru rol</h2>

                    @auth
                        @if (auth()->user()->role === \App\Enums\UserRole::Candidate)
                            @if (! auth()->user()->hasVerifiedEmail())
                                <p class="mt-2 text-sm text-slate-600">Verifica adresa de email inainte sa trimiti candidatura.</p>
                                <a href="{{ route('verification.notice') }}" class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Verifica email</a>
                            @elseif (! auth()->user()->candidateProfile)
                                <p class="mt-2 text-sm text-slate-600">Completeaza profilul de candidat si incarca CV-ul curent inainte sa aplici.</p>
                                <a href="{{ route('candidate.profile.edit') }}" class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Completeaza profilul</a>
                            @elseif ($job->applications()->where('candidate_id', auth()->id())->exists())
                                <p class="mt-2 text-sm text-slate-600">Ai aplicat deja la acest rol. Poti urmari statusul candidaturii in dashboard.</p>
                                <a href="{{ route('candidate.applications.index') }}" class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Vezi aplicari</a>
                            @else
                                <p class="mt-2 text-sm text-slate-600">Trimite candidatura cu profilul tau si CV-ul curent.</p>

                                <form method="POST" action="{{ route('jobs.apply', [$job->company, $job]) }}" class="mt-5 space-y-4">
                                    @csrf

                                    <div>
                                        <label for="message" class="text-sm font-medium text-slate-800">Mesaj pentru angajator</label>
                                        <textarea id="message" name="message" rows="5" class="mt-2 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600" maxlength="2000">{{ old('message') }}</textarea>
                                        @error('message')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    @error('candidate_profile')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    @error('job')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror

                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Trimite candidatura</button>
                                </form>
                            @endif
                        @else
                            <p class="mt-2 text-sm text-slate-600">Aplicarea este disponibila doar pentru conturile de candidat.</p>
                            <a href="{{ route('dashboard') }}" class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2">Inapoi la dashboard</a>
                        @endif
                    @else
                        <p class="mt-2 text-sm text-slate-600">Intra in cont sau creeaza un cont de candidat pentru a trimite candidatura.</p>
                        <div class="mt-5 grid gap-3">
                            <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-md bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Aplică</a>
                            <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-100">Creeaza cont</a>
                        </div>
                    @endauth

                    <div class="mt-6 border-t border-slate-200 pt-6">
                        <h3 class="text-sm font-semibold text-slate-950">Companie</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $job->company->name }}</p>
                        @if ($job->company->location)
                            <p class="mt-1 text-sm text-slate-500">{{ $job->company->location }}</p>
                        @endif
                    </div>
                </aside>
            </main>
        </div>
    </body>
</html>

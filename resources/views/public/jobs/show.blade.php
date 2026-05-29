<x-public-layout>
    <x-slot name="title">{{ $job->title }} - {{ config('app.name', 'HireMe') }}</x-slot>

    <section class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8">
        <article class="rounded-lg border border-slate-200 bg-white p-6">
            <a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Inapoi la joburi</a>
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

        <aside class="h-fit space-y-5">
            <x-insights.responsiveness-card :score="$responsivenessScore" />

            @if ($fitScore)
                <x-insights.job-fit-card :fit-score="$fitScore" title="Potrivirea ta pentru rol" />
            @endif

            @if ($candidateAdvice)
                <section class="rounded-lg border border-violet-200 bg-violet-50 p-5">
                    <p class="text-sm font-semibold text-violet-950">Career Coach</p>
                    <p class="mt-2 text-sm text-slate-700">{{ $candidateAdvice['pitch'] }}</p>
                    <ul class="mt-3 space-y-1 text-sm text-slate-700">
                        @foreach ($candidateAdvice['actions'] as $action)
                            <li>{{ $action }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-950">Aplica pentru rol</h2>

                @auth
                    @if (auth()->user()->role === \App\Enums\UserRole::Candidate)
                        @if (! auth()->user()->hasVerifiedEmail())
                            <p class="mt-2 text-sm text-slate-600">Verifica adresa de email inainte sa trimiti candidatura.</p>
                            <a href="{{ route('verification.notice') }}" class="btn-primary mt-5 w-full">Verifica email</a>
                        @elseif (! auth()->user()->candidateProfile)
                            <p class="mt-2 text-sm text-slate-600">Completeaza profilul de candidat si incarca CV-ul curent inainte sa aplici.</p>
                            <a href="{{ route('candidate.profile.edit') }}" class="btn-primary mt-5 w-full">Completeaza profilul</a>
                        @elseif ($job->applications()->where('candidate_id', auth()->id())->exists())
                            <p class="mt-2 text-sm text-slate-600">Ai aplicat deja la acest rol. Poti urmari statusul candidaturii in dashboard.</p>
                            <a href="{{ route('candidate.applications.index') }}" class="btn-primary mt-5 w-full">Vezi aplicari</a>
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

                                <button type="submit" class="btn-primary w-full">Trimite candidatura</button>
                            </form>
                        @endif
                    @else
                        <p class="mt-2 text-sm text-slate-600">Aplicarea este disponibila doar pentru conturile de candidat.</p>
                        <a href="{{ route('dashboard') }}" class="btn-secondary mt-5 w-full">Inapoi la dashboard</a>
                    @endif
                @else
                    <p class="mt-2 text-sm text-slate-600">Intra in cont sau creeaza un cont de candidat pentru a trimite candidatura.</p>
                    <div class="mt-5 grid gap-3">
                        <a href="{{ route('login') }}" class="btn-primary w-full">Aplică</a>
                        <a href="{{ route('register') }}" class="btn-secondary w-full">Creeaza cont</a>
                    </div>
                @endauth

                <div class="mt-6 border-t border-slate-200 pt-6">
                    <h3 class="text-sm font-semibold text-slate-950">Companie</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $job->company->name }}</p>
                    @if ($job->company->location)
                        <p class="mt-1 text-sm text-slate-500">{{ $job->company->location }}</p>
                    @endif
                </div>
            </section>
        </aside>
    </section>
</x-public-layout>

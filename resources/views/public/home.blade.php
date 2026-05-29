<x-public-layout>
    <x-slot name="title">{{ config('app.name', 'HireMe') }}</x-slot>

    <section
        class="relative overflow-hidden border-b border-slate-900 bg-slate-950 text-white"
        style="background-image: linear-gradient(90deg, rgba(2, 6, 23, 0.96) 0%, rgba(15, 23, 42, 0.88) 45%, rgba(15, 23, 42, 0.38) 100%), url('{{ asset('images/recruitment-intelligence-hero.webp') }}'); background-position: center; background-size: cover;"
    >
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_26rem] lg:px-8 lg:py-16">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-300">Recruitment intelligence platform</p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                    Recrutare cu potrivire explicabila, raspunsuri transparente si decizii mai bune.
                </h1>
                <p class="mt-5 max-w-2xl text-base leading-7 text-slate-200">
                    HireMe combina joburi publice, profiluri relevante si un strat de intelligence care arata de ce un candidat se potriveste, cat de rapid raspund companiile si cum poate HR-ul lua decizii consecvente.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('jobs.index') }}" class="btn-primary">Caut un job</a>
                    <a href="{{ route('register', ['role' => 'employer']) }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-white/30 bg-white/10 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-slate-950">Angajez oameni</a>
                </div>

                <div class="mt-9 grid max-w-2xl gap-3 sm:grid-cols-3">
                    <div class="border-l-2 border-emerald-400 pl-3">
                        <p class="text-2xl font-bold">150</p>
                        <p class="mt-1 text-sm text-slate-300">joburi demo cu salariu si mod de lucru</p>
                    </div>
                    <div class="border-l-2 border-sky-400 pl-3">
                        <p class="text-2xl font-bold">46</p>
                        <p class="mt-1 text-sm text-slate-300">aplicari cu fit si response snapshots</p>
                    </div>
                    <div class="border-l-2 border-amber-300 pl-3">
                        <p class="text-2xl font-bold">23</p>
                        <p class="mt-1 text-sm text-slate-300">scorecard-uri demo pentru HR</p>
                    </div>
                </div>
            </div>

            <div class="grid content-center gap-3">
                <div class="rounded-lg border border-white/20 bg-white/95 p-4 text-slate-950 shadow-xl backdrop-blur">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Explainable Job Fit</p>
                            <h2 class="mt-1 text-lg font-bold">Senior Laravel Engineer</h2>
                        </div>
                        <span class="rounded-md bg-sky-50 px-3 py-1 text-sm font-bold text-sky-900">88%</span>
                    </div>
                    <div class="mt-4 grid gap-2">
                        <div>
                            <div class="flex justify-between text-xs font-semibold text-slate-600">
                                <span>Skills</span>
                                <span>92%</span>
                            </div>
                            <div class="mt-1 h-1.5 rounded-full bg-slate-200">
                                <div class="h-1.5 w-[92%] rounded-full bg-sky-600"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs font-semibold text-slate-600">
                                <span>Preferinte</span>
                                <span>86%</span>
                            </div>
                            <div class="mt-1 h-1.5 rounded-full bg-slate-200">
                                <div class="h-1.5 w-[86%] rounded-full bg-emerald-600"></div>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-sm text-slate-700">Match pe Laravel, MySQL, Redis si REST API. Range-ul salarial este compatibil.</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-slate-950 shadow-lg">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Anti-ghosting score</p>
                        <div class="mt-2 flex items-end justify-between gap-4">
                            <p class="text-3xl font-bold">91%</p>
                            <p class="text-sm font-semibold text-amber-900">risc scazut</p>
                        </div>
                        <p class="mt-2 text-sm text-slate-700">Raspuns mediu in 8h si aplicari vechi fara raspuns: 0.</p>
                    </div>

                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-slate-950 shadow-lg">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800">HR Copilot</p>
                        <p class="mt-2 text-sm font-semibold">Prioritizeaza candidatul</p>
                        <p class="mt-1 text-sm text-slate-700">Sugereaza intrebari, riscuri de clarificat si urmatorul pas.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-4 lg:grid-cols-5">
                <article class="rounded-lg border border-sky-200 bg-sky-50 p-5">
                    <p class="text-sm font-semibold text-sky-950">Job Fit Score</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">Candidatii vad scorul de potrivire, skill-urile matchuite si gap-urile reale.</p>
                </article>
                <article class="rounded-lg border border-violet-200 bg-violet-50 p-5">
                    <p class="text-sm font-semibold text-violet-950">Career Coach</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">Profilul primeste recomandari concrete pentru experienta, skill-uri si aplicari.</p>
                </article>
                <article class="rounded-lg border border-amber-200 bg-amber-50 p-5">
                    <p class="text-sm font-semibold text-amber-950">Anti-ghosting</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">Companiile afiseaza rata si timpul de raspuns, fara promisiuni ascunse.</p>
                </article>
                <article class="rounded-lg border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-sm font-semibold text-emerald-950">HR Copilot</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">Recruiterii primesc sumar, motive de interes si intrebari pentru interviu.</p>
                </article>
                <article class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm font-semibold text-slate-950">Scorecards</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">Interviurile sunt evaluate consecvent, cu scoruri si evidence salvate.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="border-b border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Joburi recomandate</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Roluri publicate de companii verificate.</h2>
                </div>
                <a href="{{ route('jobs.index') }}" class="btn-secondary">Vezi toate joburile</a>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($featuredJobs as $job)
                    <article class="rounded-lg border border-slate-200 bg-white p-5">
                        <p class="text-sm text-slate-500">{{ $job->company->name }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950">
                            <a href="{{ route('jobs.show', [$job->company, $job]) }}" class="hover:text-emerald-700">{{ $job->title }}</a>
                        </h3>
                        <p class="mt-3 text-sm text-slate-600">
                            {{ $job->location ?: 'Locatie flexibila' }}
                            · {{ str($job->workplace_type->value)->replace('_', ' ')->title() }}
                            · {{ str($job->employment_type->value)->replace('_', ' ')->title() }}
                        </p>
                        @if ($job->salary_min || $job->salary_max)
                            <p class="mt-3 inline-flex rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-900">
                                {{ $job->salary_min ? number_format($job->salary_min) : 'Nespecificat' }} - {{ $job->salary_max ? number_format($job->salary_max) : 'Nespecificat' }} RON
                            </p>
                        @endif
                    </article>
                @empty
                    <p class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-600 md:col-span-2 lg:col-span-3">Nu exista joburi publicate momentan.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section id="companii" class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Pentru ambele parti</p>
            <h2 class="mt-3 text-2xl font-bold tracking-tight text-slate-950">Un flux complet pentru candidati si companii.</h2>
            <p class="mt-4 text-sm leading-6 text-slate-600">
                Candidatii pot cauta joburi, aplica si urmari conversatiile. Companiile pot publica roluri, evalua aplicatii si mentine o experienta de recrutare transparenta.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Pentru candidati</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">Aplicari, mesaje, profil structurat si recomandari de imbunatatire.</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Pentru companii</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">Joburi, aplicari, copilot HR si scorecard-uri intr-un dashboard compact.</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Moderare</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">Companiile si joburile pot fi verificate inainte de publicare.</p>
            </div>
        </div>
    </section>
</x-public-layout>

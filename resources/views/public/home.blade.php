<x-public-layout>
    <x-slot name="title">{{ config('app.name', 'HireMe') }}</x-slot>

    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8 lg:py-14">
            <div class="flex flex-col justify-center">
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Marketplace de recrutare</p>
                <h1 class="mt-4 max-w-3xl text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">Conectam companiile ambitioase cu oamenii potriviti.</h1>
                <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600">Descopera roluri publicate de angajatori verificati sau creeaza un cont pentru a-ti administra recrutarea intr-un singur loc.</p>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('jobs.index') }}" class="btn-primary">Caut un job</a>
                    <a href="{{ route('register', ['role' => 'employer']) }}" class="btn-secondary">Angajez oameni</a>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-base font-semibold text-slate-950">Joburi recomandate</h2>
                    <a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Vezi toate</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($featuredJobs as $job)
                        <article class="rounded-md border border-slate-200 bg-white p-4">
                            <p class="text-sm text-slate-500">{{ $job->company->name }}</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">
                                <a href="{{ route('jobs.show', [$job->company, $job]) }}" class="hover:text-emerald-700">{{ $job->title }}</a>
                            </h3>
                            <p class="mt-2 text-sm text-slate-600">{{ $job->location ?: 'Locatie flexibila' }} · {{ str($job->workplace_type->value)->replace('_', ' ')->title() }} · {{ str($job->employment_type->value)->replace('_', ' ')->title() }}</p>
                        </article>
                    @empty
                        <p class="rounded-md border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-600">Nu exista joburi publicate momentan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section id="companii" class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Pentru candidati</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">Aplicari, mesaje si profil intr-un flux simplu, usor de urmarit.</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Pentru companii</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">Joburi, aplicari si comunicare cu talentele intr-un dashboard compact.</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Moderare</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">Companiile si joburile pot fi verificate inainte de publicare.</p>
            </div>
        </div>
    </section>
</x-public-layout>

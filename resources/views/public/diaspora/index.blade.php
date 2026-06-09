<x-public-layout>
    <x-slot name="title">Vino acasa - {{ config('app.name', 'HireMe') }}</x-slot>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Diaspora</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-emerald-950">Vino acasa</h1>
            <p class="mt-3 max-w-2xl text-sm text-emerald-900">
                Joburi din Romania cu pachet de relocare sau 100% remote, potrivite pentru romanii din strainatate
                care vor sa revina sau sa lucreze pentru companii de acasa.
            </p>

            <form method="GET" action="{{ route('diaspora.index') }}" class="mt-5 flex max-w-xl gap-3">
                <input name="q" value="{{ $filters['q'] ?? '' }}" type="search" class="field-control flex-1" placeholder="Rol, tehnologie, domeniu">
                <button type="submit" class="btn-primary">Cauta</button>
            </form>
        </div>

        <div class="mt-6 grid gap-4">
            @forelse ($jobs as $job)
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow-md">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-medium text-slate-500">{{ $job->company->name }}</p>
                        @if ($job->offers_relocation)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Pachet de relocare</span>
                        @endif
                        @if ($job->workplace_type === \App\Enums\WorkplaceType::Remote)
                            <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700">Remote</span>
                        @endif
                    </div>
                    <h2 class="mt-2 text-lg font-semibold text-slate-950">
                        <a href="{{ route('jobs.show', [$job->company, $job]) }}" class="hover:text-emerald-700">{{ $job->title }}</a>
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $job->location ?: 'Locatie flexibila' }}</p>
                </article>
            @empty
                <p class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-500">
                    Momentan nu exista joburi cu relocare sau remote. Revino in curand.
                </p>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $jobs->links() }}
        </div>
    </section>
</x-public-layout>

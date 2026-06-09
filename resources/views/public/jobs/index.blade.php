<x-public-layout>
    <x-slot name="title">Joburi - {{ config('app.name', 'HireMe') }}</x-slot>

    @php
        $activeFilters = collect([
            'Cautare' => $filters['q'] ?? null,
            'Locatie' => $filters['location'] ?? null,
            'Lucru' => isset($filters['workplace_type']) ? str($filters['workplace_type'])->replace('_', ' ')->title() : null,
            'Contract' => isset($filters['employment_type']) ? str($filters['employment_type'])->replace('_', ' ')->title() : null,
            'Nivel' => $filters['experience_level'] ?? null,
        ])->filter();
    @endphp

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="{ filtersOpen: false }">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-eyebrow">Job board transparent</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Joburi publice</h1>
                <p class="mt-2 max-w-2xl text-slate-600">Cauta roluri publicate si filtreaza dupa modul de lucru, locatie sau nivel.</p>
            </div>
            <div class="flex items-center gap-3">
                <p class="text-sm font-semibold text-slate-600">{{ $jobs->total() }} rezultate</p>
                <button type="button" class="btn-secondary md:hidden" @click="filtersOpen = ! filtersOpen" :aria-expanded="filtersOpen.toString()">
                    Filtre
                </button>
            </div>
        </div>

        @if ($activeFilters->isNotEmpty())
            <div class="mt-4 flex flex-wrap items-center gap-2">
                @foreach ($activeFilters as $label => $value)
                    <span class="chip-emerald">{{ $label }}: {{ $value }}</span>
                @endforeach
                <a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">Sterge filtrele</a>
            </div>
        @endif

        <form
            method="GET"
            action="{{ route('jobs.index') }}"
            class="mt-6 hidden gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid md:grid-cols-2 lg:grid-cols-6"
            :class="filtersOpen ? '!grid' : ''"
        >
            <div class="lg:col-span-2">
                <label for="q" class="field-label">Cautare</label>
                <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" type="search" class="field-control" placeholder="Titlu, descriere, companie">
            </div>
            <div>
                <label for="location" class="field-label">Locatie</label>
                <input id="location" name="location" value="{{ $filters['location'] ?? '' }}" type="search" class="field-control" placeholder="Cluj, remote">
            </div>
            <div>
                <label for="workplace_type" class="field-label">Lucru</label>
                <select id="workplace_type" name="workplace_type" class="field-control">
                    <option value="">Toate</option>
                    @foreach ($workplaceTypes as $type)
                        <option value="{{ $type->value }}" @selected(($filters['workplace_type'] ?? '') === $type->value)>{{ str($type->value)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="employment_type" class="field-label">Contract</label>
                <select id="employment_type" name="employment_type" class="field-control">
                    <option value="">Toate</option>
                    @foreach ($employmentTypes as $type)
                        <option value="{{ $type->value }}" @selected(($filters['employment_type'] ?? '') === $type->value)>{{ str($type->value)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="experience_level" class="field-label">Nivel</label>
                <input id="experience_level" name="experience_level" value="{{ $filters['experience_level'] ?? '' }}" type="search" class="field-control" placeholder="mid">
            </div>
            <div>
                <label for="near" class="field-label">Langa orasul</label>
                <input id="near" name="near" value="{{ $filters['near'] ?? '' }}" type="search" class="field-control" placeholder="Bucuresti">
            </div>
            <div>
                <label for="radius_km" class="field-label">Raza (km)</label>
                <input id="radius_km" name="radius_km" value="{{ $filters['radius_km'] ?? '' }}" type="number" min="1" max="500" class="field-control" placeholder="50">
            </div>
            <div class="flex items-end gap-3 lg:col-span-6">
                <button type="submit" class="btn-primary">Filtreaza</button>
                <a href="{{ route('jobs.index') }}" class="btn-secondary">Reseteaza</a>
            </div>
        </form>

        <div class="mt-6 grid gap-4">
            @forelse ($jobs as $job)
                @php($companyResponse = $responsiveness[$job->company_id] ?? null)
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow-md">
                    <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-medium text-slate-500">{{ $job->company->name }}</p>
                                @if ($companyResponse)
                                    <span class="chip-amber">Anti-ghosting {{ $companyResponse['score'] }}%</span>
                                @endif
                                @if ($job->published_at)
                                    <span class="chip">{{ $job->published_at->diffForHumans() }}</span>
                                @endif
                            </div>
                            <h2 class="mt-2 text-xl font-bold tracking-tight text-slate-950">
                                <a href="{{ route('jobs.show', [$job->company, $job]) }}" class="hover:text-emerald-700">{{ $job->title }}</a>
                            </h2>
                            <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{{ str($job->description)->limit(190) }}</p>
                        </div>
                        <div class="flex flex-col gap-3 lg:items-end">
                            @if ($job->salary_min || $job->salary_max)
                                <div class="rounded-lg bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-950">
                                    {{ $job->salary_min ? number_format($job->salary_min) : 'Nespecificat' }} - {{ $job->salary_max ? number_format($job->salary_max) : 'Nespecificat' }} RON
                                </div>
                            @endif
                            <a href="{{ route('jobs.show', [$job->company, $job]) }}" class="btn-secondary w-full justify-center lg:w-auto">Detalii</a>
                        </div>
                    </div>
                    <dl class="mt-4 flex flex-wrap gap-2 text-sm text-slate-600">
                        <div class="chip">{{ $job->location ?: 'Locatie flexibila' }}</div>
                        <div class="chip">{{ str($job->workplace_type->value)->replace('_', ' ')->title() }}</div>
                        <div class="chip">{{ str($job->employment_type->value)->replace('_', ' ')->title() }}</div>
                        @if ($job->experience_level)
                            <div class="chip">{{ str($job->experience_level)->title() }}</div>
                        @endif
                    </dl>
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">Nu am gasit joburi pentru filtrele alese.</p>
            @endforelse
        </div>

        <div class="mt-8">{{ $jobs->links() }}</div>
    </section>
</x-public-layout>

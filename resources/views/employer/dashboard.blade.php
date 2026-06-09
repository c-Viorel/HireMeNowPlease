<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-eyebrow">Recruiting command center</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">
                    {{ __('Employer Dashboard') }}
                </h2>
                <p class="mt-1 text-sm text-slate-600">Prioritizeaza candidatii si mentine raspunsurile vizibile.</p>
            </div>
            <a href="{{ route('employer.jobs.create') }}" class="btn-primary">Post a job</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 lg:grid-cols-4">
                <div class="surface p-5">
                    <p class="text-sm font-semibold text-slate-500">Aplicatii totale</p>
                    <p class="mt-2 text-3xl font-bold text-slate-950">{{ $totalApplications }}</p>
                    <p class="mt-1 text-xs text-slate-500">toate companiile tale</p>
                </div>
                <div class="surface p-5">
                    <p class="text-sm font-semibold text-slate-500">Open pipeline</p>
                    <p class="mt-2 text-3xl font-bold text-slate-950">{{ $openApplications }}</p>
                    <p class="mt-1 text-xs text-slate-500">submitted, viewed, shortlist, interview</p>
                </div>
                <div class="surface p-5">
                    <p class="text-sm font-semibold text-slate-500">Joburi active</p>
                    <p class="mt-2 text-3xl font-bold text-slate-950">{{ $activeJobs->sum('applications_count') }}</p>
                    <p class="mt-1 text-xs text-slate-500">aplicatii pe rolurile listate</p>
                </div>
                <div class="action-strip p-5">
                    <p class="text-sm font-semibold text-emerald-950">Next best action</p>
                    <p class="mt-2 text-sm leading-6 text-emerald-900">Deschide lista de aplicari si rezolva candidatii cu fit mare sau raspuns intarziat.</p>
                    <a href="{{ route('employer.applications.index') }}" class="mt-3 inline-flex text-sm font-bold text-emerald-800 hover:text-emerald-950">Review applications</a>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <section class="surface overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div>
                            <h3 class="text-base font-semibold text-slate-950">Priority candidates</h3>
                            <p class="mt-1 text-sm text-slate-500">Cele mai recente aplicari, ordonate dupa fit cand exista snapshot.</p>
                        </div>
                        <a href="{{ route('employer.applications.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Toate</a>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($priorityApplications as $application)
                            @php($fit = (int) data_get($application->fit_snapshot, 'score', 0))
                            <a href="{{ route('employer.applications.show', $application) }}" class="grid gap-3 px-5 py-4 transition hover:bg-slate-50 sm:grid-cols-[1fr_auto] sm:items-center">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $application->candidate->name }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $application->job->title }} · {{ $application->job->company->name }}</p>
                                </div>
                                <div class="flex items-center gap-2 sm:justify-end">
                                    <span class="chip">{{ str($application->status->value)->replace('_', ' ')->title() }}</span>
                                    <span @class([
                                        'chip-sky' => $fit >= 70,
                                        'chip-amber' => $fit > 0 && $fit < 70,
                                        'chip' => $fit === 0,
                                    ])>{{ $fit > 0 ? $fit.'% fit' : 'fit nou' }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="px-5 py-8 text-sm text-slate-600">No applications yet.</div>
                        @endforelse
                    </div>
                </section>

                <section class="surface overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 class="text-base font-semibold text-slate-950">Companies</h3>
                        <a href="{{ route('employer.companies.create') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Add</a>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($companies as $company)
                            <div class="px-5 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-slate-950">{{ $company->name }}</p>
                                        <p class="mt-1 text-sm text-slate-600">{{ $company->active_jobs_count }} active jobs</p>
                                    </div>
                                    <span class="chip-amber">{{ ucfirst($company->status) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-sm text-slate-600">No companies yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                <section class="surface overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h3 class="text-base font-semibold text-slate-950">Active jobs</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($activeJobs as $job)
                            <div class="px-5 py-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-950">{{ $job->title }}</p>
                                        <p class="mt-1 text-sm text-slate-600">{{ $job->company->name }} · {{ $job->location ?: 'Location flexible' }}</p>
                                    </div>
                                    <span class="chip-sky">
                                        {{ $job->applications_count }} {{ Str::plural('application', $job->applications_count) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-sm text-slate-600">No active jobs yet.</div>
                        @endforelse
                    </div>
                </section>

                <section class="surface overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h3 class="text-base font-semibold text-slate-950">Candidate experience health</h3>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        @forelse ($responseHealth as $health)
                            <x-insights.responsiveness-card :score="$health['score']" :label="$health['company']->name" compact="true" class="bg-amber-50" />
                        @empty
                            <p class="text-sm text-slate-600">No company response data yet.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="surface overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-slate-950">Latest messages</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($latestMessages as $message)
                        <div class="grid gap-2 px-5 py-4 lg:grid-cols-[1fr_20rem]">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $message->sender->name }}</p>
                                <p class="mt-1 line-clamp-2 text-sm leading-6 text-slate-700">{{ $message->body }}</p>
                            </div>
                            <p class="text-sm text-slate-500 lg:text-right">
                                {{ $message->conversation->application->job->title }}
                            </p>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-sm text-slate-600">No messages yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

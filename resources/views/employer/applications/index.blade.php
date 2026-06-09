<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-eyebrow">Pipeline inbox</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ __('Applications') }}</h2>
                <p class="mt-1 text-sm text-slate-600">Scaneaza rapid fit-ul, statusul si urmatoarea actiune.</p>
            </div>
            <a href="{{ route('employer.jobs.create') }}" class="btn-primary">Publica job</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 md:grid-cols-4">
                <div class="surface p-5">
                    <p class="text-sm font-semibold text-slate-500">Total</p>
                    <p class="mt-2 text-3xl font-bold text-slate-950">{{ $applicationStats['total'] }}</p>
                </div>
                <div class="surface p-5">
                    <p class="text-sm font-semibold text-slate-500">Open</p>
                    <p class="mt-2 text-3xl font-bold text-slate-950">{{ $applicationStats['open'] }}</p>
                </div>
                <div class="surface p-5">
                    <p class="text-sm font-semibold text-slate-500">High fit</p>
                    <p class="mt-2 text-3xl font-bold text-slate-950">{{ $applicationStats['high_fit'] }}</p>
                </div>
                <div class="action-strip p-5">
                    <p class="text-sm font-semibold text-emerald-950">Needs response</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-950">{{ $applicationStats['needs_response'] }}</p>
                </div>
            </section>

            <section class="surface overflow-hidden">
                <div class="hidden border-b border-slate-100 bg-slate-50 px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500 lg:grid lg:grid-cols-[1.2fr_1.4fr_8rem_9rem_7rem]">
                    <span>Candidat</span>
                    <span>Rol</span>
                    <span>Fit</span>
                    <span>Status</span>
                    <span class="text-right">Actiune</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($applications as $application)
                        @php
                            $fit = (int) data_get($application->fit_snapshot, 'score', 0);
                            $status = str($application->status->value)->replace('_', ' ')->title();
                            $actionLabel = match ($application->status->value) {
                                'submitted' => 'Review now',
                                'viewed' => 'Decide next',
                                'shortlisted' => 'Schedule',
                                'interview' => 'Scorecard',
                                default => 'Review',
                            };
                        @endphp
                        <article class="grid gap-4 px-5 py-5 lg:grid-cols-[1.2fr_1.4fr_8rem_9rem_7rem] lg:items-center">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $application->candidate->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $application->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium leading-snug text-slate-900">{{ $application->job->title }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $application->job->company->name }}</p>
                                @if ($application->message)
                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-500">{{ $application->message }}</p>
                                @endif
                            </div>
                            <div>
                                <span @class([
                                    'chip-sky' => $fit >= 70,
                                    'chip-amber' => $fit > 0 && $fit < 70,
                                    'chip' => $fit === 0,
                                ])>{{ $fit > 0 ? $fit.'%' : 'new' }}</span>
                            </div>
                            <div>
                                <span class="chip">{{ $status }}</span>
                            </div>
                            <div class="lg:text-right">
                                <a href="{{ route('employer.applications.show', $application) }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-800">{{ $actionLabel }}</a>
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-10 text-sm text-slate-600">No applications yet.</div>
                    @endforelse
                </div>
            </section>

            <div>{{ $applications->links() }}</div>
        </div>
    </div>
</x-app-layout>

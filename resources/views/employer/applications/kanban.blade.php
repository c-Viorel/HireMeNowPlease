<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-eyebrow">Pipeline board</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ __('Applications') }}</h2>
                <p class="mt-1 text-sm text-slate-600">Vizualizeaza pipeline-ul pe coloane si muta candidatii intre etape.</p>
            </div>
            <a href="{{ route('employer.jobs.create') }}" class="btn-primary">Publica job</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-[90rem] space-y-6 px-4 sm:px-6 lg:px-8">
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

            @include('employer.applications.partials.filters')

            <div class="grid auto-cols-[18rem] grid-flow-col gap-4 overflow-x-auto pb-4 lg:auto-cols-fr">
                @foreach ($columns as $column)
                    <section class="flex h-fit flex-col rounded-lg border border-slate-200 bg-slate-50">
                        <header class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                            <p class="text-sm font-bold text-slate-800">{{ $column['label'] }}</p>
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">{{ $column['applications']->count() }}</span>
                        </header>

                        <div class="space-y-3 p-3">
                            @forelse ($column['applications'] as $application)
                                @php($fit = (int) data_get($application->fit_snapshot, 'score', 0))
                                <article class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                                    <div class="flex items-start justify-between gap-2">
                                        <a href="{{ route('employer.applications.show', $application) }}" class="text-sm font-semibold text-slate-950 hover:text-emerald-700">{{ $application->candidate->name }}</a>
                                        <span @class([
                                            'chip-sky' => $fit >= 70,
                                            'chip-amber' => $fit > 0 && $fit < 70,
                                            'chip' => $fit === 0,
                                        ])>{{ $fit > 0 ? $fit.'%' : 'new' }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-600">{{ $application->job->title }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $application->created_at->diffForHumans() }}</p>

                                    <form method="POST" action="{{ route('employer.applications.status', $application) }}" class="mt-3">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" class="field-control text-xs">
                                            <option value="{{ $application->status->value }}" selected>{{ $column['label'] }}</option>
                                            @foreach ($statusTransitions as $statusValue)
                                                @if ($statusValue !== $application->status->value)
                                                    <option value="{{ $statusValue }}">→ {{ str($statusValue)->replace('_', ' ')->title() }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </form>
                                </article>
                            @empty
                                <p class="px-1 py-6 text-center text-xs text-slate-400">Niciun candidat</p>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>

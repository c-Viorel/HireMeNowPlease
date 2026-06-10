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

            @include('employer.applications.partials.filters')

            <p class="text-sm text-slate-500">{{ $applications->total() }} rezultate</p>

            <div x-data="{
                selected: [],
                get count() { return this.selected.length; },
                toggleAll(ids, checked) { this.selected = checked ? ids : []; },
            }">
                <form method="POST" action="{{ route('employer.applications.bulk-status') }}">
                    @csrf
                    @method('PATCH')
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="application_ids[]" :value="id">
                    </template>

                    <div x-show="count > 0" x-cloak class="mb-3 flex flex-wrap items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <span class="text-sm font-semibold text-emerald-900"><span x-text="count"></span> candidati selectati</span>
                        <select name="status" class="field-control max-w-56">
                            @foreach ($statusTransitions as $statusValue)
                                <option value="{{ $statusValue }}">Muta la: {{ str($statusValue)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-primary">Aplica</button>
                        <button type="button" @click="selected = []" class="text-sm font-semibold text-slate-500 hover:text-slate-700">Anuleaza</button>
                    </div>
                </form>

                <section class="surface overflow-hidden">
                    <div class="hidden border-b border-slate-100 bg-slate-50 px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500 lg:grid lg:grid-cols-[2.5rem_1.1fr_1.3fr_5rem_11rem_5rem] lg:items-center">
                        <span>
                            <input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600"
                                   @change="toggleAll([{{ $applications->pluck('id')->implode(',') }}], $event.target.checked)">
                        </span>
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
                            @endphp
                            <article class="grid gap-4 px-5 py-5 lg:grid-cols-[2.5rem_1.1fr_1.3fr_5rem_11rem_5rem] lg:items-center">
                                <div>
                                    <input type="checkbox" value="{{ $application->id }}" x-model.number="selected"
                                           class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                                </div>
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
                                    <form method="POST" action="{{ route('employer.applications.status', $application) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" class="field-control text-sm">
                                            <option value="{{ $application->status->value }}" selected>{{ $status }}</option>
                                            @foreach ($statusTransitions as $statusValue)
                                                @if ($statusValue !== $application->status->value)
                                                    <option value="{{ $statusValue }}">→ {{ str($statusValue)->replace('_', ' ')->title() }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                                <div class="flex items-center gap-3 lg:justify-end">
                                    @if ($application->cv_path)
                                        <a href="{{ route('employer.applications.cv', $application) }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700" title="Descarca CV">CV</a>
                                    @endif
                                    <a href="{{ route('employer.applications.show', $application) }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-800">Vezi</a>
                                </div>
                            </article>
                        @empty
                            <div class="px-6 py-10 text-sm text-slate-600">Nu exista candidati pentru filtrele selectate.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div>{{ $applications->links() }}</div>
        </div>
    </div>
</x-app-layout>

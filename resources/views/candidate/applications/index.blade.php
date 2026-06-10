<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-eyebrow">Aplicarile mele</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ __('My applications') }}</h2>
                <p class="mt-1 text-sm text-slate-600">Urmareste statusul, fit-ul si transparenta companiei.</p>
            </div>
            <a href="{{ route('jobs.index') }}" class="btn-primary">Cauta joburi</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Application submitted.</p>
            @endif

            <section class="surface overflow-hidden">
                <div class="hidden border-b border-slate-100 bg-slate-50 px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500 lg:grid lg:grid-cols-[1.4fr_8rem_9rem_8rem]">
                    <span>Rol</span>
                    <span>Fit</span>
                    <span>Status</span>
                    <span class="text-right">Actiune</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($applications as $application)
                        @php
                            $fit = (int) data_get($application->fit_snapshot, 'score', 0);
                            $response = (int) data_get($application->responsiveness_snapshot, 'score', 0);
                        @endphp
                        <article class="grid gap-4 px-5 py-5 lg:grid-cols-[1.4fr_8rem_9rem_8rem] lg:items-center">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $application->job->title }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $application->job->company->name }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if ($response > 0)
                                        <span class="chip-amber">Anti-ghosting {{ $response }}%</span>
                                    @endif
                                    <span class="chip">{{ $application->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <div>
                                <span @class([
                                    'chip-sky' => $fit >= 70,
                                    'chip-amber' => $fit > 0 && $fit < 70,
                                    'chip' => $fit === 0,
                                ])>{{ $fit > 0 ? $fit.'%' : 'new' }}</span>
                            </div>
                            <div>
                                <span class="chip">{{ str($application->status->value)->replace('_', ' ')->title() }}</span>
                            </div>
                            <div class="lg:text-right">
                                <div class="flex flex-col items-start gap-2 lg:items-end">
                                    <a href="{{ route('jobs.show', [$application->job->company, $application->job]) }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-800">Vezi jobul</a>
                                    <form method="POST" action="{{ route('conversations.store', $application) }}">
                                        @csrf
                                        <button type="submit" class="text-sm font-semibold text-slate-500 hover:text-slate-700">Trimite mesaj</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-10 text-sm text-slate-600">No applications yet.</div>
                    @endforelse
                </div>
            </section>

            <div class="mt-6">{{ $applications->links() }}</div>
        </div>
    </div>
</x-app-layout>

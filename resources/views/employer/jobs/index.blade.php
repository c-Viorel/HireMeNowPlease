<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ __('Jobs') }}</h2>
            <a href="{{ route('employer.jobs.create') }}" class="btn-primary">Publica job</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="rounded-md bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">Job salvat.</p>
            @endif

            <form method="GET" action="{{ route('employer.jobs.index') }}" class="surface grid gap-3 p-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <label for="q" class="field-label">Cautare</label>
                    <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" type="search" class="field-control" placeholder="Titlu job">
                </div>
                <div>
                    <label for="status" class="field-label">Status</label>
                    <select id="status" name="status" class="field-control">
                        <option value="">Toate</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected(($filters['status'] ?? '') === $statusOption->value)>{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="per_page" class="field-label">Pe pagina</label>
                    <select id="per_page" name="per_page" class="field-control">
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 10) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-3 lg:col-span-4">
                    <button type="submit" class="btn-primary">Filtreaza</button>
                    <a href="{{ route('employer.jobs.index') }}" class="btn-secondary">Reseteaza</a>
                    <p class="ml-auto self-center text-sm text-slate-500">{{ $jobs->total() }} joburi</p>
                </div>
            </form>

            <div class="surface overflow-hidden">
                <div class="divide-y divide-slate-100">
                    @forelse ($jobs as $job)
                        @php($count = $job->applications_count)
                        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-950">{{ $job->title }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $job->company->name }}</p>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $job->status->chipClass() }}">{{ $job->status->label() }}</span>
                                    <span @class([
                                        'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        'bg-emerald-100 text-emerald-700' => $count >= 5,
                                        'bg-sky-100 text-sky-700' => $count > 0 && $count < 5,
                                        'bg-slate-100 text-slate-500' => $count === 0,
                                    ])>{{ $count }} {{ $count === 1 ? 'aplicare' : 'aplicari' }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm font-semibold">
                                <a href="{{ route('employer.jobs.show', $job) }}" class="text-slate-600 hover:text-slate-900">Vezi</a>
                                <a href="{{ route('employer.jobs.edit', $job) }}" class="text-emerald-700 hover:text-emerald-800">Editeaza</a>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-sm text-slate-600">Nu exista joburi pentru filtrele selectate.</div>
                    @endforelse
                </div>
            </div>

            <div>{{ $jobs->links() }}</div>
        </div>
    </div>
</x-app-layout>

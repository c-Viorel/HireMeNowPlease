<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ __('Companies') }}</h2>
            <a href="{{ route('employer.companies.create') }}" class="btn-primary">Companie noua</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="rounded-md bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">Companie salvata.</p>
            @endif

            <form method="GET" action="{{ route('employer.companies.index') }}" class="surface grid gap-3 p-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <label for="q" class="field-label">Cautare</label>
                    <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" type="search" class="field-control" placeholder="Nume companie">
                </div>
                <div>
                    <label for="status" class="field-label">Status</label>
                    <select id="status" name="status" class="field-control">
                        <option value="">Toate</option>
                        <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Aprobata</option>
                        <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>In asteptare</option>
                        <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Respinsa</option>
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
                    <a href="{{ route('employer.companies.index') }}" class="btn-secondary">Reseteaza</a>
                    <p class="ml-auto self-center text-sm text-slate-500">{{ $companies->total() }} companii</p>
                </div>
            </form>

            <div class="surface overflow-hidden">
                <div class="divide-y divide-slate-100">
                    @forelse ($companies as $company)
                        @php($jobsCount = $company->jobs_count)
                        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-4">
                                @if ($company->logo_path)
                                    <img src="{{ Storage::disk('public')->url($company->logo_path) }}" alt="Logo {{ $company->name }}" class="h-12 w-12 rounded-md object-cover">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-md bg-slate-100 text-sm font-semibold text-slate-600">{{ Str::of($company->name)->substr(0, 1) }}</div>
                                @endif
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-950">{{ $company->name }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $company->statusChipClass() }}">{{ $company->statusLabel() }}</span>
                                        <span @class([
                                            'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-700' => $jobsCount >= 5,
                                            'bg-sky-100 text-sky-700' => $jobsCount > 0 && $jobsCount < 5,
                                            'bg-slate-100 text-slate-500' => $jobsCount === 0,
                                        ])>{{ $jobsCount }} {{ $jobsCount === 1 ? 'job' : 'joburi' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm font-semibold">
                                <a href="{{ route('employer.companies.show', $company) }}" class="text-slate-600 hover:text-slate-900">Vezi</a>
                                <a href="{{ route('employer.companies.edit', $company) }}" class="text-emerald-700 hover:text-emerald-800">Editeaza</a>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-sm text-slate-600">Nu exista companii pentru filtrele selectate.</div>
                    @endforelse
                </div>
            </div>

            <div>{{ $companies->links() }}</div>
        </div>
    </div>
</x-app-layout>

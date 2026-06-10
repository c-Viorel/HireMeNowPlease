@php($currentView = $filters['view'] ?? 'list')

<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('employer.applications.index', array_merge(request()->query(), ['view' => 'list'])) }}"
       @class([
           'rounded-md px-3 py-1.5 text-sm font-semibold transition',
           'bg-emerald-700 text-white' => $currentView === 'list',
           'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-slate-900' => $currentView !== 'list',
       ])>Lista</a>
    <a href="{{ route('employer.applications.index', array_merge(request()->query(), ['view' => 'kanban'])) }}"
       @class([
           'rounded-md px-3 py-1.5 text-sm font-semibold transition',
           'bg-emerald-700 text-white' => $currentView === 'kanban',
           'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-slate-900' => $currentView !== 'kanban',
       ])>Kanban</a>
</div>

<form method="GET" action="{{ route('employer.applications.index') }}" class="surface grid gap-3 p-4 md:grid-cols-2 lg:grid-cols-6">
    <input type="hidden" name="view" value="{{ $currentView }}">
    <div class="lg:col-span-2">
        <label for="q" class="field-label">Cautare</label>
        <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" type="search" class="field-control" placeholder="Nume candidat, email sau rol">
    </div>
    <div>
        <label for="status" class="field-label">Status</label>
        <select id="status" name="status" class="field-control">
            <option value="">Toate</option>
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption->value }}" @selected(($filters['status'] ?? '') === $statusOption->value)>{{ str($statusOption->value)->replace('_', ' ')->title() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="job" class="field-label">Job</label>
        <select id="job" name="job" class="field-control">
            <option value="">Toate</option>
            @foreach ($jobOptions as $jobOption)
                <option value="{{ $jobOption['id'] }}" @selected((string) ($filters['job'] ?? '') === (string) $jobOption['id'])>{{ $jobOption['title'] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="min_fit" class="field-label">Fit minim</label>
        <select id="min_fit" name="min_fit" class="field-control">
            <option value="">Oricare</option>
            @foreach ([50, 60, 70, 80, 90] as $threshold)
                <option value="{{ $threshold }}" @selected((string) ($filters['min_fit'] ?? '') === (string) $threshold)>{{ $threshold }}%+</option>
            @endforeach
        </select>
    </div>
    @if ($currentView !== 'kanban')
        <div>
            <label for="sort" class="field-label">Sortare</label>
            <select id="sort" name="sort" class="field-control">
                <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Cele mai noi</option>
                <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Cele mai vechi</option>
                <option value="fit_desc" @selected(($filters['sort'] ?? '') === 'fit_desc')>Fit descrescator</option>
                <option value="fit_asc" @selected(($filters['sort'] ?? '') === 'fit_asc')>Fit crescator</option>
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
    @endif
    <div class="flex items-end gap-3 lg:col-span-6">
        <button type="submit" class="btn-primary">Filtreaza</button>
        <a href="{{ route('employer.applications.index', ['view' => $currentView]) }}" class="btn-secondary">Reseteaza</a>
    </div>
</form>

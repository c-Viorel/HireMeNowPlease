@props(['coach' => null, 'title' => 'Career Coach'])

@if ($coach)
    <section {{ $attributes->merge(['class' => 'rounded-lg border border-violet-200 bg-violet-50 p-5']) }}>
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-violet-950">{{ $title }}</p>
                <h3 class="mt-1 text-lg font-bold text-slate-950">{{ $coach['title'] }}</h3>
            </div>
            <div class="rounded-md bg-white px-3 py-2 text-sm font-bold text-violet-900">{{ $coach['score'] }}%</div>
        </div>
        <ul class="mt-4 space-y-2 text-sm text-slate-700">
            @foreach ($coach['actions'] ?? [] as $action)
                <li>{{ $action }}</li>
            @endforeach
        </ul>
    </section>
@endif

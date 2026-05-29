@props(['fitScore' => null, 'title' => 'Job Fit Score'])

@if ($fitScore)
    <section {{ $attributes->merge(['class' => 'rounded-lg border border-sky-200 bg-sky-50 p-5']) }}>
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-sky-900">{{ $title }}</p>
                <h3 class="mt-1 text-2xl font-bold text-slate-950">{{ $fitScore['score'] }}%</h3>
                <p class="mt-1 text-sm text-sky-900">{{ $fitScore['label'] }}</p>
            </div>
            <div class="h-16 w-16 rounded-full border-4 border-sky-500 bg-white text-center text-sm font-bold leading-[3.5rem] text-sky-900">
                {{ $fitScore['score'] }}
            </div>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach ($fitScore['breakdown'] ?? [] as $item)
                <div class="rounded-md bg-white p-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">{{ $item['label'] }}</p>
                        <p class="text-sm font-bold text-sky-800">{{ $item['score'] }}%</p>
                    </div>
                    <div class="mt-2 h-1.5 rounded-full bg-slate-100">
                        <div class="h-1.5 rounded-full bg-sky-600" style="width: {{ $item['score'] }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-600">{{ $item['detail'] }}</p>
                </div>
            @endforeach
        </div>

        <p class="mt-4 text-sm font-medium text-slate-800">{{ $fitScore['recommendation'] }}</p>

        @if (! empty($fitScore['strengths']) || ! empty($fitScore['gaps']))
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-sm font-semibold text-slate-900">Puncte forte</p>
                    <ul class="mt-2 space-y-1 text-sm text-slate-700">
                        @foreach ($fitScore['strengths'] ?? [] as $strength)
                            <li>{{ $strength }}</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">De clarificat</p>
                    <ul class="mt-2 space-y-1 text-sm text-slate-700">
                        @forelse ($fitScore['gaps'] ?? [] as $gap)
                            <li>{{ $gap }}</li>
                        @empty
                            <li>Nu sunt gap-uri importante detectate.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        @endif
    </section>
@endif

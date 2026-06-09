@props(['fitScore' => null, 'title' => 'Job Fit Score', 'compact' => false])

@if ($fitScore)
    <section x-data="{ open: false }" {{ $attributes->merge(['class' => 'rounded-lg border border-sky-200 bg-sky-50 p-5']) }}>
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-sky-900">{{ $title }}</p>
                <h3 class="mt-1 text-2xl font-bold text-slate-950">{{ $fitScore['score'] }}%</h3>
                <p class="mt-1 text-sm text-sky-900">{{ $fitScore['label'] }}</p>
            </div>
            <div class="grid h-14 w-14 shrink-0 place-items-center rounded-full border-4 border-sky-500 bg-white text-sm font-bold text-sky-900">
                {{ $fitScore['score'] }}
            </div>
        </div>

        <p class="mt-3 text-sm font-medium text-slate-800">{{ $fitScore['recommendation'] }}</p>

        @if ($compact)
            <button type="button" @click="open = ! open" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-sky-800 hover:text-sky-900">
                <span x-show="! open">Vezi detaliile potrivirii</span>
                <span x-show="open" x-cloak>Ascunde detaliile</span>
                <svg class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M5 7.5 10 12l5-4.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        @endif

        <div @if ($compact) x-show="open" x-cloak @endif class="mt-4 space-y-4">
            <div class="grid gap-3 sm:grid-cols-2">
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

            @if (! empty($fitScore['strengths']) || ! empty($fitScore['gaps']))
                <div class="grid gap-4 md:grid-cols-2">
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
        </div>
    </section>
@endif

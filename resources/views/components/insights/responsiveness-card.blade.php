@props(['score' => null, 'compact' => false, 'label' => null])

@if ($score)
    <section {{ $attributes->merge(['class' => 'rounded-lg border border-amber-200 bg-amber-50 p-5']) }}>
        <div class="flex items-start justify-between gap-4">
            <div>
                @if ($label)
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">{{ $label }}</p>
                @endif
                <p class="text-sm font-semibold text-amber-950">Anti-ghosting score</p>
                <h3 class="mt-1 text-2xl font-bold text-slate-950">{{ $score['score'] }}%</h3>
                <p class="mt-1 text-sm text-amber-950">{{ $score['label'] }} · risc {{ $score['risk'] }}</p>
            </div>
            <div class="rounded-md bg-white px-3 py-2 text-right text-xs font-semibold text-slate-700">
                <span class="block text-lg text-amber-900">{{ $score['response_rate'] }}%</span>
                raspuns
            </div>
        </div>

        @unless ($compact)
            <dl class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-md bg-white p-3">
                    <dt class="text-xs font-medium text-slate-500">Timp mediu</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $score['average_response_hours'] ? $score['average_response_hours'].'h' : 'N/A' }}</dd>
                </div>
                <div class="rounded-md bg-white p-3">
                    <dt class="text-xs font-medium text-slate-500">Fara raspuns</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $score['unanswered_applications'] }}</dd>
                </div>
                <div class="rounded-md bg-white p-3">
                    <dt class="text-xs font-medium text-slate-500">Esantion</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $score['sample_size'] }} aplicari</dd>
                </div>
            </dl>

            <ul class="mt-4 space-y-1 text-sm text-slate-700">
                @foreach ($score['signals'] ?? [] as $signal)
                    <li>{{ $signal }}</li>
                @endforeach
            </ul>
        @endunless
    </section>
@endif

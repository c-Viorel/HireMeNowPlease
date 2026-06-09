<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">Teste de competente</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <p class="text-sm text-slate-600">
                Obtine badge-uri verificate care iti cresc scorul de potrivire la joburi.
            </p>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($assessments as $assessment)
                    @php($result = $results[$assessment->id] ?? null)
                    <div class="rounded-lg border border-slate-200 bg-white p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-slate-950">{{ $assessment->title }}</h3>
                            @if ($result && $result->badge_awarded)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Badge verificat</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ $assessment->category }} · {{ $assessment->questions_count }} intrebari</p>
                        @if ($result)
                            <p class="mt-2 text-sm text-slate-600">Ultimul scor: {{ $result->score }}%</p>
                        @endif
                        <a href="{{ route('candidate.assessments.show', $assessment) }}" class="btn-secondary mt-4 inline-flex">
                            {{ $result ? 'Reincearca' : 'Incepe testul' }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
